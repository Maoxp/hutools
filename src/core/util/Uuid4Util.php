<?php


namespace Maoxp\Tool\core\util;


class Uuid4Util
{
    /**
     * 返回36位字符串 5fc8d8c6-0080-44ef-a8d7-a8833cf6d9f4
     * @return string
     * @throws \Exception
     */
    public static function generateUuid(): string
    {
        // 使用密码级别的随机数生成器产生高质量随机性，将二进制转为16进制，$hex有32个字符
        $hex = bin2hex(random_bytes(16));
        // 一个字节有两个十六进制字符，提取8个十六进制字符，4字节32位比特，表示时间低字段
        $time_low = substr($hex, 0, 8);
        //两字节表示时间中字段
        $time_mid = substr($hex, 8, 4);
        //此处字符4（不是数字）表示uuid创建版本为4，意指使用随机数生成，占用4比特位
        $time_hi_and_version = '4' . substr($hex, 13, 3);
        // 提取8比特位，并转换为十进制整数
        $clock_seq_hi_and_reserved = base_convert(substr($hex, 16, 2), 16, 10);
        //将前两位设置为0
        $clock_seq_hi_and_reserved &= 0b00111111;
        //将前两位设置为10
        $clock_seq_hi_and_reserved |= 0b10000000;
        //提取1字节（8位）做时钟序列低位
        $clock_seq_low = substr($hex, 18, 2);
        //余下的6字节48位作为设备标识
        $node = substr($hex, 20);
        //格式化输出
        $uuid = sprintf('%s-%s-%s-%02x%s-%s',
            $time_low, $time_mid, $time_hi_and_version,
            $clock_seq_hi_and_reserved, $clock_seq_low,
            $node
        );
        return $uuid;
    }
}
