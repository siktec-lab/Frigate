<?php 

declare(strict_types=1);

namespace Frigate\Middleware\Impl\Auth\Methods\Traits;

use Frigate\Routing\Http\RequestInterface;

/**
 * HeaderParamCredentialsTrait - extract credentials from header params
 */
trait HeaderParamCredentialsTrait {


    /**
     * structure of the auth header
     * @var array{key: string, type: string, delimiter: string, encoding: string} $authParamStructure
     */
    private array $authParamStructure = [
        "key"       => "Authorization",
        "type"      => "Basic",
        "delimiter" => ":",
        "encoding"  => "base64"
    ];
    
    private function decodeAuthParamValue(string $type, string $str) : string|null {
        switch ($type) {
            case "base64":
                return base64_decode($str, true) ?: null;
            case "plain":
                return $str;
            default:
                return null;
        }
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
        $key = $this->authParamStructure["key"];
        $type = $this->authParamStructure["type"];
        $delimiter = $this->authParamStructure["delimiter"];
        $encoding = $this->authParamStructure["encoding"];

        // Prepare:
        $auth = $request->getHeader($key) ?? "";
        $type_len = strlen($type);
        $auth = strtolower($type) !== strtolower(substr($auth, 0, $type_len)) ? null : trim(substr($auth, $type_len));
        
        if (!$auth) return null;

        // Decode:
        $credentials = explode($delimiter, $this->decodeAuthParamValue($encoding, $auth) ?? "", 2);
        
        if (2 !== count($credentials)) {
            return null;
        }

        // Add the original auth value to the credentials
        $credentials[2] = $auth;


        return $credentials;
    }
    
}