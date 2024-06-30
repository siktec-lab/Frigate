<?php

declare(strict_types=1);

namespace Frigate\Routing\Paths;

use Frigate\Routing\Paths\PathBranch;

/**
 * PathTree
 */
class PathTree { 

    /**
     * The operator to use for path optional variant macro:
    */
    public const PATH_MACRO = '?';
    
    /**
     * The operator to use for path separator:
    */
    public const PATH_SEPARATOR = '/';

    /**
     * The head branch of the tree
     */
    private PathBranch $head;
    
    /**
     * initialize the tree with the head branch with a path of "/"
     */
    public function __construct() 
    {
        $this->head = new PathBranch(from_path : self::PATH_SEPARATOR);
    }
    
    
    /**
     * define a new path in this tree
     *
     * @throws Frigate\Exceptions\FrigatePathException any path related error
     */
    public function define(string|array $path, object|array|string $exp) : void 
    {
        // if its a path macro:
        if (is_string($path) && str_contains($path, self::PATH_MACRO)) {
            $path = $this->expandPathMacro($path);
        }

        // if its an array of paths:
        if (is_array($path)) {
            foreach ($path as $p) {
                $this->define($p, $exp);
            }
            return;
        }

        // Process the string path:
        $parts = $this->pathPartsFrom($path);

        // define the paths:
        $current = $this->head;
        while (!empty($parts)) {

            // Walk the tree:
            [$current, $parts] = $current->getBranch($parts);

            //Its a shadow branch existing already so just add the expression:
            if (!empty($parts) && $current->shadow_mode) {
                $current->attachExpression($exp);
                continue;
            }
            
            // Add new branch:
            if (!empty($parts)) {
                $current->addBranch($parts[0]);
            }
        }

        // Attach the expression:
        $current->attachExpression($exp);
    }
    
    /**
     * remove special chars from a path
     */
    public static function removeSpecialChars(string $path) : string 
    {
        return str_replace(
            [
                self::PATH_MACRO, 
                PathBranch::TOKEN_SHADOW_BRANCH,
                PathBranch::ARG_NAME_START,
                PathBranch::ARG_NAME_END,
                PathBranch::ARG_NAME_TYPE_SEP,
            ], 
            "", 
            $path
        );
    }

    /**
     * get the branch of a given path
     * only finite paths are returned
     * a finite path is a path that has an expression 
     * shadow expressions paths are not returned directly
     */
    public function get(string $path) : ?PathBranch 
    {
        $parts = $this->pathPartsFrom(self::removeSpecialChars($path));
        [$branch, $parts] = $this->head->getBranch($parts);
        if (!empty($parts) || $branch->exp === null) {
            return null;
        }
        return $branch;
    }

    /**
     * evaluate a path and return the branch and the context of the path
     *
     * @return array{PathBranch|null,array<string,mixed>}
     */
    public function eval(string $path, array $default_context = []) : array 
    {
        $parts = $this->pathPartsFrom(self::removeSpecialChars($path));
        // Eval and also mutate the context:
        [$branch, $parts] = $this->head->evalBranch($parts, $default_context);
        if (!empty($parts) || $branch->exp === null) {
            return [null, $default_context];
        }
        return [$branch, $default_context];
    }

    /**
     * expand a path macro into all its variations
     *
     * @benchmark .\tests\Benchmarks\PathMacroExpandBench
     * @return array<string> variations of the path
     */
    private function expandPathMacro(string $path) : array 
    {
        $last = "";
        return array_map(function(string $slice) use (&$last) {
            return $last = $last . $slice;
        }, explode(self::PATH_MACRO, $path));
    }

    /**
     * get the path parts from a path string
     *
     * @return array<string> array of path parts
     */
    private function pathPartsFrom(string $path) : array 
    {
        return array_filter(array_map(function($v) {
            return trim(strtolower($v), " \n\t\r\0\x0B/\\");
        },  explode(self::PATH_SEPARATOR, $path)), 'strlen');
    }
 
    /**
     * __toString
     * Magic method to print the tree
     * @return string
     */
    public function __toString() : string 
    {
        $str = "";
        $this->head->describeBranch($str);
        return $str;
    }
}

