<?php 

namespace Siktec\Frigate\Tools\Hashing;

use \Firebase\JWT\JWT as FirebaseJWT;
use \Firebase\JWT\Key;
use \Siktec\Frigate\Tools\Arrays\ArrayHelpers;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use DomainException;
use InvalidArgumentException;
use UnexpectedValueException;

// JWT wrapper class
class JWT {

    // JWT secret key
    private string $secret;

    // JWT issuer
    private string $issuer;
    private const DEFAULT_ISSUER = "System";

    // JWT audience
    private string $audience;
    private const DEFAULT_AUDIENCE = "Audience";
    
    // JWT algorithm
    private string $algorithm;
    private const DEFAULT_ALGORITHM = "HS256";

    // JWT expiration time in seconds
    private int $expiration;
    private const DEFAULT_EXPIRATION = 3600; // 1 hour in seconds

    // JWT not before time in seconds
    private int $not_before;
    private const DEFAULT_NOT_BEFORE = 60; // 1 minute in seconds  

    // JWT issued at time in seconds
    private int $issued_at;

    // Available JWT algorithms
    public const AVAILABLE_ALGORITHM = [
        "HS256", "HS384", "HS512",
        "RS256", "RS384", "RS512",
        "ES256", "ES384", "ES512"
    ];

    // JWT token
    private ?string $token = null;

    // JWT carried data:
    private array $data = [];

    // JWT validation error:
    private ?string $error = null;

    // Constructor
    public function __construct(
        string $secret,
        string $issuer      = self::DEFAULT_ISSUER,
        string $audience    = self::DEFAULT_AUDIENCE,
        string $algorithm   = self::DEFAULT_ALGORITHM,
        int $expiration     = self::DEFAULT_EXPIRATION,
        int $not_before     = self::DEFAULT_NOT_BEFORE
    ) {
        $this->set_secret($secret);
        $this->set_issuer($issuer);
        $this->set_audience($audience);
        $this->set_algorithm($algorithm);
        $this->set_expiration($expiration);
        $this->set_not_before($not_before);
    }
    
    /**
     * set_secret
     * Sets the JWT secret key
     * @param  string $secret JWT secret key
     * @return void
     */
    public function set_secret(string $secret) : void {
        $this->secret = $secret;
    }

    /**  
     * set_issuer
     * Sets the JWT issuer
     * @param  string $issuer JWT issuer
     * @return void
     */
    public function set_issuer(string $issuer) : void {
        $this->issuer = $issuer;
    }
 
    /**
     * set_audience
     * Sets the JWT audience
     * @param  string $audience JWT audience
     * @return void
     */
    public function set_audience(string $audience) : void {
        $this->audience = $audience;
    }

    /**
     * set_algorithm
     * Sets the JWT algorithm must be one of the available algorithms 
     * @param  string $algorithm JWT algorithm
     * @return void
     */
    public function set_algorithm(string $algorithm) : void {
        if (!in_array($algorithm, self::AVAILABLE_ALGORITHM)) {
            throw new \Exception("Invalid algorithm");
        }
        $this->algorithm = $algorithm;
    }

    /**
     * set_expiration
     * Sets the JWT expiration time in seconds greater than 0
     * @param  int $expiration JWT expiration time in seconds
     * @return void
     */
    public function set_expiration(int $expiration) : void {
        if ($expiration < 0) {
            throw new \Exception("Expiration time must be greater than 0");
        } 
        $this->expiration = $expiration;
    }

    /**
     * set_not_before
     * Sets the JWT not before time in seconds greater than 0
     * @param  int $not_before JWT not before time in seconds
     * @return void
     */
    public function set_not_before(int $not_before) : void {
        if ($not_before < 0) {
            throw new \Exception("Not before time must be greater than 0");
        } 
        $this->not_before = $not_before;
    }

    /**
     * set_issued_at
     * Sets the JWT issued at time in seconds greater than 0
     * @param  int $issued_at JWT issued at time in seconds
     * @return void
     */
    public function set_issued_at(?int $issued_at = null) : void {
        // If issued at time is not set, set it to current time
        if ($issued_at === null) {
            $issued_at = time();
        }

        // Check if issued at time is greater than 0
        if ($issued_at < 0) {
            throw new \Exception("Issued at time must be greater than 0");
        }

        // Set issued at time
        $this->issued_at = $issued_at;
    }

