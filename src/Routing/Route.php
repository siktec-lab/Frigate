<?php 

namespace Siktec\Frigate\Routing;

use \Closure;
use \Siktec\Frigate\Routing\Http;
use \Siktec\Frigate\Api\BindEndpoint;

class Route {

    public string    $path;
    public array     $context = [];
    private array    $returns = [
        "text/html",
        "application/json"
    ];
    public Http\HTTP2    $http2;
    public $func;
    protected ?\ReflectionClass $request_mutator = null;
    
    public function __construct(string $path, array $context = [], array $returns = [], $func = null, \ReflectionClass|string|null $request_mutator = null) {
        $this->path = trim($path, "\t\n\r /\\");
        $this->context = $context;
        $this->http2 = new Http\HTTP2();
        $this->func = $func;
        if (!empty($returns)) {
            $this->set_returns(...$returns);
        }
        // set the request mutator:
        if (is_string($request_mutator)) {
            $this->request_mutator = new \ReflectionClass($request_mutator);
        }
        if ($request_mutator instanceof \ReflectionClass && $request_mutator->implementsInterface(Http\RequestInterface::class)) {
            $this->request_mutator = $request_mutator;
        }
    }

        
    /**
     * returns
     * set return types for this route which are the accepted content types
     * @param  array $returns
     * @return Route
     */
    public function set_returns(...$returns) : Route {
        $returns = array_map("trim", $returns);
        $returns = array_map("strtolower", $returns);
        $returns = array_unique($returns);
        $this->returns = $returns;
        return $this;
    }
    
    /**
     * supports_accept
     * checks if requested content type is supported in the route 
     * @param  string $accept
     * @return ?string
     */
    public function negotiate_accept(?string $default = null) : ?string {
        return $this->http2->negotiateMimeType(
            $this->returns,
            null,
            null
        );
    }
    
    //method that return the first supported content type
    public function get_default_return() : string {
        return $this->returns[0] ?? "text/plain";
    }

    //Mutate request:
    public function mutate_request(Http\RequestInterface $request) : Http\RequestInterface {
        if ($this->request_mutator !== null) {
            $request = $this->request_mutator->newInstance($request);
        }
        return $request;
    }

        
    /**
     * exec
     * execute the route endpoint
     * @param  array $args packed arguments
     * @return Http\Response
     */
    public function exec(...$args) : Http\Response {

        //loop through the arguments and add find the request mutate and add it to the context:
        foreach ($args as &$arg) {
            if ($arg instanceof Http\RequestInterface) {
                $arg = $this->mutate_request($arg);
                break;
            }
        }

        // Lazy load the endpoint?
        if (is_object($this->func) && is_a($this->func, BindEndpoint::class)) {
            $this->func = $this->func->getInstance(); // will build the endpoint only at this point
        }

        //Execute the route endpoint:
        if (is_object($this->func) && !$this->func instanceof Closure && method_exists($this->func, "call")) {
            return $this->func->call($this->context, ...$args);
        }

        // Execute the route function:
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