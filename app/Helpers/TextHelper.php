<?php

namespace App\Helpers;

class TextHelper
{
    /**
     * Возвращает правильную форму множественного числа в зависимости от количества
     *
     * @param int $number Число
     * @param array $forms Массив форм слова [единственное, родительный падеж множественного, множественное]
     * @return string
     */
    public static function pluralize(int $number, array $forms): string
    {
        $number = abs($number) % 100;
        $remainder = $number % 10;
        
        if ($number > 10 && $number < 20) {
            return $forms[2];
        }
        
        if ($remainder > 1 && $remainder < 5) {
            return $forms[1];
        }
        
        if ($remainder == 1) {
            return $forms[0];
        }
        
        return $forms[2];
    }
}
