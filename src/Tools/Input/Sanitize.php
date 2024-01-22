<?php

namespace Frigate\Tools\Input;


class Sanitize {
    
    /**
     * text
     * sanitizes a string only english letters, numbers and common special characters are allowed
     * @param  string $str
     * @return string
     */
    public static function text(string $str) : string {
        $str = preg_replace('~[^ A-Za-z0-9\\\\/_\\-:?,.!@\\(\\)\\[\\]\']"~','', trim($str));
        return preg_replace('~\s+~',' ',$str);
    }
    
    /**
     * text_utf8
     * sanitize text input for utf8 characters
     * @param  string $str
     * @return string
     */
    public static function text_utf8(string $str) : string {
        $str = preg_replace('~[^\p{L}\s0-9\\\\/_\\-:?,.!@\\(\\)\\[\\]\']+~u','', trim($str));
        return preg_replace('~\s+~',' ',$str);
    }
    
    public static function chars(string $str, $allowed = "a-zA-Z") : string {
        return preg_replace('~[^'.$allowed.']~','',$str);
    }

    public static function phone(string $str) : string {
        return preg_replace('~[^0-9+\\-]~','',$str);
    }

    public static function email(string $str) : string {
        return preg_replace('~[^0-9a-z_\\-@.]~','', strtolower($str));
    }

    public static function numbers_list(string|array $list, string $delim = ",") : array {
        return array_filter(array_map(
            fn($i) => filter_var($i, FILTER_VALIDATE_INT), 
            is_string($list) ? explode($delim, $list) : $list
        ));
    }

    public static function tags_list(string|array $list, string $delim = ",") : array {
        return array_filter(array_map(
            fn($s) => self::chars((string)$s, "a-zA-Z0-9_\-,"), 
            is_string($list) ? explode($delim, $list) : $list
        ));
    }

    public static function integer(mixed $int, mixed $onerror = 0) : mixed {
        $_int = filter_var($int, FILTER_VALIDATE_INT);
        return $_int !== false ? $_int : $onerror;
    }

    public static function float(mixed $float, mixed $onerror = 0.0) : mixed {
        $_float = filter_var($float, FILTER_VALIDATE_FLOAT);
        return $_float !== false ? $_float : $onerror;
    }

    public static function boolean(mixed $bool, mixed $onerror = false) : mixed {
        return filter_var($bool, FILTER_VALIDATE_BOOLEAN);
    }

    public static function filter_empty(array $arr, array $skip = []) : array {
        return array_filter($arr, 
            fn($el) => !(empty($el) && !in_array($el, $skip, true))
        );
    }

    public static function filter_keys(array $arr, array $allowed_keys = []) : array {
        return array_filter($arr, fn($k) => in_array($k, $allowed_keys, true), ARRAY_FILTER_USE_KEY);
    }

    public static function array_defaults(array $default, array $input) : array {
        $input = self::filter_keys($input, array_keys($default));
        return array_merge($default, $input);
    }

    public static function array_value(string|int $key, array $arr, mixed $default = null) : mixed {
        return array_key_exists($key, $arr) ? $arr[$key] : $default;
    }
    
    /**
     * trim_length
     * utf-8 safe string trim with optional ellipsis
     * for utf-8 strings, mb_strlen and mb_substr are used if they are available
     * 
     * @param  mixed $str
     * @param  mixed $length
     * @param  mixed $hyphens
     * @return string
     */
    public static function trim_length(string $str, int $length, string $ellipsis = '') : string {

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