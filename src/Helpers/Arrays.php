<?php

declare(strict_types=1);

namespace Frigate\Helpers;

class Arrays 
{    
    /**
     * Checks if an array is associative or not
     *
     * @param  mixed $arr array to check
     */
    public static function isAssoc(mixed $arr) : bool 
    {
        return !array_is_list($arr);
    }

    /**
     * Checks if an array is a list or not
     *
     * @param  mixed $arr array to check
     */
    public static function isList(mixed $arr) : bool 
    {
        return array_is_list($arr);
    }
        
    /**
     * Converts a value to an array
     *
     * Unlike (array) $value, this method will convert objects to arrays and strings to arrays treating them as 
     * comma separated lists
     *
     * @param  mixed $value
     * @param  bool $empty_on_error if true, returns an empty array if the value cannot be converted to an array
     * @return array array if successful, otherwise an empty array
     * @throws \Exception if $empty_on_error is false and the value cannot be converted to an array
     */
    public static function toArray(mixed $value, bool $empty_on_error = true) : array 
    {
        // If the value is already an array, return it
        if (is_array($value)) {
            return $value;
        }
        // If its an object, convert it to an array:
        if (is_object($value)) {
            return self::stdClassToArray($value);
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
        //TODO: Add a custom exception class
        throw new \Exception("Could not convert value to array");
    }
    
    /**
     * Converts a stdClass object to an array recursively
     * 
     * @param  mixed $obj stdClass object or array of stdClass objects
     * @return mixed array if successful, otherwise the value passed in
     */
    public static function stdClassToArray(mixed $obj) : mixed 
    {
        if (is_object($obj)) {
            $obj = get_object_vars($obj);
        }
        if (is_array($obj)) {
            return array_map(__METHOD__, $obj);
        }
        return $obj;
    }

    /**
     * Flips the keys and values of an array, preserving duplicate values
     * Unlike array_flip, this method will return an array of arrays without overwriting duplicate values
     *
     * @param  array $arr the array to flip
     * @return array<int|string,array<int|string>> the flipped array
     */
    public static function arrayFlipPreserve(array $arr) : array
    {
        return array_reduce(array_keys($arr), function ($carry, $key) use (&$arr) {
            $carry[$arr[$key]] ??= [];
            $carry[$arr[$key]][] = $key;
            return $carry;
        }, []);
    }
}