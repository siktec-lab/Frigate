<?php 

declare(strict_types=1);

namespace Frigate\Contracts\Session;

use Frigate\Contracts\Session\DefaultSession;
use SessionHandler;
use SessionHandlerInterface;
use Frigate\Contracts\Session\SessionInterface;

/**
 * Session is a contract that defines the interface for a session manager.
 */

class SessionManager {

    /**
     * The default session handler
     */
    public static string $USE_DEFAULT_HANDLER = SessionHandler::class;

    /**
     * The default session manager
     */
    public static string $USE_DEFAULT_MANAGER = DefaultSession::class;

    /**
     * The session handler
     */
    public static string $handler = SessionHandler::class;


    public static SessionInterface $manager;

    /** 
     * Set the session handler
     */
    public static function setHandler(?string $handler = null) : bool {

        self::$handler = !is_null($handler) && class_exists($handler) ? $handler::class : self::$USE_DEFAULT_HANDLER;

        // Check if the handler implements the SessionHandlerInterface
        if (!is_subclass_of(self::$handler, SessionHandlerInterface::class, true)) {
            throw new \InvalidArgumentException("The session handler must implement the SessionHandlerInterface");
        }

        return session_set_save_handler(new self::$handler(), true);
    }

    /**
     * Set the session manager
     */
    public static function setManager(?string $manager = null) : void {

        // Which manager to use:
        $init_manager = !is_null($manager) && class_exists($manager) ? $manager : self::$USE_DEFAULT_MANAGER;

        // Check if the manager implements the SessionInterface
        if (!is_subclass_of($init_manager, SessionInterface::class, true)) {
            throw new \InvalidArgumentException("The session manager must implement the SessionInterface");
        }

        // Initialize the manager:
        self::$manager = new $init_manager();
    }

    /** 
     * Initialize the Session global manager
     */
    public static function init(
        ?SessionInterface $manager = null,
        ?SessionHandlerInterface $handler = null
    ) : void {
        self::setHandler($handler);
        self::setManager($manager);
    }

    /**
     * Start the session
     * 
     * @param bool $if_not_started if true, the session will only be started if it is not already started
     * @return bool true if the session was started, false otherwise
     */
    public static function start(bool $if_not_started = true) : bool {
        return self::$manager->start($if_not_started);
    }

    /**
     * Get a session variable
     * 
     * @param string $key the key of the session variable
     * @param mixed $default the default value to return if the session variable does not exist
     * @return mixed the value of the session variable
     */
    public static function get(string $key, mixed $default = null) : mixed {
        return self::$manager->get($key, $default);
    }

    /**
     * Get all session variables
     * 
     * @param array|null $keys an array of keys to return. If null, all session variables will be returned
     * @return array an associative array of session variables
     */
    public static function all(?array $keys = null) : array {
        return self::$manager->all($keys);
    }

    /**
     * Set a session variable
     * 
     * @param string $key the key of the session variable
     * @param mixed $value the value of the session variable
     * @param bool $overwrite if true, the value will be overwritten if it already exists
     * @return void
     */
    public static function set(string $key, mixed $value, bool $overwrite = true) : void {
        self::$manager->set($key, $value, $overwrite);
    }

    /**
     * Set multiple session variables
     * 
     * @param array $data an associative array of session variables
     * @param bool $overwrite if true, the values will be overwritten if they already exist
     */
    public static function setArray(array $data, bool $overwrite = true) : void {
        self::$manager->setArray($data, $overwrite);
    }

    /**
     * Check if a session variable exists
     * 
     * @param string $key the key of the session variable
     * @return bool true if the session variable exists, false otherwise
     */
    public static function has(string $key) : bool {
        return self::$manager->has($key);
    }

    /**
     * Remove a session variable
     * 
     * @param string $key the key of the session variable
     * @return void
     */
    public static function remove(string $key) : void {
        self::$manager->remove($key);
    }

    /**
     * Destroy the session
     * 
     * @return bool
     */
    public static function destroy() : void {
        self::$manager->destroy();
    }

    /**
     * Regenerate the session id
     * 
     * @param bool $delete_old if true, the old session will be deleted
     */
    public static function regenerate(bool $delete_old = false) : void {
        self::$manager->regenerate($delete_old);
    }

    /**
     * Call a method on the session manager
     */
    public static function __callStatic(string $name, array $arguments) {
        return self::$manager->$name(...$arguments);
    }

    /**
     * Call a method on the session manager
     */
    public function __call(string $name, array $arguments) {
        return self::$manager->$name(...$arguments);
    }
}