<?php 

namespace Frigate\Api;
     
use Frigate\FrigateApp;
use Frigate\Routing\Http\RequestInterface;
use Frigate\Routing\Http\ResponseInterface;

abstract class EndPoint implements EndPointInterface 
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
     * Get the debug mode flag.
     * Its an interface method which must be implemented.
     */
    public function debug() : bool {
        return $this->debug;
    }

    /**
     * Call the endpoint.
     * The actual implementation of the endpoint logic.
     */
    abstract public function call(
        array $context, 
        RequestInterface $request, 
        ResponseInterface $response
    ) : ResponseInterface;
}