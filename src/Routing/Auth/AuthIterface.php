<?php 

declare(strict_types=1);

namespace Siktec\Frigate\Routing\Auth;

use Siktec\Frigate\Routing\Http\RequestInterface;



interface AuthInterface {
    

    public function credentials(RequestInterface $request) : array|null;

    /**
     * authorize
     *
     * @param  string|array $credentials - if null, credentials will be fetched from credentials method
     * @return array returned values [bool, ...] first is the authorized status, other values are optional
     */
    public function authorize(RequestInterface $request, string|array|null $credentials) : array;

}