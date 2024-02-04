<?php

declare(strict_types=1);

namespace Frigate\Tools\FileSystem;

use SplFileObject;

class FilesHelper {

    /**
     * create a file object in a specific mode
     * if an error occurs, null is returned
     * Modes: https://www.php.net/manual/en/function.fopen.php
     *      r   Open for reading only; place the file pointer at the beginning of the file.
     *      r+  Open for reading and writing; place the file pointer at the beginning of the file.
     *      w   Open for writing only; place the file pointer at the beginning of the file and truncate the file 
     *          to zero length. If the file does not exist, attempt to create it.
     *      w+  Open for reading and writing; place the file pointer at the beginning of the file and truncate
     *          the file to zero length. If the file does not exist, attempt to create it.
     *      a   Open for writing only; place the file pointer at the end of the file. If the file does not exist,
     *          attempt to create it. In this mode, fseek() has no effect, writes are always appended.
     *      a+  Open for reading and writing; place the file pointer at the end of the file. If the file does not
     *          exist, attempt to create it. In this mode, fseek() only affects the reading position, writes are
     *          always appended.
     *      x   Create and open for writing only; place the file pointer at the beginning of the file.
     *      x+  Create and open for reading and writing; otherwise it has the same behavior as 'x'.    
     */
    final public static function file(string $path, string $mode = 'w', bool $lock = false) : ?SplFileObject 
    {   
        $set_lock = LOCK_EX ;
        if ($lock) {
            // Best practice is to use binary mode when locking files   
            match ($mode) {
                'r'     => $mode = 'rb',
                'r+'    => $mode = 'rb+',
                'w'     => $mode = 'wb',
                'w+'    => $mode = 'wb+',
                'a'     => $mode = 'ab',
                'a+'    => $mode = 'ab+',
                'x'     => $mode = 'xb',
                'x+'    => $mode = 'xb+',
                default => $set_lock = null,
            };
            if ($mode === 'rb') {
                $set_lock = LOCK_SH ;
            }
        }
        try {
            $file = new SplFileObject($path, $mode);
            if ($lock) {
                $file->flock($set_lock);
            }
            return new SplFileObject($path, $mode);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * write a file object to the filesystem
     * 
     * if $length is null, the entire string will be written
     * close will release the lock and close the file turning it into null
     */
    final public static function writeFileObject(
        SplFileObject &$file, 
        string $data, 
        ?int $length = null, 
        bool $close = false
    ) : bool {
        $args = [$data];
        if ($length !== null) {
            $args[] = $length;
        }
        $written = $file->fwrite(...$args);
        if ($close) {
            $file->fflush();
            $file = null; 
        }
        return !!$written;
    }

    /**
     * read a file object from the filesystem
     * 
     * if $length is null, the entire file will be read
     * close will release the lock and close the file turning it into null
     */
    final public static function readFileObject(
        SplFileObject &$file, 
        ?int $length = null, 
        bool $close = false,
        bool $clear_stats_cache = false
    ) : ?string {

        if ($clear_stats_cache) {
            clearstatcache(true, $file->getPathname());
        }

        $length ??= $file->getSize();
        $content = $length > 0 ? $file->fread($length) : "";
        if ($content !== false && $close) {
            $file = null; // Will release the lock and close the file
        }
        return $content === false ? null : $content;
    }

    /**
     * write a file to the filesystem
     * 
     * if $length is null, the entire string will be written
     */
    final public static function writeFile(string $path, string $data, ?int $length = null) : bool
    {
        if (!($file = self::file($path, 'w', true))) {
            return false;
        }
        return self::writeFileObject($file, $data, $length, true);
    }

    /**
     * append a file to the filesystem
     * 
     * if $length is null, the entire string will be written
     */    
    final public static function appendFile(string $path, string $data, ?int $length = null) : bool 
    {
        if (!($file = self::file($path, 'a', true))) {
            return false;
        }
        return self::writeFileObject($file, $data, $length, true);
    }

    /**
     * read a file and returns the file contents
     */
    final public static function readFileContent(string $path, ?int $length = null, bool $clear_stats_cache = false) : ?string 
    {
        $file = self::readFile($path, $length, $clear_stats_cache);
        return $file ? $file['content'] : null;
    }

    /**
     * read a file and returns an array with the file info
     */
    final public static function readFile(string $path, ?int $length = null, bool $clear_stats_cache = false) : ?array 
    {
        if (($file = self::file($path, 'r', true)) === null) {
            return null;
        }
        if ($clear_stats_cache) {
            clearstatcache(true, $path);
        }
        $size = $file->getSize();
        $content = self::readFileObject($file, $length, true);
        return $content === null ? null : [
            'tmp_name'  => $path,
            'name'      => basename($path),
            'content'   => $content,
            'type'      => mime_content_type($path),
            'length'    => $length ?? $size,
            'size'      => $size,
            'error'     => 0
        ];
    }

    /**
     * delete files from the filesystem
     */
    final public static function deleteFiles(...$files) : int 
    {
        $deleted = 0;
        foreach ($files as $file) {
            if (file_exists($file)) {
                $deleted += (int)@unlink($file);
            }
        }
        return $deleted;
    }
}