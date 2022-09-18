<?php

namespace Siktec\Frigate\Tools\Arrays;

class ArrayHelpers {

    public static function is_associative_array($arr) {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
    
    public static function to_array($value) {
        if (is_array($value) && !self::is_associative_array($value)) {
            return $value;
        }
        return isset($value) ? array($value) : array();
    }

}