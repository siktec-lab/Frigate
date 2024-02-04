<?php

declare(strict_types=1);

namespace Frigate\Tools\Paths;

class PathHelpers {
    
    /**
     * implodes an array to os based path
     * @param  array<string> ...$path
     */
    final public static function join(...$parts) : string {
        // remove empty strings and nulls
        $parts = array_filter($parts, 
            fn($v) => !empty($v) && is_string($v) && $v !== DIRECTORY_SEPARATOR 
        );
        return implode(DIRECTORY_SEPARATOR, $parts);
    }
    
    /**
     * implodes an array to os based path
     * 
     * alias for join
     */
    final public static function path(...$parts) : string {
        return self::join(...$parts);
    }

    /**
     * implodes an array to a url path URI
     * @param  array<string> ...$path
     */
    final public static function uri(...$parts) : string {
        // remove empty strings and nulls
        $parts = array_filter($parts, 
            fn($v) => !empty($v) && is_string($v) && $v !== '/' && $v !== '\\'
        );
        return implode('/', $parts);
    }
        
    /**
     * path_exists
     * checks wether a file exists with a simple path
     * @param  array ...$path_to_file - the path to the file packed as an array
     * @return string|bool - returns the path if exists or false if not
     */
    final public static function pathExists(...$parts) : string|bool {
        $path = implode(DIRECTORY_SEPARATOR, array_map(
            function($part){
                return trim($part, " \t\n\r\\/");
            },
            $parts
        ));
        return file_exists($path) ? $path : false;
    }

}