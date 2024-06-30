<?php 

declare(strict_types=1);

namespace Frigate\Routing\Auth\AuthTraits;

use Frigate\Routing\Http\RequestInterface;

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
    private string $default_header_key = "Authorization";

    private string $default_header_value_prefix  = "basic";

    private string $default_token_delimiter = ":";
    
    /**
     * credentials - get credentials from request header
     * Authorization: Basic base64_encode(username:password)
     * will return [username, password, base64_encode(username:password)]
     * @param  RequestInterface $request
     * @return array|null [username or userId, hash] or null if not found
     */
    public function credentials(RequestInterface $request) : array|null {
        
        // Args:
        $header_key = $this->custom_header_key ?? $this->default_header_key;
        $header_value_prefix = $this->custom_header_value_prefix ?? $this->default_header_value_prefix;
        $token_delimiter = $this->custom_token_delimiter ?? $this->default_token_delimiter;

        $auth = $request->getHeader($header_key) ?? "";
        $prefix_len = strlen($header_value_prefix);
        $auth = strtolower($header_value_prefix) !== strtolower(substr($auth, 0, $prefix_len)) ? null : trim(substr($auth, $prefix_len));
        
        if (!$auth) return null;

        $credentials = explode($token_delimiter, base64_decode($auth), 2);
        if (2 !== count($credentials)) {
            return null;
        }
        $credentials[2] = $auth;
        return $credentials;
    }
    
}