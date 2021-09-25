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

    /**
     * 返回给定字符串中的字节数
     * 此方法通过使用`mb_strlen`，确保字符串被视为字节数组
     * @param string $string the string being measured for length
     * @return int the number of bytes in the given string.
     */
    public static function byteLength(string $string): int
    {
        return mb_strlen($string, '8bit');
    }

    /**
     * 返回由start和length参数指定的字符串部分
     * 此方法通过使用`mbmb_substr`，确保字符串被视为字节数组
     * @param string $str the input string. Must be one character or longer.
     * @param int $start the starting position
     * @param int|null $length the desired portion length. If not specified or `null`, there will be
     * no limit on length i.e. the output will be until the end of the string.
     * @return string the extracted part of string, or FALSE on failure or an empty string.
     * @see https://secure.php.net/manual/en/function.substr.php
     */
    public static function byteSubstr(string $str, int $start, int $length = null): string
    {
        if ($length === null) {
            $length = self::byteLength($str);
        }
        return mb_substr($str, $start, $length, '8bit');
    }

    /**
     * Returns the trailing name component of a path.
     * This method is similar to the php function `basename()` except that it will
     * treat both \ and / as directory separators, independent of the operating system.
     * This method was mainly created to work on php namespaces. When working with real
     * file paths, php's `basename()` should work fine for you.
     * Note: this method is not aware of the actual filesystem, or path components such as "..".
     *
     * @param string $path A path string.
     * @param string $suffix If the name component ends in suffix this will also be cut off.
     * @return string the trailing name component of the given path.
     * @see https://secure.php.net/manual/en/function.basename.php
     */
    public static function basename(string $path, string $suffix = ''): string
    {
        if (($len = mb_strlen($suffix)) > 0 && mb_substr($path, -$len) === $suffix) {
            $path = mb_substr($path, 0, -$len);
        }
        $path = rtrim(str_replace('\\', '/', $path), '/\\');
        if (($pos = mb_strrpos($path, '/')) !== false) {
            return mb_substr($path, $pos + 1);
        }

        return $path;
    }

    /**
     * Returns parent directory's path.
     * This method is similar to `dirname()` except that it will treat
     * both \ and / as directory separators, independent of the operating system.
     *
     * @param string $path A path string.
     * @return string the parent directory's path.
     * @see https://secure.php.net/manual/en/function.basename.php
     */
    public static function dirname(string $path): string
    {
        $pos = mb_strrpos(str_replace('\\', '/', $path), '/');
        if ($pos !== false) {
            return mb_substr($path, 0, $pos);
        }

        return '';
    }

    /**
     * 将字符串截断为指定的字符数
     *
     * @param string $str The string to truncate.
     * @param int $length How many characters from original string to include into truncated string.
     * @param string $suffix String to append to the end of truncated string.
     * @param string|null $encoding The charset to use, defaults to charset currently used by application.
     * @return string the truncated string.
     */
    public static function truncate(string $str, int $length, string $suffix = '...', string $encoding = null): string
    {
        if ($encoding === null) {
            $encoding = 'UTF-8';
        }

        if (mb_strlen($str, $encoding) > $length) {
            return rtrim(mb_substr($str, 0, $length, $encoding)) . $suffix;
        }

        return $str;
    }
}






