<?php
namespace stalk\Logger;

use ArrayIterator;
use IteratorAggregate;

/**
 * Глобальный котнекст для логгера.
 * @author andrey-mostovoy <stalk.4.me@gmail.com>
 */
final class GlobalContext implements IteratorAggregate {
    /**
     * @var array Информация в контексте.
     */
    private $info = [];

    /**
     * Добавляем информацию в контекст.
     * Добавляемая информация может перезатереть добавленное ранее значение.
     * @param string $key ключ
     * @param string $value значение
     */
    public function addInfo($key, $value) {
        $this->info[$key] = (string) $value;
    }

    /**
     * Получем внешний итератор.
     * @return ArrayIterator
     */
    public function getIterator() {
        return new ArrayIterator($this->info);
    }


}
