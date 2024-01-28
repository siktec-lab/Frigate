<?php 

declare(strict_types=1);

namespace Frigate;

use Dotenv\Dotenv;
use Exception;
use Frigate\Exceptions\FrigateException;

class FrigateApp {

    /** 
     * Required environment variables
     * @var array<string,string>
    */
    public const REQUIRED_ENV = [
        'FRIGATE_ROOT_FOLDER'       => "string",
        'FRIGATE_BASE_URL'          => "not-empty",
        'FRIGATE_APP_VERSION'       => "string",
        'FRIGATE_DEBUG_ROUTER'      => "bool",
        'FRIGATE_DEBUG_ENDPOINTS'   => "bool",
        'FRIGATE_DEBUG_TO_FILE'     => "bool",
        'FRIGATE_DEBUG_FILE_PATH'   => "string",
        'FRIGATE_EXPOSE_ERRORS'     => "bool",
        'FRIGATE_ERRORS_TO_FILE'    => "bool",
        'FRIGATE_ERRORS_FILE_PATH'  => "string"
    ];

    /** 
     * Implementation version:
    */
    static public string $version = "1.0.0";

    /** 
     * Environment variables:
     */
    static public ?Dotenv $env = null;

    /**
     * Application required environment variables:
     * @var array<string,string>
     */
    public static array $application_env = [];

    /** 
     * Loaded globals:
     * @var array<string,mixed>
     */
    static public array $globals = [];

    /**
     * Initialize the application
     * 
     * @param string $root - application root path
     * @param string|array $env - path to config file or array of config values
     * @param array $extra_env - extra environment variables
     * @param bool $load_session - start session
     * @param bool $start_page_buffer - start page buffer
     * @param bool|null $adjust_ini - load ini configuration
     * 
     * @return void
     */
    public static function init(
        string $root,
        string|array|null $env  = null,
        array $extra_env        = [],
        bool $load_session      = true, 
        bool $start_page_buffer = false,
        ?bool $adjust_ini       = true
    ) : void {

        // Load environment variables:
        self::loadEnvironment($env ?? $root, $extra_env);

        // Set paths:
        self::setPaths(
            root: $root,
            base_path: $_ENV["FRIGATE_ROOT_FOLDER"] ?: "/",
            app_url: $_ENV["FRIGATE_BASE_URL"]
        );

        // Adjust ini settings:
        if ($adjust_ini) {
            self::adjustIni();
        }
        //start session:
        if ($load_session) {
            self::startSession();
        }
        //start page buffer:
        if ($start_page_buffer) {
            self::startPageBuffer();
        }
    }

    public static function adjustIni() : void {
        
        // Application version:
        if (!defined("APP_VERSION")) {
            define("APP_VERSION", self::$version);
        }
        self::$version = APP_VERSION;

        // Error log:
        if (!defined("APP_ERROR_LOG")) {
            define("APP_ERROR_LOG", false);
        }
        if (APP_ERROR_LOG) {
            ini_set("log_errors", true);
            ini_set("error_log", APP_ERROR_LOG);
        }

        // Error reporting:
        if (!defined("SHOW_ERRORS")) {
            define("SHOW_ERRORS", false);
        }
        
        error_reporting(SHOW_ERRORS ? -1 : 0);
        ini_set('display_errors', SHOW_ERRORS ? 'on' : 'off');

        // Save Globals:
        self::$globals["APP_VERSION"]   = APP_VERSION;
        self::$globals["APP_ERROR_LOG"] = APP_ERROR_LOG;
        self::$globals["SHOW_ERRORS"]   = SHOW_ERRORS;
    }

    public static function startSession() : bool {
        if (session_status() === PHP_SESSION_NONE) {
            return session_start();
        }
        return true;
    }

