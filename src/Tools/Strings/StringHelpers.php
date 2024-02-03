<?php

declare(strict_types=1);

namespace Frigate\Tools\Strings;

class StringHelpers {
    
    /** Regex patterns */
    public static $regex = [
        "filter-none" => '~[^%s]~',
        "version"     => '/^(\d+\.)?(\d+\.)?(\*|\d+)$/'
    ];

    /**
     * Escape a string for html output
    */
    final public static function escape(string $str) : string 
    {
        // Modern more safe string escape for html utf-8 safe strings:
        return htmlentities($str, ENT_QUOTES | ENT_HTML5, "UTF-8");
    }

    /**
     * fileters a string by removing all characters that are not in the allowed list
     * 
     * This will auto build a regex based on the allowed characters
     */
    final public static function filterString(string $str, string|array $allowed = ["A-Z","a-z","0-9"]) : string 
    {
        $regex = is_string($allowed) ? 
            sprintf(self::$regex["filter-none"], $allowed) :
            sprintf(self::$regex["filter-none"], implode($allowed));
        return preg_replace($regex, '', $str);
    }
    
    /**
     * checks if a string is a valid version number D.D.D
     */
    final public static function isVersion(string $version) : bool 
    {
        return preg_match(self::$regex["version"], $version);
    }
    
    /**
     * compare versions
     * 
     * More: https://www.php.net/manual/en/function.version-compare.php
     * returns -1 if the first version is lower than the second, 0 if they are equal, and 1 if the second is lower.
     * When using the optional operator argument, the function will return true if the relationship is the one 
     * specified by the operator, false otherwise.
     * 
     * @param  mixed $version
     * @param  mixed $against
     * @param  mixed $condition - <, lt, <=, le, >, gt, >=, ge, ==, =, eq, !=, <>
     * @return bool|int By default, version_compare returns -1 if the first version is lower than the 
     *                  second, 0 if they are equal, and 1 if the second is lower.
     */
    final public static function validateVersion(
        string $version, 
        string $against, 
        ?string $condition = null
    ) : bool|int {
        return version_compare(
            trim($version), 
            trim($against), 
            trim($condition)
        );
    }

    /**
     * Remove comments from strings
     * 
     * E.g: // comment, /* comment * /
     * usefull for jsonc (json with comments)
     *
	 * From https://stackoverflow.com/a/31907095/2510785
	 */
	public static function stripComments(string $str = '' ) : string 
    {
        return preg_replace(
            '/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\'|\")\/\/.*))/', '', $str
        ) ?? "";
	}
}