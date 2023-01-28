<?php 

declare(strict_types=1);

namespace Siktec\Frigate\Routing\Auth\AuthTraits;

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
    private string $credentials_header_key = "Authorization";

    private string $credentials_header_value_prefix  = "basic";

    private string $credentials_token_delimiter = ":";

    public function credentials(RequestInterface $request) : array|null {
        
        $auth = $request->getHeader($this->credentials_header_key) ?? "";
        $prefix_len = strlen($this->credentials_header_value_prefix);
        $auth = strtolower($this->credentials_header_value_prefix) !== strtolower(substr($auth, 0, $prefix_len)) ? null : trim(substr($auth, $prefix_len));
        
        if (!$auth) return null;

        $credentials = explode($this->credentials_token_delimiter, base64_decode($auth), 2);
        if (2 !== count($credentials)) {
            return null;
        }
        $credentials[2] = $auth;
        return $credentials;
    }
    
}