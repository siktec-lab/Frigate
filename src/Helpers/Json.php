<?php

declare(strict_types=1);

namespace Frigate\Helpers;

use Frigate\Helpers\Strings;

class Json 
{
    
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
            Strings::stripComments($jsonc), 
            $remove_bom ? "\xEF\xBB\xBF \t\n\r\0\x0B" : " \t\n\r\0\x0B"
        );
        return json_decode($json, $assoc) ?? $onerror;
    }

}