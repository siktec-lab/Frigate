<?php

namespace Siktec\Frigate\Tools\FileSystem;


class DirectoryHelper {

    public static function create_directory(string $path, int $permissions = 0755, $recursive = true) : bool {
        if (!is_dir($path)) {
            return mkdir($path, $permissions, $recursive);
        }
        return true;
    }
    
    public static function secure_directory(string $path) : bool {
        $content = '# Don\'t list directory contents
IndexIgnore *
# Disable script execution
AddHandler cgi-script .php .pl .jsp .asp .sh .cgi
Options -ExecCGI -Indexes';
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
    
    public static function create_secure_directory($path, int $permissions = 0755, $recursive = true) : bool {
        if (self::create_directory($path, $permissions, $recursive)) {
            return self::secure_directory($path);
        }
        return false;
    }

    public static function remove_directory($path) : void {
        //TODO add recursive option to this 
        // This currently will only directories with no subdirectories
        if (!is_dir($path)) {
            return;
        }
        $files = glob($path . DIRECTORY_SEPARATOR . '{.,}*', GLOB_BRACE);
        @array_map('unlink', $files);
        @rmdir($path);
    }

}