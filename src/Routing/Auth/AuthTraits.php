<?php 

declare(strict_types=1);

namespace Siktec\Frigate\Routing\Auth\Traits;

use \Siktec\Frigate\Routing\Http\RequestInterface;

/**
 * BasicCredentialsTrait
 * 
 */
trait BasicCredentialsTrait {
    
    /**
     * credentials - get credentials from request header
     * Authorization: Basic base64_encode(username:password)
     *
     * @param  RequestInterface $request
     * @return array|null [username or userId, hash] or null if not found
     */
    public function credentials(RequestInterface $request) : array|null {
        
        $auth = $request->getHeader('Authorization') ?? "";
        
        $auth = 'basic ' !== strtolower(substr($auth, 0, 6)) ? null : trim(substr($auth, 6));
        
        if (!$auth) return null;

        $credentials = explode(':', base64_decode($auth), 2);
        if (2 !== count($credentials)) {
            return null;
        }
        $credentials[2] = $auth;
        return $credentials;
    }
    
}

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