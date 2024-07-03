<?php 

declare(strict_types=1);

namespace Frigate\Contracts\Session;

/**
 * Session is a contract that defines the interface for a session manager.
 * Session management is a critical part of web applications. and may be implemented in different ways.
 * This contract defines the methods that a session manager must implement. and by default, Frigate uses the PHP session manager.
 */

interface SessionInterface {

    /**
     * Start the session
     * 
     * @param bool $if_not_started if true, the session will only be started if it is not already started
     * @return bool true if the session was started, false otherwise
     */
    public function start(bool $if_not_started = true) : bool;

    /**
     * Get a session variable
     * 
     * @param string $key the key of the session variable
     * @param mixed $default the default value to return if the session variable does not exist
     * @return mixed the value of the session variable
     */
    public function get(string $key, mixed $default = null) : mixed;

    /**
     * Get all session variables
     * 
     * @param array|null $keys an array of keys to return. If null, all session variables will be returned
     * @return array an associative array of session variables
     */
    public function all(?array $keys = null) : array;

    /**
     * Set a session variable
     * 
     * @param string $key the key of the session variable
     * @param mixed $value the value of the session variable
     * @param bool $overwrite if true, the value will be overwritten if it already exists
     * @return void
     */
    public function set(string $key, mixed $value, bool $overwrite = true) : void;

    /**
     * Set multiple session variables
     * 
     * @param array $data an associative array of session variables
     * @param bool $overwrite if true, the values will be overwritten if they already exist
     */
    public function setArray(array $data, bool $overwrite = true) : void;

    /**
     * Check if a session variable exists
     * 
     * @param string $key the key of the session variable
     * @return bool true if the session variable exists, false otherwise
     */
    public function has(string $key) : bool;

    /**
     * Remove a session variable
     * 
     * @param string $key the key of the session variable
     * @return void
     */
    public function remove(string $key) : void;

    /**
     * Destroy the session
     * 
     * @return bool
     */
    public function destroy() : bool;

    /**
     * Regenerate the session id
     * 
     * @param bool $delete_old if true, the old session will be deleted
     * @return bool true if the session id was regenerated, false otherwise
     */
    public function regenerate(bool $delete_old = false) : bool;

    /**
     * Get the session id
     * 
     * @return string|false the session id or false if the session is not started
     */
    public function id() : string|false;

    /**
     * Get the session name
     * 
     * @param string|null $set if not null, the session name will be set to this value
     * @return string|false the session name or false if the session is not started
     * @throws \InvalidArgumentException if the session name is invalid
     */
    public function name(?string $set = null ) : string|false;

}