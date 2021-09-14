<?php


namespace Maoxp\Tool\core\util;


class RandomUtil
{
    /**
     * 获得随机数[0, 2^32)
     *
     * @return int
     */
    public static function randomInt(): int
    {
        return mt_rand(0, mt_getrandmax());
    }

    public static function randomFloat($min = 0, $max = 1): float
    {
        return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }
}