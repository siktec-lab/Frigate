<?php

namespace Frigate\Helpers;

use FilesystemIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use SplFileInfo;
use Frigate\Helpers\Files;

class Directories 
{

    final public static function createDirectory(string $path, int $permissions = 0755, $recursive = true) : bool
    {
        if (!is_dir($path)) {
            return mkdir($path, $permissions, $recursive);
        }
        return true;
    }
    
    final public static function secureDirectory(string $path) : bool
    {
        $content = <<<EOT
        # Don't list directory contents
        IndexIgnore *
        # Disable script execution
        AddHandler cgi-script .php .pl .jsp .asp .sh .cgi
        Options -ExecCGI -Indexes
        EOT;

        if (is_dir($path)) {
            $file = $path . DIRECTORY_SEPARATOR . '.htaccess';
            if (!file_exists($file)) {
                return file_put_contents($path . DIRECTORY_SEPARATOR . '.htaccess', $content) ? true : false;
            }
            return true;
        } else {
            return false;
        }
    }
    
    final public static function createSecureDirectory($path, int $permissions = 0755, $recursive = true) : bool
    {
        if (self::createDirectory($path, $permissions, $recursive)) {
            return self::secureDirectory($path);
        }
        return false;
    }

    final public static function removeDirectory($path) : void
    {
        // This currently will only directories with no subdirectories
        if (!is_dir($path)) {
            return;
        }
        $files = glob($path . DIRECTORY_SEPARATOR . '{.,}*', GLOB_BRACE);
        @array_map('unlink', $files);
        @rmdir($path);
    }

    final public static function listDirectory(string|SplFileInfo $path) : ?RecursiveIteratorIterator
    {
        $folder = is_string($path) ? new SplFileInfo($path) : $path;
        if (!$folder->isDir())
            return null;
        /** @var RecursiveIteratorIterator SplFileInfo[] $files */
        return new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder->getRealPath()),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
    }

    final public static function clearDirecory(string $path_dir, bool $self = false) : bool
    {
        if (!file_exists($path_dir)) return false;
        $di = new RecursiveDirectoryIterator($path_dir, FilesystemIterator::SKIP_DOTS);
        $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ( $ri as $file ) {
            $file->isDir() ?  self::rrmdir($file) : Files::deleteFiles($file);
        }
        if ($self) {
            rmdir($path_dir);
        }
        return true;
    }

    final public static function  rrmdir(string $path_dir) : bool
    {
        array_map(
            fn (string $file) => is_dir($file) ? self::rrmdir($file) : unlink($file), glob($path_dir . '/' . '*')
        );
        return rmdir($path_dir);
    }

    final public static function hashDirectory(string $directory) : string|bool
    {
        if (!is_dir($directory)) { 
            return false; 
        }
        $files = [];
        $dir = dir($directory);
        while (false !== ($file = $dir->read())) {
            if ($file != '.' and $file != '..') {
                if (is_dir($directory . DIRECTORY_SEPARATOR . $file)) { 
                    $files[] = self::hashDirectory($directory . DIRECTORY_SEPARATOR . $file); 
                } else { 
                    $files[] = md5_file($directory . DIRECTORY_SEPARATOR . $file); 
                }
            }
        }
        $dir->close();
        return md5(implode('', $files));
    }

    final public static function copy(string $source, string $dest, int $permissions = 0755) : bool
    {
        $sourceHash = self::hashDirectory($source);
        // Check for symlinks
        if (is_link($source))
            return symlink(readlink($source), $dest);
        // Simple copy for a file
        if (is_file($source)) {
            $file = explode(DIRECTORY_SEPARATOR, $source);
            return copy(
                    $source, is_dir($dest) 
                    ? rtrim($dest, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.end($file) 
                    : $dest
            );
        }
        // Make destination directory
        if (!is_dir($dest)) 
            mkdir($dest, $permissions, true);
        // Loop through the folder
        $dir = dir($source);
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..')
                continue;
            // Deep copy directories
            if ($sourceHash != self::hashDirectory($source.DIRECTORY_SEPARATOR.$entry)) {
                if (!self::copy($source.DIRECTORY_SEPARATOR.$entry, $dest.DIRECTORY_SEPARATOR.$entry, $permissions)) {
                    return false;
                }
            }
        }
        // Clean up
        $dir->close();
        return true;
    }
}