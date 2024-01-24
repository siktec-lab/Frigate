<?php

declare(strict_types=1);

namespace Frigate\Routing\Paths;

use Frigate\Exceptions\FrigatePathException;

/**
 * PathBranch
 * 
 * A branch in the path tree
 */
class PathBranch {

    // Max depth of the path tree
    public const MAX_DEPTH           = 55;

    // Argument name parts
    public const ARG_NAME_START      = "{";
    public const ARG_NAME_END        = "}";
    public const ARG_NAME_TYPE_SEP   = ":";
    
    // Argument types

    /** @var array<string> ARG_ALLOWED_TYPES */
    public const ARG_ALLOWED_TYPES = ["string", "int", "float", "bool", "path"];
    public const ARG_DEFAULT_TYPE = "string";
    
    // Argument values
    
    /** @var array<string> ARG_TRUE_VALUES */
    public const ARG_TRUE_VALUES = ["1", "true", "yes"];

    /** @var array<string> ARG_FALSE_VALUES */
    public const ARG_FALSE_VALUES = ["0", "false", "no"];

    /**
     * If its an argument branch this will be true
     */
    private bool $is_arg = false;
    
    /**
     * indicates if this branch is a stopage branch
     * which means that the path will not continue from here everythin after this branch will be ignored
     * and stored in the context always as a string
     */
    private bool $is_stopage = false;

    /**
     * If its an argument branch this will be the argument type
     */
    private string $arg_type = "string";

    /**
     * If its an argument branch this will be the argument name
     */
    private string $arg_name = "";

    /**
     * Attached executable:
     */
    public string|object|null $exec = null;

    /** 
     * @var PathBranch[] 
     */
    private array $children  = [];
    
    /**
     * initialize the branch
     */
    public function __construct(

        /**
         * The name of the branch
         * 
         * Will be the full name such as "test" or "{test:string}"
         */
        public string $name,

        /**
         * parent branch
         */
        public ?PathBranch $parent = null
    ) {
    }

    /**
     * traverse the path and return the branch that matches the path with the remaining path parts
     *
     * @param array<string|int> $path_parts
     * @return array{PathBranch,array<string|int>} current branch, reminder of path parts.
     */
    public function getBranch(array $path_parts) : array 
    {
        if (!empty($path_parts)) {
            $path_part = array_shift($path_parts);
            $name = self::branchName($path_part);
            foreach ($this->children as $child) {
                if ($child->name === $name) {
                    return $child->getBranch($path_parts);
                }
            }
            array_unshift($path_parts, $path_part);
        }
        return [$this, $path_parts];
    }
    
    /**
     * evaluate the branch and return the result - will populate the defaults array
     *
     * @param array<string|int> $path_parts - the path parts to evaluate
     * @param array{string,mixed} $context - will be populated with the default values
     * @return array{PathBranch,array<string|int>} returns branch, reminder of path parts.
     */
    public function evalBranch(array $path_parts, array &$context = []) : array 
    {
        $reminder = $path_parts;
        $branch   = $this;
        $limiter  = self::MAX_DEPTH; //TODO: test this max limiter
        while (!empty($reminder) && $limiter-- > 0) {
            [$branch, $reminder] = $branch->getBranch($reminder);
            if (!empty($reminder) && !empty($branch->children)) {
                foreach ($branch->children as $child) {
                    if ($child->is_arg) {
                        $parse = array_shift($reminder);
                        $value = self::argValue($parse, $child->arg_type, $reminder);
                        if (!is_null($value)) {
                            $branch = $child;
                            $context[$child->arg_name] = $value; 
                            continue 2;
                        } else {
                            array_unshift($reminder, $parse);
                            break 2;
                        }
                    }
                }
                break;
            } else {
                break;
            }
        }
        return [$branch, $reminder];
    }
    
