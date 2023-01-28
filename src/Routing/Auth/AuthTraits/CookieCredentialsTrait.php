<?php 

declare(strict_types=1);

namespace Siktec\Frigate\Routing\Auth\AuthTraits;

use \Siktec\Frigate\Routing\Http\RequestInterface;

/**
 * CookieCredentialsTrait
 * 
 */
trait CookieCredentialsTrait {
    
    /**
     * credentials - get credentials from cookie
     * AUTHTOKEN=base64_encode(username:password)
     * 
     * @param  RequestInterface $request
     * @return array|null [username or userId, hash] or null if not found
     */
    public function credentials(RequestInterface $request) : array|null {

        $auth = $_COOKIE['AUTHTOKEN'] ?? null; 
        
        if (!$auth) return null;

        $credentials = explode(':', base64_decode($auth), 2);

        if (2 !== count($credentials)) {
            return null;
        }
        $credentials[2] = $auth;
        return $credentials;

    }
}