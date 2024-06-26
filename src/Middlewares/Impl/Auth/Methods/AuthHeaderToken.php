<?php 

declare(strict_types=1);

namespace Frigate\Middlewares\Impl\Auth\Methods;

use Frigate\Routing\Http\RequestInterface;
use Frigate\Middlewares\Impl\Auth\Methods\Interfaces\AuthInterface;


abstract class AuthHeaderToken implements AuthInterface {


    use Traits\HeaderParamCredentialsTrait;

    /**
     * The constructor
     * 
     * @param string|null $key - the key of the header param
     * @param string|null $type - the type of the auth method
     * @param string|null $encoding - the encoding of the auth method
     * @param string|null $delimiter - the delimiter of the auth method
     */
    public function __construct(?string $key = null, ?string $type = null, ?string $encoding = null, ?string $delimiter = null)
    {
        if ($key) $this->authParamStructure["key"]              = $key;
        if ($type) $this->authParamStructure["type"]            = $type;
        if ($encoding) $this->authParamStructure["encoding"]    = $encoding;
        if ($delimiter) $this->authParamStructure["delimiter"]  = $delimiter;
    }

    /**
     * grant - where the credentials are granted this is where we can take the credentials
     * and check wether they are valid or not
     * 
     * @param array $credentials - the credentials to be granted
     * @param array $secrets - additional secrets to be passed to the grant method
     *
     * @return bool - true if the credentials were granted
     */
    abstract public function grant(array $credentials, array $secrets = []) : bool;

    /**
     * authorize - checks if a auth token passed in the header is granted
     * header format: Authorization: Basic <base64 encoded user:token>
     * 
     * @param RequestInterface $request - the request object
     * @param  string|array|null $credentials - force credentials to be used
     * @param array $secrets - additional secrets to be passed to the authorization method
     * @return array{bool, string|null, string|null} - returned values [bool, user, key]
     */
    public function authenticate(
        RequestInterface $request, 
        string|array|null $credentials = null,
        array $secrets = []
    ) : array {
        
        if (is_string($credentials)) {
            $credentials = ["", $credentials];
        }

        if (is_null($credentials)) {
            $credentials = $this->credentials($request);
        }

        if  (empty($credentials)) 
            return [false, null, null]; 

        if (is_array($credentials) && $this->grant($credentials, $secrets)) {
            return [true, ...$credentials];
        } else {
            return [false, ...$credentials];
        }
    }
}