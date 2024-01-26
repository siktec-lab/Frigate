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

    // Shadow Branch token
    public const TOKEN_SHADOW_BRANCH = '^';

    // Argument name parts
    public const ARG_NAME_START      = '{';
    public const ARG_NAME_END        = '}';
    public const ARG_NAME_TYPE_SEP   = ':';
    
    // Argument types

    /** @var array<string> ARG_ALLOWED_TYPES */
    public const ARG_ALLOWED_TYPES = [
        "string", "str",
        "int", "integer",
        "float", "double",
        "bool", "boolean",
        "path"
    ];
    public const ARG_DEFAULT_TYPE = "string";
    
    // Argument values
    
    /** @var array<string> ARG_TRUE_VALUES */
    public const ARG_TRUE_VALUES = ["1", "true", "yes", "on"];

    /** @var array<string> ARG_FALSE_VALUES */
    public const ARG_FALSE_VALUES = ["0", "false", "no", "off"];

    /**
     * The name of the branch
     * 
     * Will be the full name such as "test" or "{test}"
     */
    public string $name;
    
    /**
     * Attached expression:
     */
    public object|array|string|null $exp = null;

    /**
     * If its an argument branch this will be true
     */
    public bool $is_arg = false;
    
    /**
     * indicates if this branch is a stopage branch
     * which means that the path will not continue from here everythin after this branch will be ignored
     * and stored in the context always as a string
     */
    public bool $is_stopage = false;

    /**
     * If its an argument branch this will be the argument type
     */
    public string $arg_type = self::ARG_DEFAULT_TYPE;

    /**
     * If its an argument branch this will be the argument name
     */
    public string $arg_name = "";
    
    /**
     * When getting the branch it can be in shadow mode
     * this flag will indicate that
     */
    public bool $shadow_mode = false;

    /** 
     * Attached shadow expression:
     *
     * @var array<object|array|string>
     */
    private array $shadow_exp = [];

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
         * Will be the full name such as "test" or "{test}"
         */
        string $from_path = "/",
        /**
         * parent branch
         */
        public ?PathBranch $parent = null, 
    ) {
        //Build the branch:
        [
            $this->name, 
            $this->is_arg, 
            $this->arg_name, 
            $this->arg_type, 
            $this->is_stopage, 
            $this->shadow_mode
        ] = self::branchNameFeatures($from_path);
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
            $is_shadow = str_ends_with($path_part, self::TOKEN_SHADOW_BRANCH);
            // Edge case its a self shadow branch:
            if ($is_shadow && $path_part === self::TOKEN_SHADOW_BRANCH) {
                $this->shadow_mode = true;
                return [$this, $path_parts];
            }
            // Normalize the name:
            $name = self::normalizeBranchName($path_part);
            foreach ($this->children as $child) {
                if ($child->name === $name) {
                    $child->shadow_mode = $is_shadow;
                    return $child->shadow_mode ?  
                           [$child, $path_parts] : // if its a shadow branch return it
                           $child->getBranch($path_parts);
                }
            }
            // We unshift because we could not find the branch so we need to put it back
            array_unshift($path_parts, $path_part);
        }
        return [$this, $path_parts];
    }
    
    /**
     * evaluate the branch and return the result - will populate the defaults array
     *
     * @param array<string> $path_parts - the path parts to evaluate
     * @param array<string,mixed> $context - will be populated with the default values
     * @return array{PathBranch,array<string>} returns branch, reminder of path parts.
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
    public function addBranch(string $path_define) : PathBranch 
    {
        // A new branch:
        $branch = new PathBranch(
            from_path : $path_define, 
            parent : $this
        );
        // If we are extending a stopage branch: 
        if ($this->is_stopage) {
            throw new FrigatePathException(
                FrigatePathException::CODE_FRIGATE_EXTRA_PATH_AFTER_PATH_TYPE,
                [$this->reConstructPath($path_define)]
            );
        }
        // Add the branch:
        $this->addChild($branch);

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
            $this->shadow_exp ? "Shadow(" . count($this->shadow_exp) . ")" : "",
            $this->exp !== null ? "Exc" : "",
            $this->is_stopage ? "Stop" : "",
        ];
        $flags = array_filter($flags);
        $str .= str_repeat(" ", $depth * 4) . 
                " [ " . $this->name . " ] -> " . 
                implode(" ", $flags) . PHP_EOL;
        foreach ($this->children as $child) {
            $child->describeBranch($str, $depth + 1);
        }
    }

    /**
     * check if the branch has shadow expressions
     */
    public function hasShadowExpression() : bool 
    {
        return !empty($this->shadow_exp);
    }

    /**
     * get the shadow expressions
     * 
     * If its a expression branch it will return all the shadow expressions
     * from the parent branches also
     *
     * @return array<object|array|string>
     */
    public function getShadowExpressions(bool $only_mine = false) : array 
    {
        $expressions = [];
        if ($only_mine) {
            $expressions = $this->shadow_exp;
        } else {
            if ($this->exp) {
                $parents = [];
                $parent = $this->parent;
                while ($parent !== null) {
                    if ($parent->hasShadowExpression()) {
                        $parents[]  = $parent;
                    }
                    $parent = $parent->parent;
                }
                // Loop reverse to get the shadow expressions from the top to the bottom:
                foreach (array_reverse($parents) as $parent) {
                    array_push($expressions, ...$parent->getShadowExpressions(true));
                }
            }
            array_push($expressions, ...$this->shadow_exp);
        }
        return $expressions;
    }

    /**
     * add a shadow expression to the branch
     */
    public function attachExpression(object|array|string|null $exp, bool|null $shadow = null) : void 
    {
        if (is_null($exp)) {
            return;
        }
        // If its a shadow branch:
        $shadow = $shadow ?? $this->shadow_mode;
        if ($shadow) {
            $this->shadow_exp[] = $exp;
            $this->shadow_mode = false;
        } else {
            $this->exp = $exp;
        }
    }

    /**
     * add a child branch to this branch
     */
    private function addChild(PathBranch $child) : void 
    {
        // Check if the branch has an argument branch as a child
        if ($child->is_arg && $this->childrenState(has_arg: true)) {
            throw new FrigatePathException(
                FrigatePathException::CODE_FRIGATE_PATH_MULTIPLE_ARGS, 
                [$this->reConstructPath($child->name)]
            );
        }
        // Add the branch:
        $this->children[] = $child;
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
     * returns true if the branch has a specific feature branch as a child
     */
    private function childrenState(
        bool $has_arg = false, 
        bool $has_stopage = false, 
        bool $cannot_have_stopage = false
    ) : null|PathBranch
    {
        foreach ($this->children as $child) {
            // Check if arg branch exists
            if ($has_arg && $child->is_arg) {
                return $child;
            }
            // Check if stopage branch exists
            if ($has_stopage && $child->is_stopage) {
                return $child;
            }
            // stopage branch cannot exist with other branches
            if ($cannot_have_stopage /* && ($child->is_arg || $child->is_stopage) */) { // TODO: later middleware
                return $child;
            }
        }
        return null;
    }

    /**
     * check if the name is an argument name
     */
    private static function isArgName(string $name) : bool 
    {
        return str_starts_with($name, self::ARG_NAME_START) && str_ends_with($name, self::ARG_NAME_END);
    }

    
    private static function branchNameFeatures(string $definition_name) : array 
    {
        $name           = $definition_name;
        $with_shadow    = false;
        $is_arg         = false;
        $arg_type       = self::ARG_DEFAULT_TYPE;
        $arg_name       = "";
        $is_stopage     = false;
        // Has shadow branch:
        if (str_ends_with($name, self::TOKEN_SHADOW_BRANCH)) {
            $name = rtrim($name, self::TOKEN_SHADOW_BRANCH);
            $with_shadow = true;
        }
        // Is argument branch:
        if (self::isArgName($name)) {
            $is_arg = true;
            [$arg_name, $arg_type] = self::argParts($name);
            $is_stopage = $arg_type === "path";
        }
        // Normalize the name:
        $name = self::normalizeBranchName($name);
        // Return the features:
        return [$name, $is_arg, $arg_name, $arg_type, $is_stopage, $with_shadow];
    }

    /**
     * get the branch name from the path part - only the name
     */
    private static function normalizeBranchName(string $path_part) : string 
    {   
        // Remove white spaces and special chars:
        $name = str_replace(
            [
                " ", "\t", "\n", "\r", "\0", "\x0B", 
                self::TOKEN_SHADOW_BRANCH, 
                PathTree::PATH_SEPARATOR, 
                PathTree::PATH_MACRO
            ], 
            "", 
            $path_part
        );
        // Remove argument types:
        if (self::isArgName($name)) {
            $name = explode(
                self::ARG_NAME_TYPE_SEP, 
                trim($name, self::ARG_NAME_START.self::ARG_NAME_END), 
                2
            )[0];
            $name = self::ARG_NAME_START.$name.self::ARG_NAME_END;
        }
        return !empty($name) ? $name : "/";
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
            "int", "integer"  => is_numeric($arg) ? intval($arg) : null,
            "float", "double" => is_numeric($arg) ? floatval($arg) : null,
            "bool", "boolean" => in_array(strtolower($arg), self::ARG_TRUE_VALUES, true) 
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
