<?php 

declare(strict_types=1);

namespace Frigate\Routing;

use Exception;
use Frigate\FrigateApp;
use Frigate\Routing\Http\RequestInterface;
use Frigate\Routing\Http\ResponseInterface;
use Frigate\Routing\Http\Response;
use Frigate\Routing\Http\RouteRequest;
use Frigate\Routing\Http\Methods;
use Frigate\Api\EndpointInterface;
use Frigate\Middleware\MiddlewareInterface;
use Frigate\Routing\Paths\PathBranch;
use Frigate\Routing\Paths\PathTree;
use Frigate\Routing\Routes\Route;
use Frigate\Routing\Routes\DefineRoute;

class Router 
{
    use RouterHelpersTrait;

    private static bool $debug = false;

    private static ?RequestInterface $request = null;

    /** @var Route[] $errors*/
    private static array $errors  = [];

    /** @var PathTree[] $routes */
    private static array $routes = [];
    
    /** The request class to use for building requests */
    public const DEFAULT_REQUEST_CLASS = RouteRequest::class;
    private static string $use_request = self::DEFAULT_REQUEST_CLASS;

    /** The response class to use for building responses */
    public const DEFAULT_RESPONSE_CLASS = Response::class;
    private static string $use_response = self::DEFAULT_RESPONSE_CLASS;

    /**
     * initialize the router
     * @param bool $load_request - load the request immediately
     * @param ?bool $debug - enable debug mode null for FRIGATE_DEBUG_ROUTER env
     * @param ?string $use_request - the request class to use null for default
     * @param ?string $use_response - the response class to use null for default
     * 
     * @throws Exception when the request or response class is not found or does not implement the correct interface
     */
    public static function init(
        bool $load_request = true, 
        ?bool $debug = null, 
        ?string $use_request = null,
        ?string $use_response = null
    ) : void {

        // Set request and response classes:
        self::setRequestClass($use_request);
        self::setResponseClass($use_response);
        
        // Set the debug mode:
        self::debug($debug ?? FrigateApp::ENV_BOOL("FRIGATE_DEBUG_ROUTER", false));

        // Load the request:
        if ($load_request) {
            self::loadRequest();
        }
    }
    
    /**
     * set the request class to use for building requests
     * @param ?string $request_class null to reset to the default
     * @throws Exception when the request class is not found or does not implement RequestInterface
     */
    public static function setRequestClass(?string $request_class = null) : void 
    {
        // Set the request class if needed:
        if (!is_null($request_class)) {
            //TODO: make those exceptions FrigateExceptions
            // Validate the request class:
            if (!class_exists($request_class)) {
                throw new Exception("Request class not found", 1);
            }
            if (!is_subclass_of($request_class, RequestInterface::class)) {
                throw new Exception("Request class does not implement RequestInterface", 1);
            }
            self::$use_request = $request_class;
        } else {
            self::$use_request = self::DEFAULT_REQUEST_CLASS;
        }
    }

    /**
     * set the response class to use for building responses
     * @param ?string $response_class null to reset to the default
     * @throws Exception when the response class is not found or does not implement ResponseInterface
     */
    public static function setResponseClass(?string $response_class = null) : void 
    {
        // Set the response class if needed:
        if (!is_null($response_class)) {
            //TODO: make those exceptions FrigateExceptions
            // Validate the response class:
            if (!class_exists($response_class)) {
                throw new Exception("Response class not found", 1);
            }
            if (!is_subclass_of($response_class, ResponseInterface::class)) {
                throw new Exception("Response class does not implement ResponseInterface", 1);
            }
            self::$use_response = $response_class;
        } else {
            self::$use_response = self::DEFAULT_RESPONSE_CLASS;
        }
    }

    /**
     * reset the router
     * will remove all routes and handlers
     */
    public static function reset() : void 
    {
        self::$routes = [];
        self::$errors = [];
        self::$request = null;
    }
    
    /**
     * get / set the debug mode
     */
    public static function debug(?bool $enable = null) : bool 
    {
        if (!is_null($enable)) {
            self::$debug = $enable;
        }
        return self::$debug;
    }

