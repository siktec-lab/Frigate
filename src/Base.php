<?php 

namespace Siktec\Frigate;

use \Siktec\Frigate\DataBase\MysqliDb;
use \Dotenv\Dotenv;

class Base {

    static public ?Dotenv $env = null;

    static public ?MysqliDb $db = null;

    public static function init($connect = true) : void {
        self::load_environment();
        if ($connect) self::connect_database();
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

    static public function start_page_buffer() : void {
        ob_start();
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

    static public function load_environment() {
        if (is_null(self::$env)) {
            try {
                self::$env = Dotenv::createImmutable(ROOT_PATH);
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
        }
        return true;
    }

    static public function ENV_BOOL(string $key) : bool {
        return array_key_exists($key, $_ENV) ? filter_var($_ENV[$key], FILTER_VALIDATE_BOOLEAN) : false;
    }

    static public function ENV_STR(string $key) : string {
        return strval($_ENV[$key] ?? "");
    }

    static public function ENV_INT(string $key) : bool {
        return intval($_ENV[$key] ?? "");
    }

    static public function ENV_FLOAT(string $key) : bool {
        return floatval($_ENV[$key] ?? "");
    }

}