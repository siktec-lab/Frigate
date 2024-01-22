<?php

namespace Frigate\Tools\FileSystem;


class FilesHelper {


    final public static function write_file(string $path, string $data) : bool {
        $handle = fopen($path, 'w');
        if ($handle === false) {
            return false;
        }
        $written = fwrite($handle, $data);
        fclose($handle);
        return $written ? true : false;
    }
    
    final public static function read_file_contents($filename) : ?string {
        $file = self::read_file($filename);
        if (is_null($file)) 
            return null;
        return $file['content'];
    }
    
    final public static function read_file(string $path) : ?array {
        $handle = fopen($path, 'r');
        if (!$handle) 
            return null;
        $content = fread($handle, filesize($path));
        fclose($handle);
        if (!$content) return null;
        return [
            'tmp_name'  => $path,
            'name'      => basename($path),
            'content'   => $content,
            'type'      => mime_content_type($path),
            'length'    => filesize($path),
            'error'     => 0
        ];
    }

    final public static function delete_files(...$files) : void {
        foreach ($files as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }

}