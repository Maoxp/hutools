<?php

/**
 * 支持匿名函数序列化
 *
 * @param mixed $data
 * @return string
 */
function OpisClosureSerialize($data): string
{
    return \Opis\Closure\serialize($data);
}

/**
 * 支持匿名函数反解析
 *
 * @param string $serialize
 * @return mixed
 * @example
 * ```
 * $fun = function te() {
 * echo "hello";
 * }
 * $res = serialize($fun);
 * $fun2 = unSerialize($res);
 * echo $fun2();
 * ```
 */
function OpisClosureUnSerialize(string $serialize)
{
    return \Opis\Closure\unserialize($serialize);
}

/**
 * 获取当前毫秒时间戳
 * @return float
 */
function getMicrosecond(): float
{
    return round(microtime(true) * 1000);
}

/**
 * 自定义写入日志
 * @param string $content 日志内容
 * @param string $dir 写入地址(绝对地址)
 * @param string $logFileName 文件名
 * @throws \Exception
 */
function writeLog(string $content, string $dir, string $logFileName = "")
{
    $maxFileSize = 10240; // in KB 10240
    if ($logFileName === '') {
        $logFileName = date("Ymd");
    }

    $dir .= "/logs";
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0775, true)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
    }

    try {
        @chmod($dir, 0775);
    } catch (\Exception $e) {
        throw new \RuntimeException("Failed to change permissions for directory \"$dir\": " . $e->getMessage(), $e->getCode());
    }

    $logFilePath = $dir . "/$logFileName.log";
    if (($fp = fopen($logFilePath, 'ab')) === false) {
        throw new \RuntimeException("Unable to append to log file: {$logFilePath}");
    }
    flock($fp, LOCK_EX);
    clearstatcache();

    if (filesize($logFilePath) > $maxFileSize * 1024) {
        flock($fp, LOCK_UN);
        fclose($fp);
        $newFile = sprintf("%s/%s.log", $dir, $logFileName);
        // 将文件过大size重命名，留存备用日志filename
        if (!rename($logFilePath, $newFile)) {
            throw new \RuntimeException("rename file {$logFilePath} fail");
        }
        // 利用append原子追加方式写日志，同时附加文件锁的形式避免俩次日志间产生穿插
        $writeResult = file_put_contents($logFilePath, date("Y-m-d H:i:s") . "\t" . $content . "\n\n", FILE_APPEND | LOCK_EX);
        if ($writeResult === false) {
            $error = error_get_last();
            throw new \RuntimeException("Unable to export log through file!: {$error['message']}");
        }
        // 写入日志长度是否相同
        $textSize = strlen($content);
        if ($writeResult < $textSize) {
            throw new \RuntimeException("Unable to export whole log through file! Wrote $writeResult out of $textSize bytes.");
        }
    } else {
        $writeResult = fwrite($fp, date("Y-m-d H:i:s") . "\t" . $content . "\n\n");
        if ($writeResult === false) {
            $error = error_get_last();
            throw new \RuntimeException("Unable to export log through file!: {$error['message']}");
        }
        $textSize = strlen($content);
        if ($writeResult < $textSize) {
            throw new \RuntimeException("Unable to export whole log through file! Wrote $writeResult out of $textSize bytes.");
        }
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    /**
     * 从阿拉伯数字+小写大写26字母(del:o|O)，获取指定长度随机字符串
     * @param int $length
     * @return string
     */
    function generatorString(int $length = 4): string
    {
        $char = "0,1,2,3,4,5,6,7,8,9,A,B,C,D,E,F,G,H,I,J,K,L,M,N,P,Q,R,S,T,U,V,W,X,Y,Z,a,b,c,d,e,f,g,h,i,j,k,l,m,n,p,q,r,s,t,u,v,w,x,y,z";
        $charArray = explode(',', $char);
        $charCount = count($charArray);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $charArray[rand(0, $charCount - 1)];
        }
        return $randomString;
    }

    /**
     * 获取验证码图片
     * @param string $randomString 需要显示的字符串
     * @return false|string
     */
    function generatorCaptchaImg(string $randomString)
    {
        $length = strlen($randomString);
        // 先定义图片的长、宽
        $img_height = 75 + random_int(1, 3) * $length;
        $img_width = 30;
        // 新建一个真彩色图像, 背景黑色图像
        $resourceImg = imagecreatetruecolor($img_height, $img_width);
        // 文字颜色
        $text_color = imagecolorallocate($resourceImg, 255, 255, 255);
        for ($i = 0; $i < $length; $i++) {
            $font = random_int(5, 6);
            $x = $i * $img_height / $length + random_int(1, 3);
            $y = random_int(1, 10);
            // 写入字符
            imagestring($resourceImg, $font, $x, $y, $randomString[$i], $text_color);
        }
        ob_start();
        // 生成png格式
        ImagePNG($resourceImg);
        $data = ob_get_clean();
        ImageDestroy($resourceImg);

        return $data;
    }

    /**
     * 数组or对象转xml
     * @param array|object $data
     * @return string
     */
    function data2Xml($data): string
    {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        $xml = '';
        foreach ($data as $key => $val) {
            if (is_null($val)) {
                $xml .= "<$key/>\n";
            } else {
                if (!is_numeric($key)) {
                    $xml .= "<$key>";
                }
                if (is_array($val) || is_object($val)) {
                    $xml .= data2Xml($val);
                } else {
                    $xml .= $val;
                }
                if (!is_numeric($key)) {
                    $xml .= "</$key>";
                }
            }
        }
        return $xml;
    }
}