<?php

namespace Frigate\Routing\Http;

enum Methods: string {
    case GET = "GET";
    case POST = "POST";
    case PUT = "PUT";
    case DELETE = "DELETE";
    case PATCH = "PATCH";
    case OPTIONS = "OPTIONS";
    case HEAD = "HEAD";
    case TRACE = "TRACE";
    case CONNECT = "CONNECT";

    /**
     * Builds a Methods enum from a string.
     * @throws \InvalidArgumentException
     */
    public static function fromString(string $method): Methods {
        return match(strtoupper($method)) {
            "GET"       => self::GET,
            "POST"      => self::POST,
            "PUT"       => self::PUT,
            "DELETE"    => self::DELETE,
            "PATCH"     => self::PATCH,
            "OPTIONS"   => self::OPTIONS,
            "HEAD"      => self::HEAD,
            "TRACE"     => self::TRACE,
            "CONNECT"   => self::CONNECT,
            // TODO: Proper error handling
            default     => throw new \InvalidArgumentException("Invalid HTTP Method: $method")
        };
    }
}