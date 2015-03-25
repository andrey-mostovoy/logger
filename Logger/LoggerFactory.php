<?php
namespace stalk\Logger;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LogstashFormatter;
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
 *     'path'        => LOG_PATH . 'Root.log',
 *     'level'       => Logger::DEBUG,
 *     'formatter'   => LogFormatter::class,
 *     'format'      => "%datetime% %level_name% %channel% %context%: %message%\n",
 *     'date_format' => 'Y-m-d H:i:s',
 *   ],
 *   'some_handler' => [
 *     'handler_param_key'  => 'handler_param_value',
 *     'formatter'           => SomeFormatter::class,
 *     'formatter_param_key' => 'formatter_param_value',
 *     ...
 *   ],
 * ],
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
            $Handler->setFormatter(static::createLogFormatter($handlerConfig));
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
    protected static function createHandler($handlerType, array $handlerConfig) {
        switch ($handlerType) {
            case 'stream':
                return new StreamHandler($handlerConfig['path'], $handlerConfig['level']);
            case 'syslog-logstash':
                return new LogstashSyslogUdpHandler(
                    $handlerConfig['app_name'],
                    $handlerConfig['source_host'],
                    $handlerConfig['syslog_host'],
                    $handlerConfig['syslog_port'],
                    $handlerConfig['syslog_facility'],
                    $handlerConfig['level']);
            case 'uberlog':
                return new StreamHandler('/var/log/uberlog/uberlog.log', $handlerConfig['level'], true, 0666, true);
            default:
                return null;
        }
    }

    /**
     * Получаем объект форматирования записей логов для файлов.
     * @param array $config конфиг
     * @return FormatterInterface экземпляр форматера
     */
    protected static function createLogFormatter(array $config) {
        if (isset($config['formatter'])) {
            switch ($config['formatter']) {
                case LogFormatter::class:
                    return new LogFormatter($config['format'], $config['date_format'], true);
                case LogstashFormatter::class:
                    return new LogstashFormatter($config['source_program'], $config['source_host'], 'extra_', 'ctx_', LogstashFormatter::V1);
            }
        }
        return new LogFormatter();
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
