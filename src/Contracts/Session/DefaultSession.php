<?php 

declare(strict_types=1);

namespace Frigate\Contracts\Session;

class DefaultSession implements SessionInterface
{
    /**
     * @inheritDoc
     */
    public function start(bool $if_not_started = true) : bool
    {
        if ($if_not_started && session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }
        return session_start();
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null) : mixed
    {
        return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : $default;
    }

    /**
     * @inheritDoc
     */
    public function all(?array $keys = null) : array
    {
        if ($keys) {
            return array_intersect_key($_SESSION, array_flip($keys));
        }
        return $_SESSION;
    }
    
    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, bool $overwrite = true) : void
    {
        if ($overwrite || !isset($_SESSION[$key])) {
            $_SESSION[$key] = $value;
        }
    }

    /**
     * @inheritDoc
     */
    public function setArray(array $data, bool $overwrite = true) : void
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value, $overwrite);
        }
    }

    /**
     * @inheritDoc
     */
    public function has(string $key) : bool
    {
        return array_key_exists($key, $_SESSION);
    }

    /**
     * @inheritDoc
     */
    public function remove(string $key) : void
    {
        unset($_SESSION[$key]);
    }

    /**
     * @inheritDoc
     */
    public function destroy() : bool
    {
        return session_destroy();
    }

    /**
     * @inheritDoc
     */
    public function regenerate(bool $delete_old = false) : bool
    {
        return session_regenerate_id($delete_old);
    }

    /**
     * @inheritDoc
     */
    public function id() : string|false
    {
        return session_id();
    }

    /**
     * @inheritDoc
     */
    public function name(?string $set = null ) : string|false
    {
        // Only alphanum and - are allowed in session names:
        if ($set && !preg_match('/^[a-zA-Z0-9-]+$/', $set)) {
            throw new \InvalidArgumentException('Invalid session name');
        }
        return session_name($set);
    }
}
