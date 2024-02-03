<?php

declare(strict_types=1);

namespace Frigate\Tools\Json;

use Frigate\Tools\Strings\StringHelpers as Str;

class JsonHelpers {
    
    /**
     * validates a json string by safely parsing it
     */
    final public static function isJson(...$args) : bool 
    {
        json_decode(...$args);
        return (json_last_error() === JSON_ERROR_NONE);
    }
        
    /**
     * safely try to parse json.
     */
    final public static function parseJson(
        string $json, 
        mixed $onerror = false, 
        bool $assoc = true
    ) : mixed {
        return json_decode($json, $assoc) ?? $onerror;
    }

    /**
     * safely try to parse jsonc (json with comments).
     */
    final public static function parseJsonc(
        string $jsonc, 
        bool $remove_bom = true, 
        mixed $onerror   = false, 
        bool $assoc      = true
    ) : mixed {
        $json = trim(
            Str::stripComments($jsonc), 
            $remove_bom ? "\xEF\xBB\xBF \t\n\r\0\x0B" : " \t\n\r\0\x0B"
        );
        return json_decode($json, $assoc) ?? $onerror;
    }

}