<?php 

namespace Frigate\Api;

use Frigate\Routing\Http\RequestInterface;
use Frigate\Routing\Http\Response;

interface EndPointInterface
{

    public function __construct(?bool $debug = null);

    public function call(array $context, RequestInterface $request, Response $response): Response;

    public function debug() : bool;

}