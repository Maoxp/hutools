<?php


namespace Maoxp\Tool\core\util;


use Exception;

class FileUtil
{
    private static $mimeMagicFile = "mimeTypes.php";
    public static $mimeAliasesFile = 'mimeAliases.php';

    /**
     * 创建目录，会递归创建每层目录
     *
     * @param string $path path of the directory to be created.
     * @param int $mode the permission to be set for the created directory.
     * @param bool $recursive whether to create parent directories if they do not exist.
     * @return bool whether the directory is created successfully
     * @throws Exception if the directory could not be created (i.e. php error due to parallel changes)
     */
    public static function mkdir(string $path, int $mode = 0775, bool $recursive = true): bool
    {
        if (is_dir($path)) {
            return true;
        }
        $parentDir = dirname($path);
        // recurse if parent dir does not exist and we are not at the root of the file system.
        if ($recursive && !is_dir($parentDir) && $parentDir !== $path) {
            static::mkdir($parentDir, $mode, true);
        }
        try {
            if (!mkdir($path, $mode)) {
                return false;
            }
        } catch (Exception $e) {
            if (!is_dir($path)) {// https://github.com/yiisoft/yii2/issues/9288
                throw new Exception("Failed to create directory \"$path\": " . $e->getMessage(), $e->getCode(), $e);
            }
        }
        try {
            return chmod($path, $mode);
        } catch (Exception $e) {
            throw new Exception("Failed to change permissions for directory \"$path\": " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * 规范化文件/目录路径
     *
     * e.g. "\a/b\c" becomes "/a/b/c"
     * e.g. "/a/b/c/" becomes "/a/b/c"
     * e.g. "/a///b/c" becomes "/a/b/c"
     * e.g. "/a/./b/../c" becomes "/a/c"
     *
     * note:对于已注册的流包装器，将跳过连续斜杠规则和".."/"."translations
     * @param string $path
     * @param string $ds
     * @return string
     */
    public static function normalizePath(string $path, string $ds = DIRECTORY_SEPARATOR): string
    {
        $path = rtrim(strtr($path, '/\\', $ds . $ds), $ds);
        if (strpos($ds . $path, "{$ds}.") === false && strpos($path, "{$ds}{$ds}") === false) {
            return $path;
        }
        // 获取当前系统可使用的流类型
        foreach (stream_get_wrappers() as $protocol) {
            if (strpos($path, "{$protocol}://") === 0) {
                return $path;
            }
        }
        // the path may contain ".", ".." or double slashes, need to clean them up
        if (strpos($path, "{$ds}{$ds}") === 0 && $ds == '\\') {
            $parts = [$ds];
        } else {
            $parts = [];
        }
        foreach (explode($ds, $path) as $part) {
            if ($part === '..' && !empty($parts) && end($parts) !== '..') {
                array_pop($parts);
            } elseif ($part === '.' || $part === '' && !empty($parts)) {
                continue;
            } else {
                $parts[] = $part;
            }
        }
        $path = implode($ds, $parts);
        return $path === '' ? '.' : $path;
    }

    /**
     * 获取mime类型
     * based on [finfo_open](https://secure.php.net/manual/en/function.finfo-open.php).如果`fileInfo` extension is not installed,
     * it will fall back to [[getMimeTypeByExtension()]] when `$checkExtension` is true.
     * @param string $file
     * @param string|null $magicFile
     * @param bool $checkExtension
     * @return string|null the MIME type (e.g. `text/plain`). Null is returned if the MIME type cannot be determined.
     */
    public static function getMimeType(string $file, string $magicFile = null, bool $checkExtension = true): ?string
    {
        if (!extension_loaded('fileinfo')) {
            if ($checkExtension) {
                return static::getMimeTypeByExtension($file, $magicFile);
            }
            throw new \RuntimeException('The fileinfo PHP extension is not installed.');
        }

        // 返回 mime 类型
        $info = finfo_open(FILEINFO_MIME_TYPE, $magicFile);
        if ($info) {
            $result = finfo_file($info, $file);
            finfo_close($info);
            if ($result !== false) {
                return $result;
            }
        }

        return static::getMimeTypeByExtension($file, $magicFile);
    }

    /**
     * 根据指定文件的扩展名确定MIME类型.
     * 此方法将在扩展名和MIME类型之间使用本地映射
     * @param string $file 文件名
     * @param string|null $magicFile 包含所有可用MIME类型信息的文件的路径
     * @return mixed|null
     */
    public static function getMimeTypeByExtension(string $file, string $magicFile = null)
    {
        $mimeTypes = static::loadMimeTypes($magicFile);

        if (($ext = pathinfo($file, PATHINFO_EXTENSION)) !== '') {
            $ext = strtolower($ext);
            if (isset($mimeTypes[$ext])) {
                return $mimeTypes[$ext];
            }
        }

        return null;
    }

    /**
     * @param string $mimeType
     * @param string|null $magicFile
     * @return int[]|string[]
     */
    public static function getExtensionsByMimeType(string $mimeType, string $magicFile = null):array
    {
        $aliases = static::loadMimeAliases(static::$mimeAliasesFile);
        if (isset($aliases[$mimeType])) {
            $mimeType = $aliases[$mimeType];
        }

        $mimeTypes = static::loadMimeTypes($magicFile);
        return array_keys($mimeTypes, mb_strtolower($mimeType, 'UTF-8'), true);
    }

    private static $_mimeTypes = [];
    /**
     * Loads MIME types from the specified file.
     * @param string $magicFile the path (or alias) of the file that contains all available MIME type information.
     * If this is not set, the file specified by [[mimeMagicFile]] will be used.
     * @return array the mapping from file extensions to MIME types
     */
    protected static function loadMimeTypes(string $magicFile): array
    {
        if ($magicFile === null) {
            $magicFile = static::$mimeMagicFile;
        }
        if (!isset(self::$_mimeTypes[$magicFile])) {
            self::$_mimeTypes[$magicFile] = require $magicFile;
        }

        return self::$_mimeTypes[$magicFile];
    }

    private static $_mimeAliases = [];
    /**
     * Loads MIME aliases from the specified file.
     * @param string $aliasesFile the path (or alias) of the file that contains MIME type aliases.
     * If this is not set, the file specified by [[mimeAliasesFile]] will be used.
     * @return array the mapping from file extensions to MIME types
     */
    protected static function loadMimeAliases(string $aliasesFile): array
    {
        if ($aliasesFile === null) {
            $aliasesFile = static::$mimeAliasesFile;
        }
        if (!isset(self::$_mimeAliases[$aliasesFile])) {
            self::$_mimeAliases[$aliasesFile] = require $aliasesFile;
        }

        return self::$_mimeAliases[$aliasesFile];
    }

    /**
     * @param string $dir
     * @return string
     */
    private static function clearDir(string $dir): string
    {
        if (!is_dir($dir)) {
            throw new \RuntimeException("The dir argument must be a directory: $dir");
        }
        return rtrim($dir, DIRECTORY_SEPARATOR);
    }

    /**
     * @param string $dir
     * @return resource
     */
    private static function openDir(string $dir)
    {
        $handle = opendir($dir);
        if ($handle === false) {
            throw new \RuntimeException("Unable to open directory: $dir");
        }
        return $handle;
    }

    /**
     * 返回在指定目录和子目录下找到的目录
     * @param string $dir 用于查找目录的目录
     * @return array 在目录下找到的目录，没有特定顺序。排序取决于所使用的文件系统
     */
    public static function findDirectoriesFromDir(string $dir): array
    {
        $list = [];
        $dir = self::clearDir($dir);
        $handle = self::openDir($dir);
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $list[] = $path;
                $list = array_merge($list, static::findDirectoriesFromDir($path));
            }
        }
        closedir($handle);

        return $list;
    }

    /**
     * 递归从目录下列出所有文件，排除.和..
     * @param string $dir 用于查找文件的目录
     * @return array 在目录下找到的文件，没有特定顺序。排序取决于所使用的文件系统
     */
    public static function findFilesFromDir(string $dir): array
    {
        $dir = self::clearDir($dir);
        $list = [];
        $handle = self::openDir($dir);
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_file($path)) {
                $list[] = $path;
            } elseif (is_dir($path)) {
                $list = array_merge($list, static::findFilesFromDir($path));
            }
        }
        closedir($handle);

        return $list;
    }

    // todo
    public static function getFileName(string $filePath){}
}