<?php 

namespace Frigate\Api;

use Frigate\Routing\Http\RequestInterface;
use Frigate\Routing\Http\ResponseInterface;

interface EndPointInterface
{

    public function call(array $context, RequestInterface $request, ResponseInterface $response): ResponseInterface;

    public function debug() : bool;

}