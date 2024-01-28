<?php 

namespace Frigate\Api;
     
use Frigate\Application;
use Frigate\Routing\Http;

abstract class EndPoint implements EndPointInterface 
{ 
    public bool $debug                  = false;
    protected bool $authorize             = false;
    protected string $authorize_method    = "basic";

    public function __construct(bool $debug = false, $auth = false, $auth_method = "basic")
    {
        $this->debug = $debug;
        $this->authorize = $auth;
        $this->authorize_method = $auth_method;
    }

    abstract public function call(array $context, Http\RequestInterface $request) : Http\Response;

}