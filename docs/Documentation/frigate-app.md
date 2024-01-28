# Frigate Application

At the heart of every Frigate application is the Frigate application instance. The application instance is a static class that is responsible for bootstrapping the application, to normalize and make several features available to the entire Frigate application.

The application instance is responsible for:

- Parsing environment variables and making them available to the application.
- Defining some default global constants.
- Adjusting the php configuration for the runtime environment (if needed).
- Starting a session (if needed).

While its possible to use Frigate without initializing the application instance, it is recommended to initialize the application instance as soon as possible.
The application instance is initialized by calling the `init` method:

```php
<?php

// index.php

// ... other code

use Frigate\FrigateApp as App;

// Initialize the application instance.
App::init(
    root              : __DIR__, // will set the root folder of the application. (string)
    env               : null,   // will load the environment variables from the .env file default is null. (array|string|null)
    extra_env         : [],     // will add additional environment variables to the application. (array)
    load_session      : true,   // will load the session if needed. (bool) default is true. 
    start_page_buffer : false,  // will start the page buffer if needed. (bool) default is false.
    adjust_ini        : true    // will adjust the php configuration if needed. (bool) default is true
);

// ... other code
```

Once the application instance is initialized accross the application we can access the application instance using the `#!js FrigateApp` class
To access different features of the application instance.

## Environment variables

Each Frigate application has a set of environment variables that are used to configure the application.
Those are required for the application to work correctly and are loaded from the `.env` file by default.

1. Load the environment variables from root folder `.env` file:
    ```php
    <?php
    
    use Frigate\FrigateApp as App;
    // will load the environment variables from the .env file.
    App::init( root : __DIR__, /* env: null */ ); // env is null by default. root will be used as the root folder.
    ```

2. Load the environment variables from a specific file:
    ```php
    <?php
    
    use Frigate\FrigateApp as App;
    // will load the environment variables from the .env file.
    App::init(
        root : __DIR__,
        env: [  __DIR__ . "/config/", ".env.local" ] // will load the .env and .env.local files from the config folder.
    );
    ```
3. Load the environment variables from several files.
    ```php
    <?php
    
    use Frigate\FrigateApp as App;
    // will load the environment variables from the .env file.
    App::init(
        root : __DIR__,
        env: [ 
            [ __DIR__ . "/config/" ], // Directories to look in.
            [ ".env", ".env.local" ]  // files to load.
        ]
    );
    ```
4. Load the environment variables from an array.
    ```php
    <?php

    use Frigate\FrigateApp as App;
    // will load the environment variables from the .env file and add the additional environment variables.
    App::init(
        root : __DIR__,
        env: __DIR__,
        extra_env: [
            "MY_ENV_VAR" => "my value"
        ]
    );
    ```

### Required Env variables

- `#!js FRIGATE_ROOT_FOLDER` *string* - the root folder of the application. empty string by default ('/'). (1)
{ .annotate }

    1.  The root folder is the folder that contains the `index.php` in relation to the web root folder.

- `#!js FRIGATE_BASE_URL` *string* - the base URL of the application. e.g. `#!js "http://example.com/"`. (1)
{ .annotate }

    1.  The base URL is used to generate the URLs of the application. the base URL should not include the URI path. 
    for example, if the application is located at `http://example.com/my-app/` the base URL should be `http://example.com/` and the root folder should be `my-app`. 

- `#!js FRIGATE_APP_VERSION` *string* - the version of YOUR application.
- `#!js FRIGATE_DEBUG_ROUTER` *boolean* - enable debug mode for the router.
- `#!js FRIGATE_DEBUG_ENDPOINTS` *boolean* - enable debug mode for the endpoints.
- `#!js FRIGATE_DEBUG_TO_FILE` *boolean* - enable debug mode for the endpoints.
- `#!js FRIGATE_DEBUG_FILE_PATH` *string* - the path to the debug log file. (1)
{ .annotate }

    1.  The debug log file will contain all debug messages from the router and the endpoints.

