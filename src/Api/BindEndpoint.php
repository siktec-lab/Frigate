<?php 

namespace Siktec\Frigate\Api;

/**
 * api endpoint binding
 * this class is used to bind an endpoint class to a route
 * it is used to lazy load the endpoint class very performant in large applications
 * @package Siktec\Frigate\Api
 */

class BindEndpoint
{ 

    private string            $class             = "";  
    private ?\ReflectionClass $endpoint          = null;
    private bool              $debug             = false;
    private bool              $authorize         = false;
    private string            $authorize_method  = "basic";
    
    /**
     * __construct
     * create a new endpoint binding
     * @param  string $endpoint
     * @param  bool $debug
     * @param  bool $auth
     * @param  mixed $auth_method
     * @return self
     */
    public function __construct(string $endpoint, bool $debug = false, bool $auth = false, $auth_method = "basic")
    {
        $this->class            = $endpoint;
        $this->debug            = $debug;
        $this->authorize        = $auth;
        $this->authorize_method = $auth_method;
    }
    
    /**
     * get_instance
     * returns an instance of the endpoint class - this is a lazy load
     * @throws \Exception if the endpoint class could not be instantiated will trigger a 500 error
     * 
     * @param  ?bool $debug Override debug setting
     * @param  ?bool $auth Override auth setting
     * @param  mixed $auth_method Override auth method
     * @return EndPointInterface
     */
    public function getInstance(
        ?bool $debug = null,
        ?bool $auth = null,
        $auth_method = null
    ) : EndPointInterface
    {   
        try {
            if ($this->endpoint === null) {
                $this->endpoint = new \ReflectionClass($this->class);
            }
            return $this->endpoint->newInstance(
                is_null($debug) ? $this->debug : $debug, 
                is_null($auth) ? $this->authorize : $auth,
                is_null($auth_method) ? $this->authorize_method : $auth_method
            );
        } catch (\ReflectionException $e) {
            throw new \Exception("Could not create endpoint instance: " . $e->getMessage(), 500);
        }
    }
}