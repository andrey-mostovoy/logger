<?php
namespace stalk\Logger;

use Monolog\Formatter\LineFormatter;

/**
 * Форматтер для записи логов в файл.
 * По сравнению с LineFormatter переопределен метод превращения
 * данных в строку для более красивого вывода массивов.
 * @author andrey-mostovoy <stalk.4.me@gmail.com>
 */
final class LogFormatter extends LineFormatter {
    /**
     * @inheritdoc
     */
    public function stringify($value) {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $k => $v) {
                $result[] = "$k=$v";
            }
            return '[' . implode(', ', $result) . ']';
        }
        return parent::stringify($value);
    }
}
