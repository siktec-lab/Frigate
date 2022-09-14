<?php declare(strict_types=1);

namespace Siktec\Frigate\Routing\Paths;

/**
 * PathBranch
 */
class PathBranch {

    public string $name;
    public bool $is_arg     = false;
    public string $arg_type = "string";
    public string $arg_name = "";
    public $exec            = null;
    public array $children  = [];
    
    /**
     * get_branch
     * traverse the path and return the branch that matches the path with the remaining path parts
     * @param array $path_parts
     * @return array[PathBranch, array]
     */
    public function get_branch(array $path_parts) : array {
        if (!empty($path_parts)) {
            $path_part = array_shift($path_parts);
            $name = self::branch_name($path_part);
            foreach ($this->children as $child) {
                if ($child->name === $name) {
                    return $child->get_branch($path_parts);
                }
            }
            array_unshift($path_parts, $path_part);
        }
        return [$this, $path_parts];
    }
    
    /**
     * eval_branch
     * evaluate the branch and return the result - will populate the defaults array
     * @param  array $path_parts
     * @param  array $defaults
     * @return array[PathBranch, array]
     */
    public function eval_branch(array $path_parts, array &$defaults = []) : array {
        $reminder = $path_parts;
        $branch   = $this;
        $limiter  = 25;
        while (!empty($reminder) && $limiter-- > 0) {
            [$branch, $reminder] = $branch->get_branch($reminder);
            if (!empty($reminder) && !empty($branch->children)) {
                foreach ($branch->children as $child) {
                    if ($child->is_arg) {
                        $parse = array_shift($reminder);
                        $value = self::arg_value($parse, $child->arg_type);
                        if (!is_null($value)) {
                            $branch = $child;
                            $defaults[$child->arg_name] = $value;
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
     * add_branch
     * adds a new branch to the this current branch
     * @param  array $path_parts
     * @param  mixed $exec
     * @return PathBranch
     */
    public function add_branch(array $path_parts, mixed $exec) : PathBranch {
        $branch = new PathBranch();
        $path_part = array_shift($path_parts);
        $name = self::branch_name($path_part);
        $branch->name = $name;
        $branch->is_arg = self::is_arg_name($name);
        if ($branch->is_arg) {
            [$branch->arg_name, $branch->arg_type] = self::arg_parts($path_part);
        }
        if ($this->has_arg_branch() && $branch->is_arg) {
            throw new \Exception("Cannot add a several argument branches to the same path");
        }
        $this->children[] = $branch;
        if (!empty($path_parts)) {
            return $branch->add_branch($path_parts, $exec);
        } else {
            $branch->exec = $exec;
        }
        return $branch;
    }
    
    /**
     * has_arg_branch
     * returns true if the branch has an argument branch
     * @return bool
     */
    public function has_arg_branch() : bool {
        foreach ($this->children as $child) {
            if ($child->is_arg) {
                return true;
            }
        }
        return false;
    }

    /**
     * print_tree
     * print the tree
     * @param  int $depth
     * @return void
     */
    public function print_tree(int $depth = 0) : void {
        $indent  = str_repeat(" ", $depth * 4);
        $flags   = [];
        $flags[] = $this->is_arg ? "A:".$this->arg_type : "P";
        $flags[] = $this->exec !== null ? "E" : "N";
        echo $indent . $this->name . " " . (implode(" ", $flags)) . PHP_EOL;
        foreach ($this->children as $child) {
            $child->print_tree($depth + 1);
        }
    }
    
    /**
     * is_arg_name
     * check if the name is an argument name
     * @param  string $name
     * @return bool
     */
    private static function is_arg_name(string $name) : bool {
        return str_starts_with($name, "{") && str_ends_with($name, "}");
    }

        
    /**
     * branch_name
     * get the branch name from the path part - only the name
     * @param  string $path_part
     * @return string
     */
    private static function branch_name(string $path_part) : string {
        return self::is_arg_name($path_part) ? "{".explode(":", trim($path_part, "{} "), 2)[0]."}" : $path_part;
    }

        
    /**
     * arg_parts
     * get the argument name and type from the path part
     * @param  mixed $path_part
     * @return array [string:name, string:type]
     */
    private static function arg_parts(string $path_part) : array {
        $parts = explode(":", trim($path_part, "{} "), 2);
        $arg = $parts[0];
        $type = in_array($parts[1] ?? "", ["string", "int", "float", "bool"]) ? $parts[1] : "string";
        return [$arg, $type];
    }
    
    /**
     * arg_value
     * safely parse the argument value
     * @param  string $arg
     * @param  string $type [string, int, float, bool]
     * @return mixed null if not valid
     */
    private static function arg_value(string $arg, string $type) : mixed {
        //"string", "int", "float", "bool"
        $output = $arg;
        switch ($type) {
            case "int" : {
                if (is_numeric($arg)) {
                    $output = intval($arg);
                } else {
                    $output = null;
                }
            } break;
            case "float" : {
                if (is_numeric($arg)) {
                    $output = floatval($arg);
                } else {
                    $output = null;
                }
            } break;
            case "bool" : {
                if (in_array(strtolower($arg), ['1', "true", "yes"], true)) {
                    $output = true;
                } elseif (in_array(strtolower($arg), ['0', "false", "no"], true)) {
                    $output = false;
                } else {
                    $output = null;
                }
            } break;
        }
        return $output;
    }

}
