<?php 

namespace Siktec\Frigate\Routing;

use \Siktec\Frigate\Base;
use \Siktec\Frigate\Routing\Http;
use \Throwable;

class Router {


    public static bool $debug = false;

    private static Http\RouteRequest $request;

    /** @var Route[] $errors*/
    private static array $errors  = [];

    /** @var Route[] $routes */
    private static array $routes  = [];
    
    public static function init(bool $debug = false) : void {
        self::$debug = $debug;
    }
    /**
     * parse_request
     * load and parses the request uri
     * @param  string $route or none for SERVER => REQUEST_URI
     * @return void
     */
    public static function parse_request(string $route = "") : void {

        $server_arr = $_SERVER;

        if ('cli' === PHP_SAPI) {
            // If we're running off the CLI, we're going to set some default settings.
            $server_arr['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
            $server_arr['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        }

        $got =  self::createFromServerArray($server_arr);
        self::$request = new Http\RouteRequest($got);
        self::$request->setBody(fopen('php://input', 'r'));
        self::$request->setPostData($_POST);
        self::$request->setRoute($_ENV["ROOT_FOLDER"]);

        Base::debug(self::class, "got request",     (string)self::$request);
        Base::debug(self::class, "request raw parts", [
            "ROUTE" => self::$request->getRoute(),
            "PATH"  => self::$request->getPath(),
            "POST"  => self::$request->getPostData(),
            "QUERY" => self::$request->getQueryParameters()
        ]);
    }
    
    public static function define_error(int|string $code, Route $route) : void {
        self::$errors[$code] = $route;
    }

    private static function route_str(string $method, string $path) : string {
        return $method . ( !empty($path) ? "::".$path : "");
    }

    public static function define(string $method, Route $route) : void {
        self::$routes[
            self::route_str($method, $route->path)
        ] = $route;
    }

    public static function route_defined(?string $method = null, ?string $path = null) : bool {
        $route = self::route_str($method ?? "", $path ?? "");
        return array_key_exists($route, self::$routes);
    }

    public static function dump_routes() : void {
        foreach (self::$routes as $key => $route) {
            echo explode("::", $key)[0]." -> ".$route.PHP_EOL;
        }
    }

    private static function negotiate_accept(Route $route, Http\RouteRequest $request) : ?string {
        $accept = $request->getAccept();
        foreach ($accept as $accept_type) {
            if ($route->supports_accept($accept_type)) {
                return $accept_type;
            }
        }
        return null;
    }

    public static function load(?Http\RouteRequest $request = null) : Http\Response {
        $request = $request ?? self::$request;
        try {
            if (self::route_defined($request->getMethod(), $request->getRoute())) {
                //The route is defined
                $route = self::$routes[self::route_str($request->getMethod(), $request->getRoute())];
                //negotiate accept
                $accept = self::negotiate_accept($route, $request) ?? "";
                $request->expects = $accept;
                if (empty($accept)) {
                    throw new \Exception("No Supported Acceptable Content Type Found", 406);
                }
                return $route->exec($request);
            } else {
                throw new \Exception("Not Found", 404);
            }
        } catch(Throwable $e) {
            return self::error($request, $e->getCode(), $e->getMessage(), $e->getTraceAsString());
        }
    } 
    
    public static function error(Http\RouteRequest $request, int $code, string $message = "", string $trace = "") : Http\Response {
        if (array_key_exists($code, self::$errors)) {
            self::$errors[$code]->context["code"] = $code;
            self::$errors[$code]->context["message"] = $message;
            self::$errors[$code]->context["trace"] = $trace;
            $request->expects = self::negotiate_accept(self::$errors[$code], $request) ?? self::$errors[$code]->get_default_return();
            return self::$errors[$code]->exec($request);
        }
        if (array_key_exists("any", self::$errors)) {
            self::$errors["any"]->context["code"] = $code;
            self::$errors["any"]->context["message"] = $message;
            self::$errors["any"]->context["trace"] = $trace;
            $request->expects = self::negotiate_accept(self::$errors["any"], $request) ?? self::$errors["any"]->get_default_return();
            return self::$errors["any"]->exec($request);
        }
        //Default error handler:
        return new Http\Response($code, [], $message);
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