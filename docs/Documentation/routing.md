# Routing

## Initialize the router

After the frigate `Base` class has been initialized, the router can be initialized.
The router is initialized with the `init` method. The `init` method takes:
- `debug` - a boolean value to enable debug mode for the router.

```php
<?php

// We use the Frigate Base class to help with environment variables.
// This is not required but it is recommended.
Router::init(debug : Base::ENV_BOOL("DEBUG_ROUTER"));

// That's it, the router is now initialized we can parse the request.
Router::parse_request(APP_BASE_URL_PATH);

```

Parsing the request will simply load the current request method in the router.

## Defining Routes:

## The Route path:

The route path is the path that the router will match against the current request path.
The route path can contain path parameters, and path parameters can have a type and a default value.

- `#!js "/"` - will match the root path.
- `#!js "users"` - will match the path "/users"
- `#!js "users/{id}"` - will match the path "/users/1" and "/users/2" etc...
- `#!js "users/{id:int}"` - will match the path "/users/1" and "/users/2" etc... but not "/users/1a"

### Path parameters:

Path parameters are defined by wrapping the parameter name with curly brackets `{}`.
The parameter name can be any string, but it is recommended to use a descriptive name.
The parameter name can also contain a type. Those types are defined by the router and are:

- `int` - will match only integers.
- `float` - will match only floats.
- `bool` - will match only boolean values.
- `path` - will be a string containing the rest of the path.
- `string` - will match any string. Which is the default type.

This types wil be enforced when the router will try to match the path parameters.
The way we define the type is by adding a colon `:` after the parameter name and then the type name:

- `#!js "users/{id:int}"` - id will be an `int`.
- `#!js "users/{height:float}"` - height will be a `float`.
- `#!js "users/{is_active:bool}"` - is_active will be a `bool`.
- `#!js "users/{name}"` - name will be a `string`.
- `#!js "users/{name:string}/{id:int}/{action}"` - we can mix types and non types.

### Path parameters default values:

Default values are not defined in the path, but in the context of the route. You can read more about the route context in the [Route Context](#route-context) section.
But in short, the route context is the context that is passed to the route handler and any path parameter will be added to the context.

```php
<?php
//TODO: add example
```



