<?php 

declare(strict_types=1);

namespace Frigate\Routing\Routes;

use Closure;
use ReflectionClass;
use Frigate\Routing\Http\RequestInterface;
use Frigate\Routing\Http\ResponseInterface;
use Frigate\Api\BindEndpoint;
use Frigate\Middlewares\MiddlewareInterface;
class Route 
{

    /**
     *  Default return type:
     *  @var string
     */
    public const DEFAULT_RETURN = "application/json";

    /**
     * The route path syntax
     */
    public string $path;
    
    /**
     * The route initial context
     */
    public array $context = [];
    
    /**
     * The supported return types in a hierarchy of preference
     */
    private array $returns = [
        "application/json",
        "text/html"
    ];
    
    /**
     * The route endpoint or a bind endpoint
     */
    public object|array|string|null $exp;
    
    /**
     * The request mutator
     */
    protected ?ReflectionClass $request_mutator = null;
    
    /**
     * The route additional middleware
     * @var array<string|MiddlewareInterface> $middleware - class names of the middleware to apply
     */
    public array $middleware = [];

    /**
     * The route middleware to avoid
     * @var array<string> $avoid_middleware - class names of the middleware to avoid
     */
    public array $avoid_middleware = [];

    /**
     * Avoid all middleware
     */
    public bool $avoid_all_middleware = false;

    /**
     * construct a new route
     * 
     * @param string $path the route path
     * @param array<string,mixed> $context the route default context
     * @param array<string> $returns the supported return types i.e mime-types
     * @param object|array|string|null $exp the route endpoint or a bind endpoint
     * @param array<string|MiddlewareInterface> $middleware the route middleware class names to apply
     * @param array<string|MiddlewareInterface>|bool $avoid_middleware the route middleware class names to avoid if true avoid all
     * @param ReflectionClass|string|null $request_mutator the request mutator
     */
    public function __construct(
        string $path, 
        array $context = [], 
        array $returns = [], 
        object|array|string|null $exp = null, 
        array $middleware = [],
        array|bool $avoid_middleware = [],
        ReflectionClass|string|null $request_mutator = null
    ) {
        $this->path = trim($path, "\t\n\r /\\");
        $this->context = $context;
        $this->exp = $exp;
        if (!empty($returns)) {
            $this->setSupportedReturnTypes(...$returns);
        }

        // set the middleware:
        foreach ($middleware as $mw) {
            $this->addMiddleware($mw);
        }

        // set the avoid middleware:
        if ($avoid_middleware === true) {
            $this->avoid_all_middleware = true;
        } elseif (is_array($avoid_middleware)) {
            foreach ($avoid_middleware as $mw) {
                $this->avoidMiddleware($mw);
            }
        }
        
        // set the request mutator:
        if (is_string($request_mutator)) {
            $this->request_mutator = new ReflectionClass($request_mutator);
        }
        if ( $request_mutator instanceof ReflectionClass
            && $request_mutator->implementsInterface(RequestInterface::class)
        ) {
            $this->request_mutator = $request_mutator;
        }
    }

    /**
     * add a middleware to this route
     * 
     * @param string|MiddlewareInterface|array<string|MiddlewareInterface> $middleware the middleware class name or instance
     * @return self
     */
    public function addMiddleware(string|MiddlewareInterface|array $middleware) : self
    {
        $middleware = is_array($middleware) ? $middleware : [$middleware];
        foreach ($middleware as $m) {
            $this->middleware[] = is_string($m) ? $m : get_class($m);
        }
        return $this;
    }

    /**
     * avoid a middleware for this route
     * 
     * @param string|MiddlewareInterface|array<string|MiddlewareInterface> $middleware the middleware class name or instance
     * @return self
     */
    public function avoidMiddleware(string|MiddlewareInterface|array $middleware) : self
    {
        $middleware = is_array($middleware) ? $middleware : [$middleware];
        foreach ($middleware as $m) {
            $this->avoid_middleware[] = is_string($m) ? $m : get_class($m);
        }
        return $this;
    }

    /**
     * set return types for this route which are the accepted content types
     */
    public function setSupportedReturnTypes(...$returns) : self
    {
        $this->returns = array_unique(array_filter(array_map(function($v) {
            return is_string($v) ? strtolower(trim($v)) : null;
        }, $returns)));
        return $this;
    }
    
    /**
     * get the supported return types for this route
     */
    public function returnTypes() : array 
    {
        return $this->returns;
    }

    /**
     * get the default return type for this route
     */
    public function defaultReturn() : string
    {
        return $this->returns[0] ?? self::DEFAULT_RETURN;
    }

    /**
     * apply / extend the context of this route
     */
    public function applyContext(array $context) : Route
    {
        $this->context = array_merge($this->context, $context);
        return $this;
    }

    //Mutate request:
    public function mutateRequest(RequestInterface $request) : RequestInterface
    {
        if ($this->request_mutator !== null) {
            $request = $this->request_mutator->newInstance($request);
        }
        return $request;
    }

    /**
     * exec
     * execute the route endpoint
     * @param  array $args packed arguments
     * @return ResponseInterface
     */
    public function exec(RequestInterface $request, ResponseInterface $response) : ResponseInterface 
    {
        
        // Mutate the request:
        // $request = $this->mutateRequest($request);

        // Lazy load the endpoint?
        if (is_object($this->exp) && is_a($this->exp, BindEndpoint::class)) {
            $this->exp = $this->exp->getInstance(); // will build the endpoint only at this point
        }

        //Execute the route endpoint:
        if (is_object($this->exp) && !$this->exp instanceof Closure && method_exists($this->exp, "call")) {
            return $this->exp->call($this->context, $request, $response);
        }
        // Execute the route function using modern PHP syntax:
        return ($this->exp)($this->context, $request, $response);
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