<?php 

declare(strict_types=1);

namespace Frigate\Routing\Auth\BaseMethods;

use Frigate\Routing\Http\RequestInterface;
use Frigate\Routing\Auth\AuthInterface;
use Frigate\Routing\Auth\AuthTraits;

class AuthBasic implements AuthInterface {


    use AuthTraits\BasicCredentialsTrait;

    /**
     * authorize - checks if a auth token passed in the header is valid and matches the ENV ADMIN_KEY
     * header format: Authorization: Basic <base64 encoded user:token>
     * @param  string|array $credentials
     * @return array returned values [bool, user, key]
     */
    public function authorize(RequestInterface $request, string|array|null $credentials) : array {
        
        if (is_string($credentials)) {
            $credentials = ["", $credentials];
        }

        if (is_null($credentials)) {
            $credentials = $this->credentials($request);
        }

        if (is_array($credentials) && $credentials[1] === $_ENV["ADMIN_KEY"]) {
            return [true, ...$credentials];
        }

        return empty($credentials) ? [false, null, null] : [false, ...$credentials];
    }
}