    public static function setPaths(string $root, string $base_path = "/", $app_url = "http://localhost/") : void {

        //Directory separator
        if (!defined("DS")) 
            define("DS", DIRECTORY_SEPARATOR);

        //Application root path
        if (!defined("APP_ROOT")) 
            define("APP_ROOT", $root);
        
        //Application vendor path
        if (!defined("APP_VENDOR")) 
            define("APP_VENDOR", APP_ROOT.DS."vendor");
        
        //Application base path - for applications that are not in the root directory
        if (!defined("APP_BASE_OS_PATH")) 
            define("APP_BASE_OS_PATH", $base_path);

        //Application base url path - for applications that are not in the root directory        
        if (!defined("APP_BASE_URL_PATH")) {
            $base_path = trim(rtrim($base_path, " \n\t\r\0\x0B/\\")) . "/";
            define("APP_BASE_URL_PATH", $base_path);
        }

        //Application base url: domain + base url path
        if (!defined("APP_BASE_URL")) {
            //normalize the app url make sure it ends with a slash
            $app_url = trim(rtrim($app_url, " \n\t\r\0\x0B/\\")) . "/";
            //join the app url with the base path to get the base url make sure no extra slashes are added
            define("APP_BASE_URL", $app_url . ltrim(APP_BASE_URL_PATH, " \n\t\r\0\x0B/\\"));
        }
        
        //Save Globals:
        self::$globals["APP_ROOT"]         = APP_ROOT;
        self::$globals["APP_VENDOR"]       = APP_VENDOR;
        self::$globals["APP_BASE_OS_PATH"] = APP_BASE_OS_PATH;
        self::$globals["APP_BASE_URL_PATH"]= APP_BASE_URL_PATH;
        self::$globals["APP_BASE_URL"]     = APP_BASE_URL;
        
    }

    static public function startPageBuffer() : bool {
        return ob_start();
    }

    static public function endPageBuffer() : string {
        return ob_get_clean();
    }

    static public function debug(mixed $from, string $message, mixed $variable = null) {
        if (
            ( is_string($from) && class_exists($from) && property_exists($from, 'debug') && $from::$debug)
            ||
            ( is_object($from) && property_exists($from, 'debug') && $from->debug )
        ) {
            $name = is_object($from) ? get_class($from) : $from;
            print "****************************************".PHP_EOL;
            print "- FROM : {$name}".PHP_EOL;
            print "----------------------------------------".PHP_EOL;
            print "- {$message}".PHP_EOL;
            print "----------------------------------------".PHP_EOL;
            print_r($variable);
            print PHP_EOL."****************************************".PHP_EOL;
        }
    }

    static public function loadEnvironment(string|array|null $path = null, array $extra) : bool {

        
        if (is_string($path)) {
            $path = [ $path ];
        }
        if (is_null($path)) {
            $path = [[]];
        }

        // Load environment variables:
        if (is_null(self::$env)) {
            try {
                [$dirs, $files] = $path + [[], null];
                var_dump($dirs, $files);
                self::$env = Dotenv::createImmutable($dirs, $files, false);
                self::$env->safeLoad();

                // Load extra environment variables:
                foreach ($extra as $key => $value) {
                    $_ENV[strtoupper($key)] = $value;
                    $_SERVER[strtoupper($key)] = $value;
                }

                // Validate required environment variables:
                self::validateEnv(self::REQUIRED_ENV);

                // Validate application environment variables:
                self::validateEnv(self::$application_env);

            } catch (Exception $e) {
                throw new FrigateException(
                    FrigateException::CODE_FRIGATE_ENV_ERROR,
                    [$e->getMessage()],
                    $e
                );
            }
            return true;    
        }
        return false;
    }
    
    /**
     * Validate required environment variables
     * 
     * @param array<string,string> $rules
     * @throws \Dotenv\Exception\ValidationException
     */
    static protected function validateEnv(array $rules) : void {
        foreach ($rules as $key => $type) {
            $validate = self::$env->required($key);
            switch ($type) {
                case "bool":
                    $validate->isBoolean();
                    break;
                case "string":
                    $validate->required();
                    break;
                case "int":
                    $validate->isInteger();
                    break;
                case "not-empty":
                    $validate->notEmpty();
                    break;
                default:
                    $validate->required();
            }
        }
    }

    static public function ENV_BOOL(string $key, ?bool $default = null) : ?bool {
        return array_key_exists($key, $_ENV) 
            ? filter_var($_ENV[$key], FILTER_VALIDATE_BOOLEAN) 
            : $default;
    }

    static public function ENV_STR(string $key, ?string $default = null) : ?string {
        return array_key_exists($key, $_ENV) 
            ? strval($_ENV[$key])
            : $default;
    }

    static public function ENV_INT(string $key, ?int $default = null) : ?int {
        return array_key_exists($key, $_ENV) 
            ? intval($_ENV[$key])
            : $default;
    }

    static public function ENV_FLOAT(string $key, ?float $default = null) : ?float {
        return array_key_exists($key, $_ENV) 
            ? floatval($_ENV[$key])
            : $default;
    }

}