<?php
namespace stalk\Logger;

/**
 * Постпроцессор для логгера.
 * Добавляет в контекст данные глобального контекста.
 * @author andrey-mostovoy <stalk.4.me@gmail.com>
 */
final class GlobalContextProcessor {
    /**
     * Превращаем объект в колбэк.
     * @param array $record
     * @return array
     */
    function __invoke($record) {
        if (!isset($record['context'])) {
            $record['context'] = [];
        }

        foreach (LoggerFactory::getGlobalContext() as $k => $v) {
            $record['context'][$k] = $v;
        }
        return $record;
    }
}
