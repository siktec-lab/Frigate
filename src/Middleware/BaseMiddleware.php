<?php 

declare(strict_types=1);

namespace Frigate\Middleware;

use Frigate\FrigateApp;
use Frigate\Routing\Http\Methods;
use Frigate\Routing\Http\RequestInterface;
use Frigate\Routing\Http\ResponseInterface;
use Frigate\Routing\Routes\Route;

/**
 * Middleware Interface
 * 
 * This interface defines the methods that a middleware must implement.
 */
abstract class BaseMiddleware implements MiddlewareInterface 
{ 

    /**
     * Debug mode flag.
     */
    public bool $debug = false;

    /**
     * Construct a new endpoint
     */
    public function __construct(?bool $debug = null)
    {
        $this->debug = $debug ?? FrigateApp::ENV_BOOL("FRIGATE_DEBUG_ENDPOINTS", false);
    }

    /**
     * Execute the middleware.
     * The actual implementation of the middleware logic.
     * 
     * @param Methods $method the request method
     * @param RequestInterface $request a mutable request object which is passed to the endpoint
     * @param ResponseInterface $response a mutable response object which is passed to the endpoint
     * @param array $context the context array which is passed to the endpoint
     * @param Route $target_route immutable route object of the target route
     */
    abstract public function exec(
        Methods $method, 
        RequestInterface &$request, 
        ResponseInterface &$response,
        array &$context,
        Route $target_route
    ) : void;

    /**
     * Get the debug mode flag.
     * Its an interface method which must be implemented.
     */
    public function debug() : bool {
        return $this->debug;
    }
}