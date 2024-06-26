<?php 

declare(strict_types=1);

namespace Frigate\Middlewares\Impl\Auth\Methods\Interfaces;

use Frigate\Routing\Http\RequestInterface;

interface AuthInterface {


    /**
     * credentials - extract the credentials of the current request
     * 
     * @param RequestInterface $request - the request object
     *
     * @return array|null - the credentials or null if not found
     */
    public function credentials(RequestInterface $request) : array|null;

    /**
     * grant - where the credentials are granted this is where we can take the credentials
     * and check wether they are valid or not
     * 
     * @param array $credentials - the credentials to be granted
     * @param array $secrets - additional secrets to be passed to the grant method
     *
     * @return bool - true if the credentials were granted
     */
    public function grant(array $credentials, array $secrets = []) : bool;

    /**
     * authorize
     * 
     * @param RequestInterface $request - the request object
     * @param string|array|null $credentials - if null, credentials will be fetched from credentials method
     * @param array $secrets - additional secrets to be passed to the authorization method
     *
     * @return array returned values [bool, ...] first is the authorized status, other values are optional
     */
    public function authenticate(RequestInterface $request, string|array|null $credentials = null, array $secrets = []) : array;



}