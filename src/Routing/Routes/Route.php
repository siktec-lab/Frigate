<?php 

declare(strict_types=1);

namespace Frigate\Routing\Routes;

use Closure;
use Frigate\Routing\Http;
use Frigate\Api\BindEndpoint;

class Route {

    public const DEFAULT_RETURN = "application/json";

    public string $path;
    
    public array $context = [];
    
    private array $returns = [
        "application/json",
        "text/html"
    ];
    
    public object|array|string|null $exp;
    
    protected ?\ReflectionClass $request_mutator = null;
    
    private array $override_params = [ //TODO: do we need this????
        "debug"         => null,
        "auth"          => null,
        "auth_method"   => null
    ];

    /**
     * construct a new route
     */
    public function __construct(
        string $path, 
        array $context = [], 
        array $returns = [], 
        object|array|string|null $exp = null, 
        \ReflectionClass|string|null $request_mutator = null
    ) {
        $this->path = trim($path, "\t\n\r /\\");
        $this->context = $context;
        $this->exp = $exp;
        if (!empty($returns)) {
            $this->setSupportedReturnTypes(...$returns);
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
     * set return types for this route which are the accepted content types
     */
    public function setSupportedReturnTypes(...$returns) : Route
    {
        $this->returns = array_unique(array_filter(array_map(function($v) {
            return is_string($v) ? strtolower(trim($v)) : null;
        }, $returns)));
        return $this;
    }
    
    public function getSupportedReturnTypes() : array {
        return $this->returns;
    }

    //method that return the first supported content type
    public function getDefaultReturn() : string {
        return $this->returns[0] ?? self::DEFAULT_RETURN;
    }

    public function applyContext(array $context) : Route {
        $this->context = array_merge($this->context, $context);
        return $this;
    }

    //Mutate request:
    public function mutateRequest(Http\RequestInterface $request) : Http\RequestInterface {
        if ($this->request_mutator !== null) {
            $request = $this->request_mutator->newInstance($request);
        }
        return $request;
    }

    public function overrideEndpointParams(
        ?bool $debug = null,
        ?bool $auth  = null,
        $auth_method = null
    ) : Route {
        if (!is_null($debug)) {
            $this->override_params["debug"] = $debug;
        }
        if (!is_null($auth)) {
            $this->override_params["auth"] = $auth;
        }
        if (!is_null($auth_method)) {
            $this->override_params["auth_method"] = $auth_method;
        }
        return $this;
    }

    /**
     * exec
     * execute the route endpoint
     * @param  array $args packed arguments
     * @return Http\Response
     */
    public function exec(Http\RequestInterface $request, Http\Response $response) : Http\Response {

        //TODO: clean this up:
        //loop through the arguments and add find the request mutate and add it to the context:
        // foreach ($args as &$arg) {
        //     if ($arg instanceof Http\RequestInterface) {
        //         // Mutate the request:
        //         $arg = $this->mutate_request($arg);
        //         break;
        //     }
        // }

        // Negotiate the accept header:
        // $request->expects = $this->negotiateAccept($request, $this->getDefaultReturn());
        
        // Mutate the request:
        // $request = $this->mutateRequest($request);

        // Set the context:
        // $this->context = array_merge($this->context, $context); // TODO: check this one.

        // Lazy load the endpoint?
        if (is_object($this->exp) && is_a($this->exp, BindEndpoint::class)) {
            $this->exp = $this->exp->getInstance( // All overrides are passed to the endpoint
                $this->override_params["debug"], 
                $this->override_params["auth"],
                $this->override_params["auth_method"]
            ); // will build the endpoint only at this point
        }

        //Execute the route endpoint:
        if (is_object($this->exp) && !$this->exp instanceof Closure && method_exists($this->exp, "call")) {
            return $this->exp->call($this->context, $request, $response);
        }

        // Execute the route function:
        //TODO: Make this more modern.
        return call_user_func_array(
            $this->exp, 
            [$this->context, $request, $response]
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