    /**
     * construct a branch from a path and add it to this branch
     */
    public function addBranch(array $path_parts, string|object|null $exec) : PathBranch 
    {
        
        // A new branch:
        $path_part = array_shift($path_parts);
        $name = self::branchName($path_part);
        $branch = new PathBranch(name : $name, parent : $this);
        $branch->is_arg = self::isArgName($name);
        
        // Build the argument branch:
        if ($branch->is_arg) {
            [$branch->arg_name, $branch->arg_type] = self::argParts($path_part);
            $branch->is_stopage = $branch->arg_type === "path";
            if ($branch->is_stopage && !empty($path_parts)) {
                throw new FrigatePathException(
                    FrigatePathException::CODE_FRIGATE_EXTRA_PATH_AFTER_PATH_TYPE,
                    [$this->reConstructPath($path_part, ...$path_parts)]
                );
            }
        }

        // Check if the branch has an argument branch as a child
        if ($this->hasArgBranch() && $branch->is_arg) {
            // TODO: improve this error should be only if it the same type also..
            throw new FrigatePathException(
                FrigatePathException::CODE_FRIGATE_PATH_MULTIPLE_ARGS, 
                [$this->reConstructPath($path_part, ...$path_parts)]
            );
        }

        // Add the branch:
        $this->children[] = $branch;
        if (!empty($path_parts)) {
            return $branch->addBranch($path_parts, $exec);
        } else {
            $branch->exec = $exec;
        }

        return $branch;
    }

    /**
     * a string representation of the branch and its children
     */
    public function describeBranch(string &$str, int $depth = 0) : void 
    { 
        //TODO: this should be improved. 
        $flags   = [
            $this->is_arg ? "Arg:".$this->arg_type : "Path",
            $this->exec !== null ? "Exc" : "NExc",
        ];
        $str .= str_repeat(" ", $depth * 4) . 
                " [ " . $this->name . " ] -> " . 
                implode(" ", $flags) . PHP_EOL;
        foreach ($this->children as $child) {
            $child->describeBranch($str, $depth + 1);
        }
    }

    /**
     * reconstruct the path from this branch to the root
     */
    private function reConstructPath(...$append) : string 
    {
        $append = implode(PathTree::PATH_SEPARATOR, array_map(fn($p) => trim($p, "/ \t\n\r\0\x0B"), $append));
        if ($this->parent !== null) {
            $append = $this->name . (!empty($append) ? PathTree::PATH_SEPARATOR . $append : "");
            return $this->parent->reConstructPath($append);
        }
        return $append;
    }

    /**
     * returns true if the branch has an argument branch as a child
     */
    private function hasArgBranch() : bool 
    {
        foreach ($this->children as $child) {
            if ($child->is_arg) {
                return true;
            }
        }
        return false;
    }

    /**
     * check if the name is an argument name
     */
    private static function isArgName(string $name) : bool {
        return str_starts_with($name, self::ARG_NAME_START) && str_ends_with($name, self::ARG_NAME_END);
    }

        
    /**
     * get the branch name from the path part - only the name
     */
    private static function branchName(string $path_part) : string 
    {
        $name = $path_part;
        if (self::isArgName($path_part)) {
            $name = explode(
                self::ARG_NAME_TYPE_SEP, 
                trim($path_part, self::ARG_NAME_START.self::ARG_NAME_END." "), 
                2
            )[0];
            $name = self::ARG_NAME_START.$name.self::ARG_NAME_END;
        }
        return $name;
    }

        
    /**
     * get the argument name and type from the path part
     *
     * @return array{string,string} the argument name and type
     */
    private static function argParts(string $path_part) : array 
    {
        $parts = explode(
            self::ARG_NAME_TYPE_SEP, 
            trim($path_part, self::ARG_NAME_START.self::ARG_NAME_END." "), 
            2
        );
        $arg = $parts[0];
        $type = in_array($parts[1] ?? "", self::ARG_ALLOWED_TYPES) ? $parts[1] : self::ARG_DEFAULT_TYPE;
        return [$arg, $type];
    }
    
    /**
     * safely parse the argument value
     * 
     * returns null if the value is not valid
     */
    private static function argValue(string $arg, string $type, array &$reminder) : mixed 
    {
        $output = match ($type) {
            "int"   => is_numeric($arg) ? intval($arg) : null,
            "float" => is_numeric($arg) ? floatval($arg) : null,
            "bool"  => in_array(strtolower($arg), self::ARG_TRUE_VALUES, true) 
                        ? true 
                        : (in_array(strtolower($arg), self::ARG_FALSE_VALUES, true) ? false : null),
            "path"  => implode(PathTree::PATH_SEPARATOR, array_merge([$arg], $reminder)),
            default => $arg,
        };
        // Reset the reminder if its a "stopage" branch:
        if ($type === "path") {
            $reminder = [];
        }
        
        return $output;

    }

}
