<?php 

namespace Siktec\Frigate\Routing;

use \Siktec\Frigate\Base;
use \Siktec\Frigate\Routing\Http;
use \Siktec\Frigate\Routing\Paths\PathTree;
use \Throwable;

class Router {


    public static bool $debug = false;

    private static Http\RouteRequest $request;

    /** @var Route[] $errors*/
    private static array $errors  = [];

    /** @var PathTree[] $routes */
    private static array $routes = [];
        
    /**
     * init
     * initialize the router
     * @param  bool $debug
     * @return void
     */
    public static function init(bool $debug = false) : void {
        self::$debug = $debug;
    }
    
    /**
     * parse_request
     * load and parses the request uri
     * @param  string $route or none for SERVER => REQUEST_URI
     * @return void
     */
    public static function parse_request(string $base = "/") : void {

        $server_arr = $_SERVER;

        if ('cli' === PHP_SAPI) {
            // If we're running off the CLI, we're going to set some default settings.
            $server_arr['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
            $server_arr['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        }

        $got =  self::createFromServerArray($server_arr);
        self::$request = new Http\RouteRequest($got);
        self::$request->setBaseUrl($server_arr['BASE_URL'] ?? "");
        self::$request->setBody(fopen('php://input', 'r'));
        self::$request->setPostData($_POST);
        self::$request->setBaseUrl($base);

        Base::debug(self::class, "got request",     (string)self::$request);
        Base::debug(self::class, "request raw parts", [
            "PATH"  => self::$request->getPath(),
            "POST"  => self::$request->getPostData(),
            "QUERY" => self::$request->getQueryParameters()
        ]);
    }
        
    /**
     * define_error
     * define an error route to be used when an error code is raised
     * @param  int|string $code 'any' for any error
     * @param  Route $route the route to be used
     * @return void
     */
    public static function define_error(int|string $code, Route $route) : void {
        self::$errors[$code] = $route;
    }
    
    /**
     * define
     * define a route to be used when a request matches the route and the method
     * @param  string|array $method
     * @param  Route $route
     * @throws Exception when the route allready exists or can't be parsed properly
     * @return void
     */
    public static function define(string|array $method, Route $route) : void {
        $methods = is_array($method) ? $method : [$method];
        // Initialize a new PathTree if it doesn't exist for this method:
        foreach ($methods as $m) {
            $m = strtoupper($m);
            if (!array_key_exists($m, self::$routes)) {
                self::$routes[$m] = new PathTree();
            }
            // Register the route:
            self::$routes[$m]->define($route->path, $route);
        }
    }
    
    /**
     * dump_routes
     * dump the defined routes trees for debugging
     * @return void
     */
    public static function dump_routes() : void {
        foreach (self::$routes as $method => $tree) {
            print PHP_EOL."Method: ".$method;
            print PHP_EOL.str_repeat("-", 80).PHP_EOL;
            print $tree.PHP_EOL;
        }
    }
    
    /**
     * negotiate_accept
     * negotiate the accept header
     * @param  Route $route
     * @return ?string null if no match
     */
    private static function negotiate_accept(Route $route) : ?string {
        return $route->negotiate_accept();
    }
    
    /**
     * load
     *
     * @param  ?RouteRequest $request null when current request should be used
     * @return Http\Response
     */
    public static function load(?Http\RouteRequest $request = null) : Http\Response {
        $request = $request ?? self::$request;
        try {
            //Check that the method is supported:
            $method = strtoupper($request->getMethod());
            if (!array_key_exists($method, self::$routes)) {
                throw new \Exception("Request method not supported", 404);
            }
            //Get the route & evaluate it:
            [$branch, $con] = self::$routes[$method]->eval($request->getPath());
            if (is_null($branch)) {
                throw new \Exception("Not Found", 404);
            }
            //Negotiate the accept type:
            $accept = self::negotiate_accept($branch->exec) ?? "";
            $request->expects = $accept;
            if (empty($accept)) {
                throw new \Exception("No Supported Acceptable Content Type Found", 406);
            }
            //Merge context:
            $branch->exec->context = array_merge($branch->exec->context, $con);
            //Execute the route:
            return $branch->exec->exec($request);
            
        } catch(Throwable $e) {
            return self::error(
                request : $request, 
                code : $e->getCode(), 
                message : $e->getMessage(),
                line : $e->getLine(),
                file : $e->getFile(), 
                trace : $e->getTraceAsString()
            );
        }
    } 
        
    /**
     * error
     * handle an error and return a response for it
     * @param  Http\RouteRequest $request
     * @param  int $code
     * @param  string $message
     * @param  string $trace
     * @return Http\Response
     */
    public static function error(
        Http\RouteRequest $request, 
        int     $code, 
        string  $message    = "",
        int     $line       = 0,
        string  $file       = "",  
        string  $trace      = ""
    ) : Http\Response {
        if (array_key_exists($code, self::$errors)) {
            self::$errors[$code]->context["code"] = $code;
            self::$errors[$code]->context["line"] = $line;
            self::$errors[$code]->context["file"] = $file;
            self::$errors[$code]->context["message"] = $message;
            self::$errors[$code]->context["trace"] = $trace;
            $request->expects = self::negotiate_accept(self::$errors[$code], $request) ?? self::$errors[$code]->get_default_return();
            return self::$errors[$code]->exec($request);
        }
        if (array_key_exists("any", self::$errors)) {
            self::$errors["any"]->context["code"] = $code;
            self::$errors["any"]->context["line"] = $line;
            self::$errors["any"]->context["file"] = $file;
            self::$errors["any"]->context["message"] = $message;
            self::$errors["any"]->context["trace"] = $trace;
            $request->expects = self::negotiate_accept(self::$errors["any"], $request) ?? self::$errors["any"]->get_default_return();
            return self::$errors["any"]->exec($request);
        }

        //Default error handler:
        var_dump($code);
        return new Http\Response(
            status :    $code, 
            headers :   [], 
            body : sprintf("Error %d: %s \nFile : %s \nLine : %d", $code, $message, $file, $line)
        );
    }


    /**
     * Sends the HTTP response back to a HTTP client.
     *
     * This calls php's header() function and streams the body to php://output.
     * inspired by => https://github.com/sabre-io/http/blob/master/lib/Sapi.php
     */
    public static function send_response(Http\Response $response): void {
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
     * This static method will create a new Request object, based on a PHP
     * $_SERVER array.
     * REQUEST_URI and REQUEST_METHOD are required.
     * @param array<string, string> $serverArray
     */
    public static function createFromServerArray(array $serverArray) : Http\Request
    {

        $headers        = [];
        $method         = null;
        $url            = null;
        $httpVersion    = '1.1';
        $protocol       = 'http';
        $hostName       = 'localhost';

        foreach ($serverArray as $key => $value) {
            $key = (string) $key;
            switch ($key) {
                case 'SERVER_PROTOCOL':
                    if ('HTTP/1.0' === $value) {
                        $httpVersion = '1.0';
                    } elseif ('HTTP/2.0' === $value) {
                        $httpVersion = '2.0';
                    }
                    break;
                case 'REQUEST_METHOD':
                    $method = $value;
                    break;
                case 'REQUEST_URI':
                    $url = $value;
                    break;
                    // These sometimes show up without a HTTP_ prefix
                case 'CONTENT_TYPE':
                    $headers['Content-Type'] = $value;
                    break;
                case 'CONTENT_LENGTH':
                    $headers['Content-Length'] = $value;
                    break;
                    // mod_php on apache will put credentials in these variables.
                    // (fast)cgi does not usually do this, however.
                case 'PHP_AUTH_USER':
                    if (isset($serverArray['PHP_AUTH_PW'])) {
                        $headers['Authorization'] = 'Basic '.base64_encode($value.':'.$serverArray['PHP_AUTH_PW']);
                    }
                    break;
                    // Similarly, mod_php may also screw around with digest auth.
                case 'PHP_AUTH_DIGEST':
                    $headers['Authorization'] = 'Digest '.$value;
                    break;
                    // Apache may prefix the HTTP_AUTHORIZATION header with
                    // REDIRECT_, if mod_rewrite was used.
                case 'REDIRECT_HTTP_AUTHORIZATION':
                    $headers['Authorization'] = $value;
                    break;

                case 'HTTP_HOST':
                    $hostName = $value;
                    $headers['Host'] = $value;
                    break;
                case 'HTTPS':
                    if (!empty($value) && 'off' !== $value) {
                        $protocol = 'https';
                    }
                    break;
                default:
                    if ('HTTP_' === substr($key, 0, 5)) {
                        // It's a HTTP header
                        // Normalizing it to be prettier
                        $header = strtolower(substr($key, 5));
                        // Transforming dashes into spaces, and upper-casing
                        // every first letter.
                        $header = ucwords(str_replace('_', ' ', $header));
                        // Turning spaces into dashes.
                        $header = str_replace(' ', '-', $header);
                        $headers[$header] = $value;
                    }
                    break;
            }
        }

        if (null === $url) {
            throw new \InvalidArgumentException('The _SERVER array must have a REQUEST_URI key');
        }

        if (null === $method) {
            throw new \InvalidArgumentException('The _SERVER array must have a REQUEST_METHOD key');
        }
        $r = new Http\Request($method, $url, $headers);
        $r->setHttpVersion($httpVersion);
        $r->setRawServerData($serverArray);
        $r->setAbsoluteUrl($protocol.'://'.$hostName.$url);

        return $r;
    }
}