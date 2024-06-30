<?php 

declare(strict_types=1);

namespace Frigate\Routing\Routes;

use ReflectionClass;
use Frigate\Routing\Router;
use Frigate\Routing\Http\Methods;

abstract class DefineRoute
{

    /**
     * The HTTP method(s) to define the route for
     */
    public Methods|string|array $method = Methods::GET;

    /**
     * The route path
     */
    public string $path = "/";

    /**
     * The route context
     */
    public array $context = [];

    /**
     * The supported return types
     */
    public array $returns = [
        "application/json"
    ];

    /**
     * The route endpoint or a bind endpoint
     */
    public object|array|string|null $exp = null;

    /**
     * The route additional middleware
     * @var array<string|MiddlewareInterface> $middleware - class names of the middleware to apply
     */
    public array $middleware = [];

    /**
     * The route middleware to avoid
     * @var array<string|MiddlewareInterface> $avoid_middleware - class names of the middleware to avoid
     */
    public array $avoid_middleware = [];

    /**
     * Avoid all middleware
     */
    public bool $avoid_all_middleware = false;

    /**
     * The request mutator
     */
    protected ?ReflectionClass $request_mutator = null;

    /** 
     * Build the route
     */
    public function init() : Route {
        // Initialize the route:
        return new Route(
            path                : $this->path,
            context             : $this->context,
            returns             : $this->returns,
            exp                 : $this->exp,
            middleware          : $this->middleware,
            avoid_middleware    : $this->avoid_all_middleware ? true : $this->avoid_middleware,
            request_mutator     : $this->request_mutator
        );
    }

    /**
     * Register the route
     */
    public function register() : void {
        // Register the route:
        Router::define($this->method, $this->init());
    }
}