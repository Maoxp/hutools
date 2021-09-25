<?php


namespace Maoxp\Tool\core\util;


class StrUtil
{
    /**
     * 以下的东西被认为是空的
     * "" (空字符串)
     * 0 (作为整数的0)
     * "0" (作为字符串的0)
     * 0.0 (作为浮点数的0)
     * null
     * false
     * array() (一个空数组)
     * $var; (一个声明了，但是没有值的变量)
     *
     * @param string $character
     * @return bool
     */
    public static function isEmpty(string $character): bool
    {
        return empty($character);
    }
}






