<?php

namespace Frigate\Tools\Arrays;

class ArrayHelpers {
    
    /**
     * is_associative_array
     * Checks if an array is associative or not
     * @param  mixed $arr array to check
     * @return bool true if associative, false if not
     */
    public static function is_associative_array(mixed $arr) : bool {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
        
    /**
     * to_array
     * Converts a value to an array
     * @param  mixed $value
     * @param  bool $empty_on_error if true, returns an empty array if the value cannot be converted to an array
     * @return array array if successful, otherwise an empty array
     * @throws \Exception if $empty_on_error is false and the value cannot be converted to an array
     */
    public static function to_array(mixed $value, bool $empty_on_error = true) : array {
        // If the value is already an array, return it
        if (is_array($value)) {
            return $value;
        }
        // If its an object, convert it to an array:
        if (is_object($value)) {
            return self::stdClass_to_array($value);
        }
        // If its a string, convert it to an array assume it is a comma separated list:
        if (is_string($value)) {
            $exploded = explode(",", $value);
            //trim each value
            return array_map(function($val) {
                return trim($val);
            }, $exploded);
        }
        // If its a number, convert it to an array with the number as the only value:
        if (is_numeric($value)) {
            return [$value];
        }
        // Return an empty array:
        if ($empty_on_error) {
            return [];
        }
        //throw an error:
        throw new \Exception("Could not convert value to array");
    }
    
    /**
     * stdClass_to_array
     * Converts a stdClass object to an array recursively
     * 
     * @param  mixed $obj stdClass object or array of stdClass objects
     * @return mixed array if successful, otherwise the value passed in
     */
    public static function stdClass_to_array(mixed $obj) : mixed {
        if (is_object($obj)) {
            $obj = get_object_vars($obj);
        }
        if (is_array($obj)) {
            return array_map(__METHOD__, $obj);
        }
        return $obj;
    }

}