    /**
     * load and parses the request
     * If no base is provided, we'll use the APP_BASE_URI
     */
    public static function loadRequest(?string $base = null) : void 
    {
        // If no base is provided, we'll use the APP_BASE_URI
        $base = $base ?? FrigateApp::$globals["APP_BASE_URI"];

        // Build the request:
        self::$request = self::buildRequest(
            server_arr : null, // Null for $_SERVER
            base       : $base,
            method     : null, // Method null for REQUEST_METHOD
            uri        : null, // Uri override null for REQUEST_URI
            query      : null, // Query override null for whatever is in the uri
            body       : fopen('php://input', 'r'),
            post       : $_POST
        );

        // Debug the request:
        FrigateApp::debug(self::class, "got request", (string)self::$request);
        FrigateApp::debug(self::class, "request raw parts", [
            "PATH"  => self::$request->getPath(),
            "POST"  => self::$request->getPostData(),
            "QUERY" => self::$request->getQueryParameters()
        ]);
    }

    /**
     * get the route branch for a given method and path
     * @throws Exception when the method is invalid
     */
    public static function getRouteBranch(
        Methods|string $method, 
        string $path, 
        array &$with_context = []
    ) : ?PathBranch {
        $method = is_string($method) ? Methods::fromString($method) : $method;
        if (!array_key_exists($method->value, self::$routes)) {
            return null;
        }
        [$branch, $with_context] = self::$routes[$method->value]->eval($path, $with_context);
        return $branch;
    }

    /**
     * create a request for endpoint invocation
     */
    public static function requestFor(
        string $path, 
        Methods|string|null $method  = null, // Method null for REQUEST_METHOD
        array $query                 = [],   // Set the query parameters
        $body                        = "",   // Body of the request
        array $post                  = []    // Post data override null for whatever is in the body
    ) : RequestInterface {

        // TODO: Extend ServerArray with more options

        // path to uri:
        $path = trim($path);
        $path = ltrim($path, '/');
        $path = FrigateApp::$globals["APP_BASE_URI"].$path;

        return self::buildRequest(
            server_arr : null, // Null for $_SERVER
            base       : null,
            method     : $method, // Method null for REQUEST_METHOD
            uri        : $path, // Uri override null for REQUEST_URI
            query      : $query, // Query override null for whatever is in the uri
            body       : $body,
            post       : $post
        );

    }

