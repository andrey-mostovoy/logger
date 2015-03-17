<?php
namespace stalk\Logger;

use Monolog\Handler\AbstractHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use stalk\Config\Config;

/**
 * Фабрика для работы с логами.
 *
 * Для работы логов нужен конфиг `logger` в формате:
 * 'handlers' => [
 *   'stream' => [
 *     'path'        => PATH_LOG . 'Root.log',
 *     'level'       => Logger::DEBUG,
 *   ],
 *   'other_handler' => [
 *     ...
 *   ],
 *   ...
 * ],
 * 'formatter' => [
 *   'format'      => "%datetime% %level_name% %channel% %context%: %message%\n",
 *   'date_format' => 'Y-m-d H:i:s',
 * ]
 *
 * @author andrey-mostovoy <stalk.4.me@gmail.com>
 */
class LoggerFactory {
    /**
     * @var Logger[] Созданные объекты логгеров.
     */
    private static $Loggers = [];

    /**
     * @var GlobalContext Экземпляр глобального контекста.
     */
    private static $GlobalContext = null;

    /**
     * @var LogFormatter Экземпляр потокового форматера.
     */
    private static $LogFormatter = null;

    /**
     * @return Logger Экземпляр базового логгера.
     */
    public static function getRootLogger() {
        return static::getLogger('Root');
    }

    /**
     * Получаем объект логгера по типу.
     * Если еще не был создан, то создаем новый объект.
     * @param string $type Тип логгера.
     * @return Logger Экземпляр логгера.
     */
    public static function getLogger($type) {
        if (!isset(self::$Loggers[$type])) {
            self::$Loggers[$type] = self::createLogger($type);
        }
        return self::$Loggers[$type];
    }

    /**
     * Создаем объект логгера нужного типа.
     * @param string $type Тип логгера.
     * @return Logger Экземпляр логгера.
     */
    private static function createLogger($type) {
        $Logger = new Logger($type);

        foreach (Config::getInstance()->get('logger', 'handlers') as $handlerType => $handlerConfig) {
            $Handler = static::createHandler($handlerType, $handlerConfig);
            if (!$Handler) {
                continue;
            }
            $Handler->setFormatter(static::getLogFormatter());
            $Logger->pushHandler($Handler);
        }

        $Logger->pushProcessor(new GlobalContextProcessor());
        return $Logger;
    }

    /**
     * Создаем хэндлер по типу и конфигу.
     * @param string $handlerType Тип хэндлера записи логов.
     * @param array $handlerConfig Конфиг хэндлера.
     * @return AbstractHandler
     */
    protected static function createHandler($handlerType, $handlerConfig) {
        switch ($handlerType) {
            case 'stream':
                return new StreamHandler($handlerConfig['path'], $handlerConfig['level']);
            case 'syslog-logstash':
                return new LogstashSyslogUdpHandler(
                    $handlerConfig['source_program'],
                    $handlerConfig['source_host'],
                    $handlerConfig['syslog_host'],
                    $handlerConfig['syslog_port'],
                    $handlerConfig['syslog_facility'],
                    $handlerConfig['level']);
            default:
                return null;
        }
    }

    /**
     * Получаем объект форматирования записей логов для файлов.
     * @return LogFormatter Экземпляр потокового форматера.
     */
    private static function getLogFormatter() {
        if (!isset(self::$LogFormatter)) {
            $formatterConfig = Config::getInstance()->get('logger', 'formatter');
            self::$LogFormatter = new LogFormatter($formatterConfig['format'], $formatterConfig['date_format'], true);
        }
        return self::$LogFormatter;
    }

    /**
     * Получаем объект работы с глобальным контекстом.
     * @return GlobalContext Экземпляр глобального контекста.
     */
    public static function getGlobalContext() {
        if (!isset(self::$GlobalContext)) {
            self::$GlobalContext = new GlobalContext();
        }
        return self::$GlobalContext;
    }
}
