<?php 

namespace Siktec\Frigate\Routing;

use \Closure;
use \Siktec\Frigate\Routing\Http;

class Route {

    public string   $path;
    public array    $context = [];
    private array    $returns = [
        "text/html",
        "application/json"
    ];
    public $func;

    public function __construct(string $path, array $context = [], array $returns = [], $func = null) {
        $this->path = trim($path, "\t\n\r /\\");
        $this->context = $context;
        $this->func = $func;
        if (!empty($returns)) {
            $this->set_returns(...$returns);
        }
    }

        
    /**
     * returns
     * set return types for this route which are the accepted content types
     * @param  string* $returns
     * @return Route
     */
    public function set_returns(...$returns) : Route {
        $returns = array_map("trim", $returns);
        $returns = array_map("strtolower", $returns);
        $this->returns = $returns;
        return $this;
    }
    
    /**
     * supports_accept
     * checks if requested content type is supported in the route 
     * @param  string $accept
     * @return bool
     */
    public function supports_accept(string $accept) : bool {
        return in_array($accept, $this->returns);
    }
    
    //method that return the first supported content type
    public function get_default_return() : string {
        return $this->returns[0] ?? "text/plain";
    }

    public function exec(...$args) : Http\Response {
        if (is_object($this->func) &&  !$this->func instanceof Closure && method_exists($this->func, "call")) {
            return $this->func->call($this->context, ...$args);
        }
        return call_user_func_array(
            $this->func, 
            [$this->context, ...$args]
        );
    }

    public function __toString() : string
    {
        $con = [];
        foreach ($this->context as $k => $v) {
            $con[] = "$k::$v";
        }
        return sprintf("Route '%s', Context %s", $this->path, $con ? implode(",", $con) : "empty");
    }

}