<?php
namespace stalk\Logger;

use DateTime;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Logger;

/**
 * Обработчик записи логов в syslog в нужном для logstash формате.
 *
 * Для записи логов через syslog в logstash требуется формат RFC 3164
 * (http://www.elasticsearch.org/guide/en/logstash/current/plugins-inputs-syslog.html).
 * В заголовок отправляемого пакета добавляется информация:
 * время, имя хоста-отправителя, идентификатор приложения.
 * Также, в отличие от родительского класса, убрана
 * разбивка многострочных сообщений на отдельные пакеты.
 *
 * @author andrey-mostovoy <stalk.4.me@gmail.com>
 */
class LogstashSyslogUdpHandler extends SyslogUdpHandler {
    /**
     * @var string Имя приложения.
     */
    private $appName;

    /**
     * @var string Имя хоста.
     */
    private $hostName;

    /**
     * Конструктор.
     * @param string $sourceAppName имя приложения-отправителя
     * @param string $sourceHostName имя хоста-отправителя
     * @param string $syslogHost syslog-хост
     * @param int $syslogPort syslog-порт
     * @param mixed $syslogFacility источник логирования
     * @param integer $level The minimum logging level at which this handler will be triggered
     * @param Boolean $bubble Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct($sourceAppName, $sourceHostName,
                                $syslogHost, $syslogPort, $syslogFacility = LOG_USER,
                                $level = Logger::DEBUG, $bubble = true) {
        parent::__construct($syslogHost, $syslogPort, $syslogFacility, $level, $bubble);
        $this->appName = $sourceAppName;
        $this->hostName = $sourceHostName;
    }


    /**
     * Пишем данные в syslog.
     *
     * Переопределяем функцию для более правильной записи в syslog:
     * - Формируем заголовок по RFC 3164.
     * - Убраем разбивку многострочных сообщений на отдельные пакеты.
     * @param array $record данные для логирования
     */
    protected function write(array $record) {
        $header = $this->getSyslogHeader($this->logLevels[$record['level']], $record['datetime']);
        $this->socket->write($record['formatted'], $header);
    }

    /**
     * Формируем заголовок для syslog по RFC 3164.
     *
     * Заголовок должен быть в формате:
     * <приоритет>время_в_формате_RFC3164_или_ISO8601 хост программа
     * @param int $severity важность события для вычисления syslog-проиритета
     * @param DateTime $Time время события
     * @return string
     */
    private function getSyslogHeader($severity, DateTime $Time) {
        $priority = $severity + $this->facility;
        $formattedTime = $Time->format(DateTime::ISO8601);
        return sprintf('<%d>%s %s %s: ', $priority, $formattedTime, $this->hostName, $this->appName);
    }
}
