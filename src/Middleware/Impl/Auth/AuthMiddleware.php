<?php 

declare(strict_types=1);

namespace Frigate\Middleware\Impl\Auth;

use Frigate\Middleware\BaseMiddleware;
use Frigate\Middleware\Impl\Auth\Methods\Interfaces\AuthInterface;
use Frigate\Routing\Http\Methods;
use Frigate\Routing\Http\RequestInterface;
use Frigate\Routing\Http\ResponseInterface;
use Frigate\Routing\Routes\Route;

/**
 * Middleware Interface
 * 
 * This interface defines the methods that a middleware must implement.
 */
class AuthMiddleware extends BaseMiddleware 
{ 

    /**
     * Debug mode flag.
     */
    public bool $debug = false;

    /**
     * The supported methods
     * @var AuthInterface[]
     */
    public array $methods = [];

    /**
     * Construct a new endpoint
     */
    public function __construct(
        array $methods  = [], // All the authentication methods that are supported
        ?bool $debug    = null
    ) {
        parent::__construct($debug);

        // Validate the methods
        foreach ($methods as $method) {
            if (!($method instanceof AuthInterface)) {
                // TODO: Better error handling
                throw new \InvalidArgumentException("All auth methods must implement the 'AuthInterface'.");
            }
        }

        $this->methods = $methods;
    }

    /**
     * Execute the auth middleware.
     * The actual implementation of the middleware logic.
     * 
     * @param Methods $method the request method
     * @param RequestInterface $request a mutable request object which is passed to the endpoint
     * @param ResponseInterface $response a mutable response object which is passed to the endpoint
     * @param array $context the context array which is passed to the endpoint
     * @param Route $target_route immutable route object of the target route
     */
    public function exec(
        Methods $method, 
        RequestInterface &$request, 
        ResponseInterface &$response,
        array &$context,
        Route $target_route
    ) : void {

        foreach ($this->methods as $method) {
            [$granted, $user, $token] = $method->authenticate($request);
            if ($granted) {
                $context["auth"] = ["user" => $user, "token" => $token];
                return;
            }
        }

        throw new \Exception("Unauthorized", 401);
    }
}