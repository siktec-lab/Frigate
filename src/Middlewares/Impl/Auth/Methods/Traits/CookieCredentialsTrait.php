<?php 

declare(strict_types=1);

namespace Frigate\Middlewares\Impl\Auth\Methods\Traits;

use Frigate\Routing\Http\RequestInterface;

/**
 * CookieCredentialsTrait
 * 
 */
trait CookieCredentialsTrait {
    

    private string $default_cookie_key = "AUTHTOKEN";

    private string $default_token_delimiter = ":";

    /**
     * credentials - get credentials from cookie
     * AUTHTOKEN=base64_encode(username:password)
     * 
     * @param  RequestInterface $request
     * @return array|null [username or userId, hash] or null if not found
     */
    public function credentials(RequestInterface $request) : array|null {

        // Args:
        $cookie_key = $this->custom_cookie_key ?? $this->default_cookie_key;
        $token_delimiter = $this->custom_token_delimiter ?? $this->default_token_delimiter;

        $auth = $_COOKIE[$cookie_key] ?? null; 
        
        if (!$auth) return null;

        $credentials = explode($token_delimiter, base64_decode($auth), 2);

        if (2 !== count($credentials)) {
            return null;
        }
        $credentials[2] = $auth;
        return $credentials;

    }
}