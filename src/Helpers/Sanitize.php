<?php

declare(strict_types=1);

namespace Frigate\Helpers;

class Sanitize 
{    
    /**
     * filter a string only english letters, numbers and common special characters are allowed
     */
    public static function text(string $str) : string
    {
        $str = preg_replace('~[^ A-Za-z0-9\\\\/_\\-:?,.!@\\(\\)\\[\\]\']"~','', trim($str));
        return preg_replace('~\s+~',' ',$str);
    }
    
    /**
     * filter text input for utf8 characters
     */
    public static function textUtf8(string $str) : string
    {
        $str = preg_replace('~[^\p{L}\s0-9\\\\/_\\-:?,.!@\\(\\)\\[\\]\']+~u','', trim($str));
        return preg_replace('~\s+~',' ',$str);
    }
    
    /**
     * filter to only allow letters which is a RegExp like pattern
     * e.g. "a-zA-Z" or "a-zA-Z0-9"
     */
    public static function chars(string $str, $allowed = "a-zA-Z") : string
    {
        return preg_replace('~[^'.$allowed.']~','',$str);
    }

    /**
     * filter out all non phone number characters
     * only numbers, plus and hyphen are allowed
     */
    public static function phone(string $str) : string
    {
        return preg_replace('~[^0-9+\\-]~','',$str);
    }

    /**
     * filter out all non email characters
     * only numbers, letters, hyphen, underscore, at and dot are allowed
     */
    public static function email(string $str) : string
    {
        return preg_replace('~[^0-9a-z_\\-@.]~','', strtolower($str));
    }

    /**
     * convert a string to a list of integers
     * e.g. "1,2,3,4,5" => [1,2,3,4,5]
     */
    public static function numbersList(string|array $list, string $delim = ",") : array
    {
        return array_filter(array_map(
            fn($i) => filter_var($i, FILTER_VALIDATE_INT), 
            is_string($list) ? explode($delim, $list) : $list
        ));
    }

    /**
     * convert a string to a list of tags
     * e.g. "tag1,tag2,tag3" => ["tag1","tag2","tag3"]
     */
    public static function tagsList(string|array $list, string $delim = ",") : array
    {
        return array_filter(array_map(
            fn($s) => self::chars((string)$s, "a-zA-Z0-9_\-,"), 
            is_string($list) ? explode($delim, $list) : $list
        ));
    }

    /**
     * filter an integer
     * if the input is not an integer, the $onerror value will be returned
     */
    public static function integer(mixed $int, mixed $onerror = 0) : mixed
    {
        $_int = filter_var($int, FILTER_VALIDATE_INT);
        return $_int !== false ? $_int : $onerror;
    }

    /**
     * filter a float
     * if the input is not a float, the $onerror value will be returned
     */
    public static function float(mixed $float, mixed $onerror = 0.0) : mixed
    {
        $_float = filter_var($float, FILTER_VALIDATE_FLOAT);
        return $_float !== false ? $_float : $onerror;
    }

    /**
     * filter a boolean
     * if the input is not a boolean, the $onerror value will be returned
     */
    public static function boolean(mixed $bool, mixed $onerror = false) : mixed
    {
        return filter_var($bool, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * remove all empty values from an array
     * if $skip is provided, those values will not be removed
     * e.g. filter_empty(["a", "", "b", "c", null], [null]) => ["a", "b", "c", null]
     */
    public static function filterEmpty(array $arr, array $skip = []) : array
    {
        return array_filter($arr, 
            fn($el) => !(empty($el) && !in_array($el, $skip, true))
        );
    }

    /**
     * filter an array to only allow the keys in the $allowed_keys array
     * e.g. filter_keys(["a" => 1, "b" => 2, "c" => 3], ["a", "c"]) => ["a" => 1, "c" => 3]
     */
    public static function filterKeys(array $arr, array $allowed_keys = []) : array
    {
        return array_filter($arr, fn($k) => in_array($k, $allowed_keys, true), ARRAY_FILTER_USE_KEY);
    }

    /**
     * extend a default array with the input array overwriting the default values
     * only the keys in the default array will be used
     */
    public static function arrayDefaults(array $default, array $input) : array
    {
        $input = self::filterKeys($input, array_keys($default));
        return array_merge($default, $input);
    }

    /**
     * get a value from an array or return a default value
     */
    public static function arrayValue(string|int $key, array $arr, mixed $default = null) : mixed
    {
        return array_key_exists($key, $arr) ? $arr[$key] : $default;
    }
    
    /**
     * utf-8 safe string trim with optional ellipsis
     * for utf-8 strings, mb_strlen and mb_substr are used if they are available
     */
    public static function trimLength(string $str, int $length, string $ellipsis = '') : string
    {
        //First trim:
        $str = trim($str);
        //Check if string is longer than $length:
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            $limit = $length - mb_strlen($ellipsis);
            if (mb_strlen($str) > $limit) {
                return mb_substr($str, 0, $limit) . $ellipsis;
            }
        } else {
            $limit = $length - strlen($ellipsis);
            if (strlen($str) > $limit) {
                return substr($str, 0, $limit) . $ellipsis;
            }
        }
        return $str;
    }
}