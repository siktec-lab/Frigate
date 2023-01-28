<?php 

declare(strict_types=1);

namespace Siktec\Frigate\Routing\Auth\BaseMethods;

use \Siktec\Frigate\Routing\Http\RequestInterface;
use \Siktec\Frigate\Routing\Auth\AuthInterface;
use \Siktec\Frigate\Routing\Auth\AuthTraits;

class AuthSession implements AuthInterface {

    
    use AuthTraits\CookieCredentialsTrait;

    /**
     * authorize - checks if a user cookie has AUTHTOKEN and if it matches the session token
     * will extend the cookie lifetime if the user is authorized
     * 
     * @param  string|array $credentials
     * @return array returned values [bool, user, key]
     */
    public function authorize(RequestInterface $request, string|array|null $credentials) : array {
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (is_string($credentials)) {
            $credentials = ["", $credentials];
        }

        if (is_null($credentials)) {
            $credentials = $this->credentials($request);
        }

        if (
            array_key_exists("AUTHTOKEN", $_SESSION) 
            && is_array($credentials)  && count($credentials) === 2
            && $credentials[1] === $_SESSION["AUTHTOKEN"]
        ) {
            setcookie("AUTHTOKEN", $credentials[2], time() + 3600, "/");
            return [true, ...$credentials];
        }

        return empty($credentials) ? [false, null, null] : [false, ...$credentials];
    }
}