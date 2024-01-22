<?php declare(strict_types=1);

namespace Frigate\Routing\Paths;

use Frigate\Routing\Paths\PathBranch;

/**
 * PathTree
 */
class PathTree { 

    private PathBranch $head;
    
    /**
     * __construct
     * initialize the tree with the head branch with a path of "/"
     * @return void
     */
    public function __construct() {
        $this->head = new PathBranch();
        $this->head->name = "/";
    }
    
    /**
     * define
     * define a new path int the tree
     * @param  string $path
     * @param  mixed $exec
     * @throws Exception when the path allready exists
     * @return void
     */
    public function define(string $path, mixed $exec) : void {
        $parts = $this->path_parts($path);
        //Walk the tree:
        [$branch, $parts] = $this->head->get_branch($parts);
        //if its allready registered throw an error:
        if (empty($parts) && $branch->exec !== null) {
            throw new \Exception("Path already registered");
        }
        //Register the new branch:
        if (empty($parts)) {
            $branch->exec = $exec;
        }
        //Register the new branch:
        if (!empty($parts)) {
            $branch->add_branch($parts, $exec);
        }
    }
    
    /**
     * get
     * get the branch for of a given path
     * @param  string $path
     * @return PathBranch|null null if not found
     */
    public function get(string $path) : ?PathBranch {
        //Walk the tree:
        $parts = $this->path_parts($path);
        [$branch, $parts] = $this->head->get_branch($parts);
        if (!empty($parts) || $branch->exec === null) {
            return null;
        }
        return $branch;
    }

    /**
     * eval
     * evaluate a path and return the branch and the arguments/context
     * @param  string $path
     * @param  array $default_context
     * @return array[PathBranch|null, array] null if not found
     */
    public function eval(string $path, array $default_context = []) : array {
        $parts = $this->path_parts($path);
        $parts = array_map(function($v){ return trim($v, " {}"); }, $parts);
        $context = $default_context;
        [$branch, $parts] = $this->head->eval_branch($parts, $context);
        if (!empty($parts) || $branch->exec === null) {
            return [null, $context];
        }
        return [$branch, $context];
    }

        
    /**
     * path_parts
     * get the path parts from a path string
     * @param  string $path
     * @return array
     */
    private function path_parts(string $path) : array {
        $path = trim($path, " \n\t\r\0\x0B/\\");
        $parts = explode("/", $path);
        //normalize the parts:
        $parts = array_map(function($v) {
            $v = trim($v, " \n\t\r\0\x0B/\\");
            $v = strtolower($v);
            return $v;
        }, $parts);
        //remove empty parts:
        $parts = array_filter($parts, function($part) {
            return !empty($part);
        });
        return $parts;
    }
 
    /**
     * __toString
     * Magic method to print the tree
     * @return string
     */
    public function __toString() : string {
        $this->head->print_tree();
        return "";
    }
}

