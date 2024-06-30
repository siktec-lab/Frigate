<?php 

namespace Frigate\Api;

use Frigate\Api\EndPointInterface;
use ReflectionClass;

/**
 * api endpoint binding
 * this class is used to bind an endpoint class to a route
 * it is used to lazy load the endpoint class very performant in large applications
 */
class BindEndpoint
{ 

    private string            $class            = "";  
    private ?ReflectionClass  $endpoint         = null;
    private array             $args             = [];
    
    /**
     * __construct
     * create a new endpoint binding
     */
    public function __construct(string $endpoint, array $args = [])
    {
        $this->class = $endpoint;
        $this->args = $args;
    }
    
    /**
     * get_instance
     * returns an instance of the endpoint class - this is a lazy load
     * @throws \Exception if the endpoint class could not be instantiated will trigger a 500 error
     * @return EndPointInterface
     */
    public function getInstance() : EndPointInterface
    {   
        try {
            if ($this->endpoint === null) {
                $this->endpoint = new \ReflectionClass($this->class);
            }
            return $this->endpoint->newInstance(...$this->args);
        } catch (\ReflectionException $e) {
            throw new \Exception("Could not create endpoint instance: " . $e->getMessage(), 500);
        }
    }
}