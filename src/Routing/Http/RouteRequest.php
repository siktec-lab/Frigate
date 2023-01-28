<?php

namespace Siktec\Frigate\Routing\Http;

use \Siktec\Frigate\Routing\Auth\AuthFactory;

class RouteRequest extends RequestDecorator {

    private string $method = "";
    public string  $expects = "text/plain";

    public function isTest() : bool {
        return strtolower($this->getHeader('X-Perform') ?? "") === "test";
    }

    // get the accepted content type from the request
    public function getAccept() : array {
        $accept = $this->getHeader('Accept');
        if (empty($accept)) {
            return [];
        }
        $accept = preg_replace("/;.*$/", "", $accept); //remove the q=1.0 from the accept header and the charset
        $accept = explode(",", $accept);
        $accept = array_map("trim", $accept);
        $accept = array_map("strtolower", $accept);
        return $accept;
    }
        
    /**
     * getPatchData
     * return the patch data as passed in the body -> json or string 
     * this will destroy teh input.
     * @return array
     */
    public function getPatchData() : array {
        $data = [];
        $str = $this->getBodyAsString();
        //check is $str is json:
        if (substr($str, 0, 1) === "{" || substr($str, 0, 1) === "[") {
            $data = json_decode($str, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $data = [];
            }
        } else {
            parse_str($str, $data);
        }
        return $data;
    }

    public function authorize(string|array $methods, array|null $manual_credentials = null, bool $throw = true) : array {

        if (is_string($methods)) {
            $methods = explode("|", trim($methods));
        } else if (!is_array($methods) || empty($methods)) {
            throw new \Exception("Invalid authorization method");
        }

        // check if methods are supported and execute them:
        foreach ($methods as $method) {
            if (!AuthFactory::has($method)) {
                throw new \Exception("Unsupported authorization method: {$method}");
            }
            $auth = AuthFactory::get($method);
            $authorized = $auth->authorize($this, $manual_credentials); // Authrization is the first array element returned
            if ($authorized[0]) {
                return $authorized;
            }
        }

        //Return or throw:
        if ($throw) {
            throw new \Exception("Not Authorized check credentials", 401);
        }
        return [false, null, null]; // we return minimal data here, so that the user can check if the request was authorized
    }
    
    public function getCredentials(string $from = "header") : ?array {
        $auth = "xxxx";
        switch ($from) {
            case "header": 
                $auth = $this->getHeader('Authorization') ?? "";
                if ('basic ' !== strtolower(substr($auth, 0, 6))) {
                    $auth = null;
                } else {
                    $auth = substr($auth, 6);
                }
            break;
            case "cookie": 
                $auth = $_COOKIE['AUTHTOKEN'] ?? null;
            break;
        }
        if (!$auth) {
            return null;
        }
        $credentials = explode(':', base64_decode($auth), 2);
        if (2 !== count($credentials)) {
            return null;
        }
        $credentials[2] = $auth;
        return $credentials;
    }

    public function requireAuthorization(string $method, bool $throw = true) : string|bool {

        $methods = explode("|", trim($method));
        $authorized = false;
        $user = "";
        foreach ($methods as $m) {

            switch (strtolower($m)) {
            
                //Basic method:
                case "basic": {
                    $credential = $this->getCredentials("header");
                    if (is_array($credential) && $credential[1] === $_ENV["ADMIN_KEY"]) {
                        $authorized = true;
                        $user = $credential[0];
                        break 2;
                    }
                } break;
                //Session method:
                case "session": {
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    $credential = $this->getCredentials("cookie");
                    if (
                        array_key_exists("AUTHTOKEN", $_SESSION) 
                        && is_array($credential) 
                        && $credential[1] === $_SESSION["AUTHTOKEN"]
                    ) {
                        $authorized = true;
                        //Extend cookie:
                        setcookie("AUTHTOKEN", $credential[2], time() + 3600, "/");
                        $user = $credential[0];
                        break 2;
                    }
                } break;
            }
        }
        
        //Return or throw:
        if (!$authorized && $throw) {
            throw new \Exception("Not Authorized check credentials", 401);
        }
        return $authorized === true ? $user : false;
    }

}