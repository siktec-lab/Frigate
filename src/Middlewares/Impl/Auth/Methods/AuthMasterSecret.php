<?php 

declare(strict_types=1);

namespace Frigate\Middlewares\Impl\Auth\Methods;

use Frigate\FrigateApp;


class AuthMasterSecret extends AuthHeaderToken {


    /** The Environment key that holds the master secret */
    private string $env_key = "ACCESS_SECRET";

    /**
     * The constructor
     * 
     * @param string $env_key - the key of the environment variable that holds the master secret
     * @param string $type - the type of the auth method (default: Basic use Basic,Digest,Bearer,OAuth etc)
     * @param string|null $delimiter - the delimiter of the auth method - default is ":"
     */
    public function __construct(string $env_key, string $type = "Basic", ?string $delimiter = null)
    {
        // Call the parent constructor
        parent::__construct(
            type: $type,
            delimiter: $delimiter
        );

        // Set the env key
        $this->env_key = $env_key;
    }

    /**
     * @inheritDoc
     */
    public function grant(array $credentials, array $secrets = []) : bool
    {
        $secret = FrigateApp::ENV_STR($this->env_key, null);
        return $secret && $credentials[1] === $secret;
    }
}