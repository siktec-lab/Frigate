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

## Defining Routes

## Path Syntax

The route path is the URI that the router will match against.
The route path can contain path parameters, and path parameters can have a type:

- `#!js "/"` - will match the root path.
- `#!js "users"` - will match the path "/users"
- `#!js "users/{id}"` - will match the path "/users/1" and "/users/2" etc...
- `#!js "users/{id:int}"` - will match the path "/users/1" and "/users/2" and will place the id in the context as an integer.

!!! note
    1. The path is case insensitive. This means that the path `/users` will match `/Users` and `/USERS` etc...
    2. When defining the **same path** with the **same method** twice, the last definition will **override** the previous one.

### Path parameters

Path parameters are defined by wrapping the parameter name with curly brackets `{}`.
The parameter name can be any alphanumeric string and can contain underscores `_`. It is recommended to use a descriptive name.

The parameter name can also contain a type. The type is used to convert the path parameter to a specific type in the context.

- `int, integer` - will convert to an integer.
- `float, double` - will convert to a float.
- `bool, boolean` - will convert to a boolean. (1)
{ .annotate }
    1.  The boolean type will convert the following values to true: `1, true, on, yes` and the following values to false: `0, false, off, no`.

- `string, str` - will match any string. Which is the default type.
- `path` - will be a string containing the rest of the path.

The way we define the type is by adding a colon `:` after the parameter name and then the type name:

- `#!js "users/{id:int}"` - id will be an `int`.
- `#!js "users/{height:float}"` - height will be a `float`.
- `#!js "users/{is_active:bool}"` - is_active will be a `bool`.
- `#!js "users/{name}"` - name will be a `string`.
- `#!js "users/{name:string}/{id:int}/{action}"` - we can mix types and non types.
- `#!js "users/{storage:path}"` - storage will be a `string` containing the rest of the path.

!!! warning
    1. An exception will be thrown if the several path parameters are defined on the same level.
        - i.e. defining `#!js "users/{id:int}"` and `#!js "users/{name:string}"` will throw an exception.
    2. The `path` parameter cannot be extended with other parameters.
        - i.e. `#!js "users/{storage:path}/{id:int}"` will throw an exception.

### Variation Macro

In Frigate we can define multiple levels of a path with the variation macro `?`. The variation macro will expand to a several paths:

- `#!js "users/storage/?{find}/?{term}"` - will expand to:
    - `#!js "users/storage/"`
    - `#!js "users/storage/{find}/"`
    - `#!js "users/storage/{find}/{term}"`

Obviously, this can be done manually but it is a lot easier to use the variation macro.
Also, the variation macro can be used to mimic default values in path parameters. For example:

```php
<?php
// ...

Router::define(Methods::GET, new Route("users/storage/?find/?{term}",
    context : [
        "find" => "all",
        "term" => "avatar.png",
    ],
    // ... rest of the route definition
));
```

All the expanded paths will be matched and the context will be merged with the context defined in the route definition. This behavior will result in 3 paths that points to the same route and have "default" values for the `find` and `term` parameters.

- `#!js "users/storage/"` - will have the context: `["find" => "all", "term" => "avatar.png"]`
- `#!js "users/storage/picture/"` - will have the context: `["find" => "picture", "term" => "avatar.png"]`
- `#!js "users/storage/picture/profie.png"` - will have the context: `["find" => "picture", "term" => "profie.png"]`


### Default parameter values

Default values are not supported in path parameters. They don't make sense in the context of a path parameter. Some frameworks support default values (sort of) by looking ahead in the path and matching the next path part. This is not supported in Frigate.

The best way to handle this is to define several paths that point to the same route and have different levels of path parameters.
This can be done easily with the variation macro `?` as [described above](#variation-macro).

!!! note
    The reason for this is that it is not clear what the default value should be. For example, if we have the following path: `#!js "users/{id:int}/{action}"` and we want to set the default value of `action` to `view`. What should happen if the path is `/users/1`? Should the default value be set to `view` or should the path not match? This is not clear and can lead to unexpected behavior.

### Shadow path markers

In Frigate shadow paths are defined by adding a `^` after the path. Internally, the shadow path marker is used to attach
expressions that will be executed when the this marker is reached. For example:

- `#!js "/users^"` - a any path that starts with `/users` will invoke the attached expressions before the route is executed.
- `#!js "/users^/{id:int}"` - when executing the route `/users/1` the attached expressions will be executed before the route is executed, passing the modified context to the route.
- `#!js "/users^/{id:int}^/profile"` - shadow paths can be chained together. when executing the route `/users/1/profile` the shadow expressions will be executed twice, once for `/users/1` and once for `/users/1/profile`.

This mechanism is used to attach middleware to a route. and is described in the [middleware section](#middleware).

!!! note
    The shadow path marker is not part of the path and will not be matched. It is used to attach expressions to the path.

!!! warning
    Don't use the shadow path marker unless you know what you are doing. Frigate offers a better way to attach middleware to a route. See the [middleware section](#middleware).
