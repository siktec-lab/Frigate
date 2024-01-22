<?php 

/**
 * ErrorEndPoint
 * this endpoint class implementation is used to handle errors
 * it is used to return a nice error page to the user or a json error message
 * depending on the request header. 
 * use this class to handle errors in your application - if you want you can use it as a template
 * to create your own error handling endpoint.
 */

declare(strict_types=1);

namespace Frigate\Api;

use Frigate\Routing\Http;

class ErrorEndPoint extends EndPoint { 

    public function __construct(bool $debug = false, $auth = false, $auth_method = "basic")
    {
        parent::__construct($debug, $auth, $auth_method);
    }

    public function call(array $context, Http\RequestInterface $request) : Http\Response {

        //Check what is the expected data to be returned?
        $return_type = $request->expects;
        $json = $return_type === "application/json";

        //The code Status:
        $code = in_array($context["code"], [400, 401, 403, 404, 405, 406, 500]) ? $context["code"] : 500;

        //The message:
        $message = $context["message"] ?? "Unknown Error";
        $file = $context["file"] ?? "unknown";
        $line = $context["line"] ?? 0;

        $error_template = 
            "<h1>Error : %d</h1></br>\n<h2>Message: %s</h2></br>\n<strong>File: %s -> Line : %d</strong>";

        $json_payload = [
            "code"      => $code,
            "message"   => $message
        ];
        // Only add the trace if we are in debug mode:
        if ($this->debug) {
            $json_payload["file"] = $file;
            $json_payload["line"] = $line;
        }
        //The body:
        switch ($code) {
            case 400:
            case 401:
            case 403:
            case 404:
            case 405:
                //"Method Not Allowed";
                $body = $json ? json_encode($json_payload) 
                              : sprintf($error_template, $code, $message, $file, $line);
                break;

            case 406:
                //"Not Acceptable";
                $body = $json ? json_encode($json_payload) 
                              : sprintf($error_template, $code, $message, $file, $line);
                break;
                
            case 500:
                if (!$this->debug) {
                    $body = $json ? json_encode(["code" => 500, "error" => "Internal Server Error"]) 
                                  : "Internal Server Error";
                } else {
                    $json_payload["trace"] = $context["trace"] ?? "No trace";
                    $body = $json ? json_encode($json_payload) 
                                  : sprintf($error_template, $code, $message, $file, $line);
                }
                break;
            default:
                if (!$this->debug) {
                    $body = $json ? json_encode(["error" => "Unknown Error"]) 
                                  : "Unknown Error";
                } else {
                    $json_payload["trace"] = $context["trace"] ?? "No trace";
                    $body = $json ? json_encode($json_payload) 
                                : sprintf($error_template, $code, $message, $file, $line);
                }
                break;
        }

        $response = new Http\Response(
            status      : $json ? $code : 200,
            headers     : [ "Content-Type" => $return_type ],
            body        : $body
        );

        return $response;
    }

}