- `#!js FRIGATE_EXPOSE_ERRORS` *boolean* - enable error reporting will expose errors to the client.
- `#!js FRIGATE_ERRORS_TO_FILE` *boolean* - enable error reporting will log errors to the file.
- `#!js FRIGATE_ERRORS_FILE_PATH` *string* - the path to the error log file. (1)
{ .annotate }

    1.  The error log file will contain all errors from the router and the endpoints.

??? warning "Will throw an exception if not defined"
    The application will throw an exception if any of the required environment variables are not defined. or are not valid.
    This also applies to your own environment variables which are defined by extending the `FrigateApp` class.

### Using Env variables

After initializing the application instance, all environment variables are available in `$_ENV` and `$_SERVER` super globals.
For a convenient way to access the environment variables, the application instance provides some helper methods that serialize the environment variables into a specific type.

```php
<?php

// ... other code

use Frigate\FrigateApp as App;

App::init( root: __DIR__ );

$root_folder = App::ENV_STR("FRIGATE_ROOT_FOLDER");
$debug_router = App::ENV_BOOL("FRIGATE_DEBUG_ROUTER");
$debug_endpoints = App::ENV_BOOL("FRIGATE_DEBUG_ENDPOINTS");

// Assuming the following optional environment variables are defined and are OPTIONAL:
// MY_ENV_NUMBER = 123
// MY_ENV_FLOAT = 123.456
$my_env_number = App::ENV_INT("MY_ENV_NUMBER", 0); // 123 or 0 if not defined.
$my_env_float = App::ENV_FLOAT("MY_ENV_FLOAT", 0.0); // 123.456 or 0.0 if not defined.

// ... other code
```
!!! tip "More about environment variables" 
    1. All environment are uppercased.
    2. All Frigate environment variables are prefixed with `FRIGATE_`.
    3. All `ENV_*` methods are case insensitive and return the `null` value if the environment variable is not defined by default.


### Custom Env variables

You can leverage the environment variables functionality to define your own environment **REQUIRED** variables. This is useful if you want to define some configuration variables for your application and properly handle them and validate them.

This can be achieved in to ways:

1. Extending the `FrigateApp` class and define the `application_env` array.
    ```php
    <?php
        // MyApplication.php
        use Frigate\FrigateApp as App;

        // Extend the FrigateApp class.
        class MyApplication extends App {
            public static array $application_env = [
                "MY_ENV_VAR"    => "not-empty",
                "MY_ENV_VAR2"   => "string",
                "MY_ENV_VAR3"   => "int",
                "MY_ENV_VAR4"   => "bool"
            ];
        }

        // index.php
        use MyApplication as App;

        App::init( root: __DIR__ ); // All New environment variables will be required and validated.
    ```

2. Adding the environment variables to the `application_env` array of the application instance.
    ```php
    <?php

        // index.php
        use Frigate\FrigateApp as App;

        App::$application_env = [
            "MY_ENV_VAR"    => "not-empty",
            "MY_ENV_VAR2"   => "string",
            "MY_ENV_VAR3"   => "int",
            "MY_ENV_VAR4"   => "bool"
        ];

        App::init( root: __DIR__ ); // All New environment variables will be required and validated.

    ```

??? note "You don't need that for your optional environment variables"
    You don't need to define your optional environment variables in the `application_env` array. You can simply use the `ENV_*` methods and provide a default value. Any environment that is defined in the `.env` file will be available in the `$_ENV` and `$_SERVER` super globals.


## Frigate Constants

After initializing the application instance, several constants are defined by the application instance and are available throughout the application.

Table:

| Constant | Description |
| -------- | ----------- |
| `APP_ROOT_FOLDER` | The root folder of the application. |
| `APP_BASE_URL_PATH` | The base url path of the application. |
| `APP_BASE_URL` | The base url of the application. |
| `APP_VERSION` | The version of the application. |
| `APP_DEBUG_ROUTER` | The debug mode of the router. |
| `APP_DEBUG_ENDPOINTS` | The debug mode of the endpoints. |

## PHP Configuration

## Session

## Page Buffer