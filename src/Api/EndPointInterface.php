<?php 

namespace Frigate\Api;

use Frigate\Routing\Http\RouteRequest;
use Frigate\Routing\Http\Response;

interface EndPointInterface
{
    public function __construct(bool $debug, bool $auth, string $auth_method);

    public function call(array $context, RouteRequest $request): Response;
}