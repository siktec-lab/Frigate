# Middleware

Frigate has a powerful middleware system that allows you to execute code before and after a route is executed. Middleware can be used to modify the request, modify the response, or to execute additional logic before the route is executed. Middleware can be attached to a route or to a group of routes. Middleware can be used to implement authentication, logging, rate limiting, caching, and any other logic.

Each middleware is a class that implements the `:::php <? MiddlewareInterface` interface OR a class that extends the `:::php <? BaseMiddleware` class. Each middleware class should implement the `:::php <? exec` method that takes:

- `:::php <? Methods $method` - **immutable** the HTTP method of the route.
- `:::php <? RequestInterface $request` - **mutable** the request object.
- `:::php <? ResponseInterface $response` - **mutable** the response object.
- `:::php <? array $context` - **mutable** the context of the route.
- `:::php <? Route $route` - **immutable** the final route object.

The `:::php <? exec` return a boolean value indicating wether execution should continue or not. It should modify the request, response, context to pass data between middleware modules and the target route.

## Basic Middleware

Here is an example of a basic middleware that modifies the context of the route:

```php
<?php

use Frigate\Middleware\BaseMiddleware;
use Frigate\Routing\Http\RequestInterface;
use Frigate\Routing\Http\ResponseInterface;
use Frigate\Routing\Routes\Route;

class MyMiddleware extends BaseMiddleware 
{
    public function exec(               // The only required method to implement
        Methods $method,                // The HTTP method of the route
        RequestInterface &$request,     // The request object which will be passed on. 
        ResponseInterface &$response,   // The response object which will be passed on.
        array &$context,                // The context of the route
        Route $target_route             // The final route object
    ) : bool {

        // Middleware logic goes here:
        $context['my_middleware'] = 'I am a middleware';

        // Return true to continue the execution of the route.
        // Return false to stop the execution of the route and return the response.
        return true; 
    }
}
```

After a middleware is executed, the context, request, and response objects are passed on to the next middleware or to the route handler. In case a middleware should stop the execution of the route, it can do so in two ways:

- By returning the `:::php <? False` value which will stop the execution of the route and return the response.
- By throwing an `:::php <? Exception` with the relevant error message and status code.

```php
<?php

    // ... Middleware exec body

    // Example of a middleware that stops the execution of the route.
    $response->setStatus(401, "Unauthorized"); 
    return false;
    
    //Or:
    $response->setStatus(401); // Status message will be automatically set based on the status code.
    return false;
    
    //Or:
    throw new Exception("Unauthorized", 401);
    
    // ... Middleware exec body
```

