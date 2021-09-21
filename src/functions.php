<?php
/**
 * 获取当前毫秒时间戳
 * @return float
 */
function getMicrosecondOfNow(): float
{
    [$micro, $sec] = explode(" ", microtime());
    return (float)sprintf("%.0f", ((float)$micro + (float)$sec) * 1000);
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
 *
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
        chmod($dir, 0775);
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
}