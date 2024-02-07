<?php

namespace Frigate\Routing;

use ReflectionClass;
use Frigate\Routing\Http\Methods;
use Frigate\Routing\Routes\Route;
use Frigate\Routing\Router;
use Frigate\Api\Impl\StaticEndpoint;

trait RouterHelpersTrait
{

    public static function get(
        string $path, 
        object|array|string $exp, 
        array $context = [], 
        array $returns = [], 
        ReflectionClass|string|null $request_mutator = null
    ) : void {
        Router::define( Methods::GET, new Route( $path, $context, $returns, $exp, $request_mutator ) );
    }

    public static function post(
        string $path, 
        object|array|string $exp, 
        array $context = [], 
        array $returns = [], 
        ReflectionClass|string|null $request_mutator = null
    ) : void {
        Router::define( Methods::POST, new Route( $path, $context, $returns, $exp, $request_mutator ) );
    }

    public static function put(
        string $path, 
        object|array|string $exp, 
        array $context = [], 
        array $returns = [], 
        ReflectionClass|string|null $request_mutator = null
    ) : void {
        Router::define( Methods::PUT, new Route( $path, $context, $returns, $exp, $request_mutator ) );
    }

    public static function delete(
        string $path, 
        object|array|string $exp, 
        array $context = [], 
        array $returns = [], 
        ReflectionClass|string|null $request_mutator = null
    ) : void {
        Router::define( Methods::DELETE, new Route( $path, $context, $returns, $exp, $request_mutator ) );
    }

    public static function patch(
        string $path, 
        object|array|string $exp, 
        array $context = [], 
        array $returns = [], 
        ReflectionClass|string|null $request_mutator = null
    ) : void {
        Router::define( Methods::PATCH, new Route( $path, $context, $returns, $exp, $request_mutator ) );
    }

    public static function head(
        string $path, 
        object|array|string $exp, 
        array $context = [], 
        array $returns = [], 
        ReflectionClass|string|null $request_mutator = null
    ) : void {
        Router::define( Methods::HEAD, new Route( $path, $context, $returns, $exp, $request_mutator ) );
    }

    public static function options(
        string $path, 
        object|array|string $exp, 
        array $context = [], 
        array $returns = [], 
        ReflectionClass|string|null $request_mutator = null
    ) : void {
        Router::define( Methods::OPTIONS, new Route( $path, $context, $returns, $exp, $request_mutator ) );
    }

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
     *
     * @return void
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