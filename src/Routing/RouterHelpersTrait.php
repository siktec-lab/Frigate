<?php

namespace Frigate\Routing;

use ReflectionClass;
use Frigate\Routing\Http\Methods;
use Frigate\Routing\Routes\Route;
use Frigate\Routing\Router;
use Frigate\Api\Impl\StaticEndpoint;

trait RouterHelpersTrait
{

    /**
     * Define a route for the GET method
     * 
     * This is a helper method, equivalent to using Router::define() with the GET method and a Route object
     *
     * @param  string $path the path to match
     * @param  object|array|string $exp the endpoint to execute
     * @param  array $context the context to pass to the endpoint
     * @param  array $returns the supported return types i.e mime-types
     * @param  ReflectionClass|string|null $request_mutator the request mutator to use
     */
    public static function get(
        string $path, 
        object|array|string $exp, 
        array $context = [], 
        array $returns = [], 
        ReflectionClass|string|null $request_mutator = null
    ) : void {
        Router::define( Methods::GET, new Route( $path, $context, $returns, $exp, $request_mutator ) );
    }

    /**
     * Define a route for the POST method
     * 
     * This is a helper method, equivalent to using Router::define() with the POST method and a Route object
     *
     * @param  string $path the path to match
     * @param  object|array|string $exp the endpoint to execute
     * @param  array $context the context to pass to the endpoint
     * @param  array $returns the supported return types i.e mime-types
     * @param  ReflectionClass|string|null $request_mutator the request mutator to use
     */
    public static function post(
        string $path, 
        object|array|string $exp, 
        array $context = [], 
        array $returns = [], 
        ReflectionClass|string|null $request_mutator = null
    ) : void {
        Router::define( Methods::POST, new Route( $path, $context, $returns, $exp, $request_mutator ) );
    }

    /**
     * Define a route for the PUT method
     * 
     * This is a helper method, equivalent to using Router::define() with the PUT method and a Route object
     *
     * @param  string $path the path to match
     * @param  object|array|string $exp the endpoint to execute
     * @param  array $context the context to pass to the endpoint
     * @param  array $returns the supported return types i.e mime-types
     * @param  ReflectionClass|string|null $request_mutator the request mutator to use
     */
    public static function put(
        string $path, 
        object|array|string $exp, 
        array $context = [], 
        array $returns = [], 
        ReflectionClass|string|null $request_mutator = null
    ) : void {
        Router::define( Methods::PUT, new Route( $path, $context, $returns, $exp, $request_mutator ) );
    }

    /**
     * Define a route for the DELETE method
     * 
     * This is a helper method, equivalent to using Router::define() with the DELETE method and a Route object
     *
     * @param  string $path the path to match
     * @param  object|array|string $exp the endpoint to execute
     * @param  array $context the context to pass to the endpoint
     * @param  array $returns the supported return types i.e mime-types
     * @param  ReflectionClass|string|null $request_mutator the request mutator to use
     */
    public static function delete(
        string $path, 
        object|array|string $exp, 
        array $context = [], 
        array $returns = [], 
        ReflectionClass|string|null $request_mutator = null
    ) : void {
        Router::define( Methods::DELETE, new Route( $path, $context, $returns, $exp, $request_mutator ) );
    }

    /**
     * Define a route for the PATCH method
     * 
     * This is a helper method, equivalent to using Router::define() with the PATCH method and a Route object
     *
     * @param  string $path the path to match
     * @param  object|array|string $exp the endpoint to execute
     * @param  array $context the context to pass to the endpoint
     * @param  array $returns the supported return types i.e mime-types
     * @param  ReflectionClass|string|null $request_mutator the request mutator to use
     */
    public static function patch(
        string $path, 
        object|array|string $exp, 
        array $context = [], 
        array $returns = [], 
        ReflectionClass|string|null $request_mutator = null
    ) : void {
        Router::define( Methods::PATCH, new Route( $path, $context, $returns, $exp, $request_mutator ) );
    }

    /**
     * Define a route for the HEAD method
     * 
     * This is a helper method, equivalent to using Router::define() with the HEAD method and a Route object
     *
     * @param  string $path the path to match
     * @param  object|array|string $exp the endpoint to execute
     * @param  array $context the context to pass to the endpoint
     * @param  array $returns the supported return types i.e mime-types
     * @param  ReflectionClass|string|null $request_mutator the request mutator to use
     */
    public static function head(
        string $path, 
        object|array|string $exp, 
        array $context = [], 
        array $returns = [], 
        ReflectionClass|string|null $request_mutator = null
    ) : void {
        Router::define( Methods::HEAD, new Route( $path, $context, $returns, $exp, $request_mutator ) );
    }

    /**
     * Define a route for the OPTIONS method
     * 
     * This is a helper method, equivalent to using Router::define() with the OPTIONS method and a Route object
     *
     * @param  string $path the path to match
     * @param  object|array|string $exp the endpoint to execute
     * @param  array $context the context to pass to the endpoint
     * @param  array $returns the supported return types i.e mime-types
     * @param  ReflectionClass|string|null $request_mutator the request mutator to use
     */
    public static function options(
        string $path, 
        object|array|string $exp, 
        array $context = [], 
        array $returns = [], 
        ReflectionClass|string|null $request_mutator = null
    ) : void {
        Router::define( Methods::OPTIONS, new Route( $path, $context, $returns, $exp, $request_mutator ) );
    }

    /**
     * Define a route for all supported methods
     * 
     * This is a helper method, equivalent to using Router::define() with all methods and a Route object
     *
     * @param  string $path the path to match
     * @param  object|array|string $exp the endpoint to execute
     * @param  array $context the context to pass to the endpoint
     * @param  array $returns the supported return types i.e mime-types
     * @param  ReflectionClass|string|null $request_mutator the request mutator to use
     */
    public static function any(
        string $path, 
        object|array|string $exp, 
        array $context = [], 
        array $returns = [], 
        ReflectionClass|string|null $request_mutator = null
    ) : void {
        Router::define( 
            [ 
                Methods::GET, 
                Methods::POST, 
                Methods::PUT, 
                Methods::DELETE, 
                Methods::PATCH, 
                Methods::HEAD, 
                Methods::OPTIONS 
            ],
            new Route( $path, $context, $returns, $exp, $request_mutator )
        );
    }

    /**
     * Define a route for static file serving
     * 
     * Uses Built-in StaticEndpoint to serve files from a directory and will auto configure the path 
     * To use a path type of {serve:path} to match the file path.
     * 
     * @param  string $path the path to match
     * @param  string $directory the directory to serve files from (all sub-directories will be served)
     * @param  array|string $types the supported mime-types to serve (also file extensions are allowed)
     */
    public static function static(string $path, string $directory, array|string $types = "*/*") : void
    {
        $path = trim($path, "\t\n\r /\\");
        $path .= "/{serve:path}";

        Router::define( Methods::GET, new Route( 
            path: $path, 
            context: [
                "directory" => $directory,
                "serve"      => null,
            ], 
            returns: [], 
            exp: new StaticEndpoint(
                directory: $directory, 
                types: (array)$types
            )
        ));
    }
    
    /**
     * dump the defined routes trees for debugging
     */
    public static function dumpRoutes() : void
    {
        /** @var self \Frigate\Routing\Router */
        foreach (Router::getRoutesTree() as $method => $tree) {
            print PHP_EOL."Method: ".$method;
            print PHP_EOL.str_repeat("-", 80).PHP_EOL;
            print $tree.PHP_EOL;
        }
    }
}