    /**
     * set_data
     * Sets the JWT carried data as an array this will overwrite any existing data
     * @param  array $data JWT carried data
     * @return void
     */
    public function set_data(array $data) : void {
        $this->data = $data;
    }
    
    /**
     * add_to_data
     * Adds data to the JWT carried data
     * @param  string $key   Key of the data
     * @param  mixed $value Value of the data
     * @return void
     */
    public function add_to_data(string $key, mixed $value) : void {
        $this->data[$key] = $value;
    }
    
    /**
     * get_data
     * Gets the JWT carried data
     * @return array JWT carried data
     */
    public function get_data() : array {
        return $this->data;
    }

    /**
     * get_token
     * Gets the JWT token
     * @return ?string JWT token
     */
    public function get_token() : ?string {
        return $this->token;
    }

    /**
     * generate_token
     */
    public function generate_new_token(?array $data = null, ?int $issued_at = null) : string {

        // Reset token and error
        $this->token = null;
        $this->error = null;

        // Set issued at time
        $this->set_issued_at($issued_at);

        // Set data and generate token
        if ($data !== null) {
            $this->set_data($data);
        }

        // Generate token
        $token = [
            "iss"   => $this->issuer,
            "aud"   => $this->audience,
            "iat"   => $this->issued_at,
            "nbf"   => $this->issued_at - $this->not_before,
            "exp"   => $this->issued_at + $this->expiration,
            "data"  => $this->data
        ];

        $this->token = FirebaseJWT::encode($token, $this->secret, $this->algorithm);

        return $this->token;
    
    }

    /**
     * validate_token
     * Validates the JWT token
     * @param  string $token JWT token
     * @return bool
     */
    public function validate_token(string $token) : bool {

        // Reset error
        $this->error = null;

        try {
            $key = new Key($this->secret, $this->algorithm);
            $data = FirebaseJWT::decode($token, $key);
            $this->set_data(ArrayHelpers::to_array($data->data));
            return true;
        } catch (InvalidArgumentException $e) {
            // provided key/key-array is empty or malformed.
            $this->error = $e->getMessage();

        } catch (DomainException $e) {
            // provided algorithm is unsupported OR
            // provided key is invalid OR
            // unknown error thrown in openSSL or libsodium OR
            // libsodium is required but not available.
            $this->error = $e->getMessage();

        } catch (SignatureInvalidException $e) {
            // provided JWT signature verification failed.
            $this->error = $e->getMessage();

        } catch (BeforeValidException $e) {
            // provided JWT is trying to be used before "nbf" claim OR
            // provided JWT is trying to be used before "iat" claim.
            $this->error = $e->getMessage();

        } catch (ExpiredException $e) {
            // provided JWT is trying to be used after "exp" claim.
            $this->error = $e->getMessage();

        } catch (UnexpectedValueException $e) {
            // provided JWT is malformed OR
            // provided JWT is missing an algorithm / using an unsupported algorithm OR
            // provided JWT algorithm does not match provided key OR
            // provided key ID in key/key-array is empty or invalid.
            $this->error = $e->getMessage();
        }

        return false;
    }

    public function get_error() : ?string {
        return $this->error;
    }
    /**
     * extend_token
     * Extends the JWT token expiration time by the given time in seconds or adds the given time to the current expiration time
     * @param  int $time JWT token expiration time in seconds
     * @param  bool $add_to_current_time If true, adds the given time to the current expiration time
     * @return string JWT token
     */
    public function extend_token(?int $time = null, bool $add_to_current_time = false) : string {

        // Get current expiration time
        $current_expiration_time = $this->data["exp"];

        // If time is not set, set it to the default expiration time
        if ($time === null) {
            $time = $this->expiration;
        }
        // If add to current time is true, add the given time to the current expiration time
        if ($add_to_current_time) {
            $current_expiration_time += $time;
        }

        // Set new expiration time
        $this->set_expiration($current_expiration_time - $this->issued_at);

        // Generate new token
        return $this->generate_new_token();
    }

}