    /**
     * build a request object for passing to the route handler
     * 
     * @param  array|null $server_arr null for $_SERVER, REQUEST_URI and REQUEST_METHOD are required.
     * @param  string|null $base Base url null for APP_BASE_URI
     * @param  Methods|string|null $method Method null for REQUEST_METHOD
     * @param  string|null $uri Uri override null for REQUEST_URI
     * @param  array|null $query Query override null for whatever is in the uri
     * @param  resource|string|callable $body the body of the request 
     * @param  array $post Post data to add to the request
     * @throws Exception when the request can't be built
     */
    private static function buildRequest(
        ?array $server_arr          = null, // Null for $_SERVER
        ?string $base               = null, // Base url null for APP_BASE_URI
        Methods|string|null $method = null, // Method null for REQUEST_METHOD
        ?string $uri                = "",   // Uri override null for REQUEST_URI
        ?array $query               = null, // Query override null for whatever is in the uri
        mixed  $body                = "",   // Body of the request
        array  $post                = []    // Post data override null for whatever is in the body
    ) : RequestInterface {
        
        // If no server array is provided, we'll use the $_SERVER array.
        if (is_null($server_arr)) {
            $server_arr = $_SERVER;
            if ('cli' === PHP_SAPI) {
                // If we're running off the CLI, we're going to set some default settings.
                $server_arr['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
                $server_arr['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
            }
        }

        // If no base is provided, we'll use the APP_BASE_URI
        if (is_null($base)) {
            $base = FrigateApp::$globals["APP_BASE_URI"];
        }

        // If method is provided, we'll use it instead of REQUEST_METHOD
        $method = !is_string($method) && !is_null($method) ? $method->value : $method;
        if (!empty($method)) {
            $server_arr['REQUEST_METHOD'] = trim($method);
        }

        // If url is provided, we'll use it instead of REQUEST_URI
        if (!empty($uri)) {
            $server_arr['REQUEST_URI'] = trim($uri);
        }

        // If query is provided, we'll use it instead of the one in the uri
        // If its an empty array, we'll remove the query from the uri
        if (!is_null($query)) { 
            // If we have a query string, we'll append it to the uri
            $server_arr['REQUEST_URI'] = trim($server_arr['REQUEST_URI'], '?');
            $server_arr['REQUEST_URI'] = strtok($server_arr['REQUEST_URI'], '?') ?: $server_arr['REQUEST_URI'];
            // If we have a query string, we'll append it to the uri
            if (!empty($query)) { 
                $server_arr['QUERY_STRING'] = http_build_query($query); 
                $server_arr['REQUEST_URI'] .= '?'.$server_arr['QUERY_STRING'];
            }
        }

        /** @var RequestInterface $request */
        $request = new self::$use_request();
        $request->initFromServerArray($server_arr);
        $request->setBody($body);
        $request->setBaseUrl($base);
        $request->setPostData($post);

        return $request;
    } 

    /**
     * Attach a route expression to the router specified by the method and path
     * 
     * @param  Methods|string $method the main method
     * @param  string $path the path to match
     * @param  object|array|string $exp the expression to register
     */
    private static function attachRouteExpression(
        Methods|string $method, 
        string $path, 
        object|array|string $exp
    ) : void {

        // Normalize:
        $method = is_string($method) ? Methods::fromString($method) : $method;

        // Check if the method needs to be defined:
        if (!array_key_exists($method->value, self::$routes)) {
            self::$routes[$method->value] = new PathTree();
        }

        // Register the route:
        self::$routes[$method->value]->define($path, $exp);

    }

    /**
     * define a route to be used when a request matches the route and the method
     *
     * @param  Methods|string|array $method the method or an array of methods
     * @param  Route $route
     * @throws Exception when the route allready exists or can't be parsed properly
     * @throws InvalidArgumentException when the method is not supported
     */
    public static function define(Methods|string|array $method, Route|DefineRoute $route) : void 
    {
        $methods = is_array($method) ? $method : [$method];

        // If the route is a DefineRoute, we'll initialize it:
        if ($route instanceof DefineRoute) {
            $methods = $route->method;
            $route = $route->init();
        }

        // Initialize a new PathTree if it doesn't exist for this method:
        foreach ($methods as $m) {

            // Attach the route expression to the router:
            self::attachRouteExpression($m, $route->path, $route);

            // Additional middleware?
            foreach ($route->middleware as $mw) {
                self::middleware($m, $route->path, $mw);
            }
        }
    }
    
    /**
     * load the routes from a folder - all files ending with Route.php will be loaded
     *
     * @param  string $path the path to the folder
     * @param  bool $cache should we cache the files
     * @return int the number of routes loaded
     */
    public static function defineFrom(string $folder, bool $cache = false) : int
    {
        //TODO: cache the files
        // Get all the files in the folder That ends with Route.php
        $files = glob($folder . '/*Route.php');

        // Check if we found any files
        if ($files === false) {
            throw new Exception("Error reading the folder $folder for routes", 1);
        }

        $cachefound = 0;

        // Loop through the files
        foreach ($files as $file) {

            // Include the file
            include_once $file;

            // Get the class name
            $class = pathinfo($file, PATHINFO_FILENAME);
            
            // Check if the class exists
            if (!class_exists($class)) {
                throw new Exception("Class $class not found in $file", 1);
            }

            // Check if the class is a subclass of DefineRoute
            if (!is_subclass_of($class, DefineRoute::class)) {
                throw new Exception("Class $class is not a subclass of DefineRoute", 1);
            }

            // Create a new instance of the class using the modern constructor
            /** @var DefineRoute $route */
            $route = new $class();
            
            // Register the route
            $route->register();

            $cachefound++;
        }

        return $cachefound;
    }
    /**
     * define an error route to be used when an error code is raised
     *
     * @param  int|string $code 'any' for any error
     * @param  EndpointInterface $endpoint the endpoint to use
     */
    public static function error(int|string $code, EndpointInterface $endpoint) : void 
    {
        self::$errors[$code] = new Route(path: "/", exp: $endpoint);
    }

    /**
     * define a middleware to be used when a request matches the method and path
     *
     * @param  Methods|string|array $method the method or an array of methods
     * @param  string|array $path the path or an array of paths
     * @param  MiddlewareInterface|string $middleware the middleware to use
     */
    public static function middleware(
        Methods|string|array $method, 
        array|string $path, 
        MiddlewareInterface|string $middleware
    ) : void
    {   
        // Normalize:
        $path = is_array($path) ? $path : [$path];
        $methods = is_array($method) ? $method : [$method];

        // Make sure the path is a shadow branch:
        foreach ($path as &$p) {
            // If the path doesn't contain any shadow branch, we'll add one to the end of the path:
            if (!str_contains($p, PathBranch::TOKEN_SHADOW_BRANCH)) {
                $p = rtrim($p, "/").PathBranch::TOKEN_SHADOW_BRANCH;
            }
            // truncate the path to the last shadow branch - a path can have multiple shadow branches
            // Example: /path^/name/path2^/name2 => /path^/name/path2^
            $i = strrpos($p, PathBranch::TOKEN_SHADOW_BRANCH);
            $p = substr($p, 0, $i + 1);

            // Attach the middleware to the path:
            foreach ($methods as $m) {
                self::attachRouteExpression($m, $p, $middleware);
            }
        }
    }

    /**
     * load
     * load the current request
     * @param  ?RouteRequest $request null when current request should be used
     * @return Http\Response
     */
    public static function load(?Http\RequestInterface $request = null) : ResponseInterface
    {
        $request = $request ?? self::$request;
        return self::execute($request);
    }

    /**
     * serve the current request
     * will load the current request and send the response
     * returns any unexpected output
     * same as Router::load() + FrigateApp::endPageBuffer() + Router::sendResponse()
     */
    public static function serve() : string
    {
        $response   = Router::load();
        $unexpected = FrigateApp::endPageBuffer();  
        self::sendResponse($response);
        return $unexpected;
    }
    
    /**
     * execute
     *
     * @param  RequestInterface $request null when current request should be used
     * @return ResponseInterface
     * @throws Exception when the request can't be executed
     */
    public static function execute(
        RequestInterface $request
    ) : ResponseInterface {
        
        //Get the Request method:
        $method = $request->getMethod();

        // Prepare the response:
        /** @var ResponseInterface $response */
        $response = new self::$use_response(
            status: 200, 
            headers: [
                "X-Perform"    => $request->isTest() ? "Test" : "Live"
            ]
        );
        
        try {
            //Check that the method is supported:
            if (!array_key_exists($method->value, self::$routes)) {
                throw new \Exception("Request method '{$method->value}' is not supported", 404);
            }
            //Get the route & evaluate it:
            /** 
             * @var PathBranch|null $branch 
             * @var array $context 
             */
            [$branch, $context] = self::$routes[$method->value]->eval($request->getPath());
            if (is_null($branch)) {
                throw new \Exception("Not Found", 404);
            }
            /** @var Route $route */
            $route = $branch->exp;
            // Prepare the request:
            $accept = $request->negotiateAccept(
                $route->returnTypes(), 
                $route->defaultReturn()
            );
            // Return an error if the accept header is not supported:
            if (empty($accept)) {
                throw new \Exception("Accept header not supported", 406);
            }
            // Set the response content type:
            $response->setHeader("Content-Type", $accept);
            // Apply the middleware:
            $middleware = $branch->getShadowExpressions();
            foreach ($middleware as $mw) {

                $class_name = is_string($mw) ? $mw : get_class($mw);
                // Avoid or continue?
                if ($route->avoid_all_middleware || in_array($class_name, $route->avoid_middleware)) {
                    continue;
                }
                // Check if the middleware is a class name:
                if (is_string($mw) && is_subclass_of($mw, MiddlewareInterface::class, true)) {
                    // Create the middleware using
                    $mw = new $mw();
                }
                // Check if the middleware is an instance of MiddlewareInterface and execute it:
                if ($mw instanceof MiddlewareInterface) {
                    // Execute the middleware:
                    $mw->exec(
                        $method, 
                        $request,
                        $response, 
                        $context,
                        $route
                    );
                } elseif (is_array($mw)) {
                    // Merge the context:
                    $context = array_merge($context, $mw);
                }
            }

            //Apply the context:
            $route->applyContext($context);
            
            //Execute the route:
            return $route->exec($request, $response);
            
        } catch(Exception $e) {

            // Return the error response:
            return self::handleError(
                request : $request, 
                response: $response,
                code    : $e->getCode(), 
                message : $e->getMessage(),
                line    : $e->getLine(),
                file    : $e->getFile(), 
                trace   : $e->getTraceAsString()
            );
        }
    }

    /**
     * Sends the HTTP response back to a HTTP client.
     *
     * This calls php's header() function and streams the body to php://output.
     * inspired by => https://github.com/sabre-io/http/blob/master/lib/Sapi.php
     */
    public static function sendResponse(ResponseInterface $response): void 
    {
        header('HTTP/'.$response->getHttpVersion().' '.$response->getStatus().' '.$response->getStatusText());
        foreach ($response->getHeaders() as $key => $value) {
            foreach ($value as $k => $v) {
                if (0 === $k) {
                    header($key.': '.$v);
                } else {
                    header($key.': '.$v, false);
                }
            }
        }

        $body = $response->getBody();
        if (null === $body) {
            return;
        }

        if (is_callable($body)) {
            $body();
            return;
        }

        $contentLength = $response->getHeader('Content-Length');
        if (null !== $contentLength) {
            $output = fopen('php://output', 'wb');
            if (is_resource($body) && 'stream' == get_resource_type($body)) {
                
                // a workaround to make PHP more possible to use mmap based copy, see https://github.com/sabre-io/http/pull/119
                $left = (int) $contentLength;
                // copy with 4MiB chunks
                $chunk_size = 4 * 1024 * 1024;
                stream_set_chunk_size($output, $chunk_size);
                // If this is a partial response, flush the beginning bytes until the first position that is a multiple of the page size.
                $contentRange = $response->getHeader('Content-Range');
                // Matching "Content-Range: bytes 1234-5678/7890"
                if (null !== $contentRange && preg_match('/^bytes\s([0-9]+)-([0-9]+)\//i', $contentRange, $matches)) {
                    // 4kB should be the default page size on most architectures
                    $pageSize = 4096;
                    $offset = (int) $matches[1];
                    $delta = ($offset % $pageSize) > 0 ? ($pageSize - $offset % $pageSize) : 0;
                    if ($delta > 0) {
                        $left -= stream_copy_to_stream($body, $output, min($delta, $left));
                    }
                }
                while ($left > 0) {
                    $copied = stream_copy_to_stream($body, $output, min($left, $chunk_size));
                    // stream_copy_to_stream($src, $dest, $maxLength) must return the number of bytes copied or false in case of failure
                    // But when the $maxLength is greater than the total number of bytes remaining in the stream,
                    // It returns the negative number of bytes copied
                    // So break the loop in such cases.
                    if ($copied <= 0) {
                        break;
                    }
                    $left -= $copied;
                }
            } else {
                fwrite($output, (string) $body, (int) $contentLength);
            }
        } else {
            file_put_contents('php://output', $body);
        }

        if (is_resource($body)) {
            fclose($body);
        }
    }

    /**
     * get the defined routes as a tree
     * @return array<string,PathTree>
     */
    public static function getRoutesTree() : array 
    {
        return self::$routes;
    }

    /**
     * handle an error and return a response for it
     *
     * @param  Http\RouteRequest $request
     * @param  int $code
     * @param  string $message
     * @param  string $trace
     */
    protected static function handleError(
        RequestInterface $request,
        ResponseInterface $response,
        int     $code, 
        string  $message    = "",
        int     $line       = 0,
        string  $file       = "",  
        string  $trace      = ""
    ) : ResponseInterface {

        // Get code or default to 500:
        if ($code < 100 || $code > 599) {
            $code = 500;
        }

        // Get the error handler:
        $handler = null;
        if (array_key_exists($code, self::$errors)) {
            $handler = self::$errors[$code];
        } else if (array_key_exists("any", self::$errors)) {
            $handler = self::$errors["any"];
        }

        // Update the response:
        $response->setStatus($code);
        if (is_null($response->getHeader("Content-Type"))) {
            $returns = [ "text/html", "application/json" ];
            $default = "text/html";
            $expects = $request->negotiateAccept(
                ($handler?->returnTypes() ?? $returns), 
                ($handler?->defaultReturn() ?? $default)
            );
            $response->setHeader("Content-Type", $expects);
        }

        // Data for the error route:
        $apply_ctx = [
            "code"      => $code,
            "line"      => $line,
            "file"      => $file,
            "message"   => $message,
            "trace"     => $trace
        ];

        // Execute the error route:
        if ($handler) {
            $handler->applyContext($apply_ctx);            
            return $handler->exec($request, $response);
        }

        //No error handler, return the default response:
        if ($response->getHeader("Content-Type") === "application/json") {
            $response->setBodyJson([
                "error" => [
                    "code"      => $code,
                    "message"   => $message,
                    "file"      => $file,
                    "line"      => $line,
                    "trace"     => $trace
                ]
            ]);
        } else {
            $response->setBody(sprintf("Error %d: %s \nFile : %s \nLine : %d", $code, $message, $file, $line));
        }
        
        return $response;
    }
}