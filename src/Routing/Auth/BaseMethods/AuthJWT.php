<?php 

declare(strict_types=1);

namespace Siktec\Frigate\Routing\Auth\BaseMethods;

use \Siktec\Frigate\Routing\Http\RequestInterface;
use \Siktec\Frigate\Routing\Auth\AuthInterface;
use \Siktec\Frigate\Routing\Auth\AuthTraits;
use \Siktec\Frigate\Tools\Hashing\JWT;

class AuthJWT implements AuthInterface {


    use AuthTraits\BasicCredentialsTrait;

    /* 
     * We override the default header key and value prefix
     * This is because servers treat the Authorization header differently
     * depending on the protocol used.
     * We use a custom header to avoid this problem.
     * 
     * Authorization: Bearer <base64 encoded JWT>
     */
    public string $custom_header_key          = "authorization";

    public string $custom_header_value_prefix = "bearer";

    public ?string $secret = null;

    public ?JWT $jwt = null;
    
    /**
     * __construct
     *
     * @param  ?string $secret JWT secret or null to use the global secret if set
     * @return void
     */
    public function __construct(?string $secret = null) {
        // Create new JWTWrapper object if secret is set
        $this->jwt = new JWT($secret);
    }
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

        $auth = $request->getHeader($header_key) ?? "";
        $prefix_len = strlen($header_value_prefix);
        $token = strtolower($header_value_prefix) !== strtolower(substr($auth, 0, $prefix_len)) ? null : trim(substr($auth, $prefix_len));

        if (!$auth) return null;

        //Validate token
        $validated = $this->jwt->validate_token($token);

        return $validated ? $this->jwt->get_data() : null;

    }

    /**
     * authorize - authorize only returns the credentials if the token is valid
     * override this method to add custom authorization logic (e.g. check if user is admin)
     * header format: Authorization: Bearer <JWT>
     * @param  string|array|null $credentials pass null we will get the credentials from the request
     * @return array returned values [bool, data] // data is the data from the token or the error message
     */
    public function authorize(RequestInterface $request, string|array|null $credentials) : array {
        
        $data = $this->credentials($request);
        
        if (is_array($data)) {
            return [true, $data];
        } else {
            return [false, $this->jwt->get_error()];
        }
    }
}