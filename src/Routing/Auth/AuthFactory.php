<?php 

declare(strict_types=1);

namespace Frigate\Routing\Auth;

class AuthFactory {

    static $auth_methods = [];

    static public function add(string $name, string|AuthInterface $auth_method) {
        
        // check that auth method implements AuthInterface
        if (
                is_string($auth_method) && !class_exists($auth_method) 
            || !is_string($auth_method) && !($auth_method instanceof AuthInterface)
        ) {
            throw new \Exception("Auth class not found");
        }
        if (is_string($auth_method) && !in_array(AuthInterface::class, class_implements($auth_method))) {
            throw new \Exception("Auth class must implement AuthInterface");
        }

        self::$auth_methods[$name] = is_string($auth_method) ? new $auth_method() : $auth_method; 
    }

    static public function remove(string $name) {
        if (isset(self::$auth_methods[$name])) {
            unset(self::$auth_methods[$name]);
        }
    }

    static public function get(string $name) : AuthInterface {
        if (!isset(self::$auth_methods[$name])) {
            throw new \Exception("Auth method not found");
        }
        return self::$auth_methods[$name];
    }

    static public function get_names() : array {
        return array_keys(self::$auth_methods);
    }

    static public function get_methods() : array {
        return self::$auth_methods;
    }

    static public function has(string $name) : bool {
        return isset(self::$auth_methods[$name]);
    }

}

/* default auth methods */
AuthFactory::add('basic', new BaseMethods\AuthBasic());
AuthFactory::add('session', new BaseMethods\AuthSession());