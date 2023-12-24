<?php 

namespace Siktec\Frigate;

use \Siktec\Frigate\DataBase\MysqliDb;
use \Dotenv\Dotenv;

class Base {

    static public string $version = "1.0.0";

    static public ?Dotenv $env = null;

    static public ?MysqliDb $db = null;

    static public array $globals = [];

    public static function init(
        string $config, 
        bool $connect = true, 
        bool $session = true, 
        bool $page_buffer = false,
        ?bool $load_ini = true
    ) : void {

        if ($load_ini) {
            self::load_ini();
        }

        self::load_environment($config);
        
        //connect to database:
        if ($connect) {
            self::connect_database();
        }

        //start session:
        if ($session) {
            self::start_session();
        }

        //start page buffer:
        if ($page_buffer) {
            self::start_page_buffer();
        }
    }

    public static function load_ini() : void {
        
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

    public static function start_session() : bool {
        if (session_status() === PHP_SESSION_NONE) {
            return session_start();
        }
        return true;
    }

    public static function set_paths(string $root, string $base_path = "/", $app_url = "http://localhost/") : void {

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

    static public function connect_database() {
        try {
            self::$db = new MysqliDb(
                $_ENV["DB_HOST"],
                $_ENV["DB_USER"],
                $_ENV["DB_PASS"],
                $_ENV["DB_NAME"]
            );
            if (!self::$db->ping()) {
                throw new \Exception("Cannot ping the database");
            }
        } catch (\Throwable $e) {
            die($e->getMessage());
        }
    }

    static public function start_page_buffer() : bool {
        return ob_start();
    }

    static public function end_page_buffer() : string {
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

    static public function load_environment(string $path = "") : bool {
        if (is_null(self::$env)) {
            try {
                self::$env = Dotenv::createImmutable($path);
                self::$env->safeLoad();
                self::$env->required([
                    'ROOT_FOLDER',
                    'DB_HOST', 
                    'DB_NAME', 
                    'DB_USER', 
                    'DB_PASS', 
                    'ADMIN_KEY',
                    'DEBUG_ROUTER',
                    'DEBUG_ENDPOINTS',
                ]);
                self::$env->required('DEBUG_ROUTER')->isBoolean();
                self::$env->required('DEBUG_ENDPOINTS')->isBoolean();

            } catch (\Throwable $e) {
                die($e->getMessage());
            }
            return true;    
        }
        return false;
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