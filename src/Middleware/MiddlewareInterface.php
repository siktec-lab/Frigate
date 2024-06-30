<?php 

declare(strict_types=1);

namespace Frigate\Middleware;

use Frigate\Routing\Http\Methods;
use Frigate\Routing\Http\RequestInterface;
use Frigate\Routing\Http\ResponseInterface;
use Frigate\Routing\Routes\Route;

/**
 * Middleware Interface
 * 
 * This interface defines the methods that a middleware must implement.
 */
interface MiddlewareInterface
{

    public function debug() : bool;

    public function exec(
        Methods $method, 
        RequestInterface &$request, 
        ResponseInterface &$response,
        array &$context,
        Route $target_route
    ) : bool;
}