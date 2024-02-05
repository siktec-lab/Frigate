<?php 

declare(strict_types=1);

namespace Frigate\Helpers\Hashing;

use DomainException;
use InvalidArgumentException;
use UnexpectedValueException;
use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Frigate\Helpers\Arrays;

// JWT wrapper class
class JWT {

    // JWT secret key
    public  static  ?string $global_secret = null;
    private string $secret;

    // JWT issuer
    public  static ?string $global_issuer = null;
    private string $issuer;
    private const DEFAULT_ISSUER = "System";

    // JWT audience
    public  static ?string $global_audience = null;
    private string $audience;
    private const DEFAULT_AUDIENCE = "Client";
    
    // JWT client id
    public  static ?string $global_subject = null;
    private string $subject;
    private const DEFAULT_SUBJECT = "Client";

    // JWT algorithm
    public  static ?string $global_algorithm = null;
    private string $algorithm;
    private const DEFAULT_ALGORITHM = "HS256";

    // JWT expiration time in seconds
    public  static ?int $global_expiration = null;
    private int $expiration;
    private const DEFAULT_EXPIRATION = 3600; // 1 hour in seconds

    // JWT not before time in seconds
    public  static ?int $global_not_before = null;
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

    /**
     * Creates a new JWTWrapper object
     *
     * @param  ?string $secret    JWT secret key (optional) null to use global value  will throw exception if no global value is set
     * @param  ?string $issuer    JWT issuer (optional) null to use global value or default value
     * @param  ?string $audience  JWT audience (optional) null to use global value or default value
     * @param  ?string $subject   JWT subject (optional) null to use global value or default value
     * @param  ?string $algorithm JWT algorithm (optional) null to use global value or default value
     * @param  ?int $expiration  JWT expiration time in seconds (optional) null to use global value or default value
     * @param  ?int $not_before  JWT not before time in seconds (optional) null to use global value or default value
     */
    public function __construct(
        ?string $secret,
        ?string $issuer      = null,
        ?string $audience    = null,
        ?string $subject     = null,
        ?string $algorithm   = null,
        ?int $expiration     = null,
        ?int $not_before     = null
    ) {
        $this->set_secret($secret);
        $this->set_issuer($issuer);
        $this->set_audience($audience);
        $this->set_subject($subject);
        $this->set_algorithm($algorithm);
        $this->set_expiration($expiration);
        $this->set_not_before($not_before);
    }
    
    /**
     * Sets the JWT secret key
     *
     * @param  ?string $secret JWT secret key
     */
    public function set_secret(?string $secret) : void
    {
        if (empty($secret) && empty(self::$global_secret)) {
            throw new \Exception("Secret key is required");
        }
        if (empty($secret)) {
            $secret = self::$global_secret;
        }
        $this->secret = $secret;
    }

    /**  
     * Sets the JWT issuer
     *
     * @param  ?string $issuer JWT issuer null to use global value or default value
     */
    public function set_issuer(?string $issuer) : void
    {
        if (is_null($issuer)) {
            $issuer = self::$global_issuer ?? self::DEFAULT_ISSUER;
        }
        $this->issuer = $issuer;
    }
 
    /**
     * Sets the JWT audience
     *
     * @param  ?string $audience JWT audience null to use global value or default value
     */
    public function set_audience(?string $audience) : void
    {
        if (is_null($audience)) {
            $audience = self::$global_audience ?? self::DEFAULT_AUDIENCE;
        }
        $this->audience = $audience;
    }

    /**
     * Sets the JWT subject
     *
     * @param  ?string $subject JWT subject null to use global value or default value
     */
    public function set_subject(?string $subject) : void
    {
        if (is_null($subject)) {
            $subject = self::$global_subject ?? self::DEFAULT_SUBJECT;
        }
        $this->subject = $subject;
    }

    /**
     * Sets the JWT algorithm must be one of the available algorithms
     *
     * @param  ?string $algorithm JWT algorithm null to use global value or default value
     */
    public function set_algorithm(?string $algorithm) : void
    {
        if (is_null($algorithm)) {
            $algorithm = self::$global_algorithm ?? self::DEFAULT_ALGORITHM;
        } 
        if (!in_array($algorithm, self::AVAILABLE_ALGORITHM)) {
            throw new \Exception("Invalid algorithm");
        }
        $this->algorithm = $algorithm;
    }

    /**
     * Sets the JWT expiration time in seconds greater than 0
     *
     * @param  ?int $expiration JWT expiration time in seconds null to use global value or default value
     */
    public function set_expiration(?int $expiration) : void
    {
        if (is_null($expiration)) {
            $expiration = self::$global_expiration ?? self::DEFAULT_EXPIRATION;
        }
        if ($expiration < 0) {
            throw new \Exception("Expiration time must be greater than 0");
        } 
        $this->expiration = $expiration;
    }

    /**
     * Sets the JWT not before time in seconds greater than 0
     *
     * @param  ?int $not_before JWT not before time in seconds null to use global value or default value
     */
    public function set_not_before(?int $not_before) : void
    {
        if (is_null($not_before)) {
            $not_before = self::$global_not_before ?? self::DEFAULT_NOT_BEFORE;
        }
        if ($not_before < 0) {
            throw new \Exception("Not before time must be greater than 0");
        } 
        $this->not_before = $not_before;
    }

    /**
     * Sets the JWT issued at time in seconds greater than 0
     *
     * @param  ?int $issued_at JWT issued at time in seconds null to use current time
     */
    public function set_issued_at(?int $issued_at = null) : void
    {
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
     * Sets the JWT carried data as an array this will overwrite any existing data
     *
     * @param  array $data JWT carried data
     */
    public function set_data(array $data) : void
    {
        $this->data = $data;
    }
    
    /**
     * Adds data to the JWT carried data
     *
     * @param  string $key   Key of the data
     * @param  mixed $value Value of the data
     */
    public function add_to_data(string $key, mixed $value) : void
    {
        $this->data[$key] = $value;
    }
    
    /**
     * Gets the JWT carried data
     *
     * @return array JWT carried data
     */
    public function get_data() : array
    {
        return $this->data;
    }

    /**
     * Gets the JWT token
     */
    public function get_token() : ?string
    {
        return $this->token;
    }
    
    /**
     * Gets the JWT expiration time in seconds since the Unix Epoch
     */
    public function get_expire_at() : int
    {
        return $this->issued_at + $this->expiration;
    }

    /**
     * generate_token
     */
    public function generate_new_token(?array $data = null, ?int $issued_at = null) : string
    {
        // Reset token and error
        $this->token = null;
        $this->error = null;
        // Set issued at time
        $this->set_issued_at($issued_at);
        // Set data and generate token
        if ($data !== null) {
            $this->set_data($data);
        }
        $jti = bin2hex(random_bytes(16));
        // Generate token
        $token = [
            "iss"   => $this->issuer,
            "aud"   => $this->audience,
            "sub"   => $this->subject,
            "iat"   => $this->issued_at,
            "nbf"   => $this->issued_at - $this->not_before,
            "exp"   => $this->get_expire_at(),
            "jti"   => $jti, 
            "data"  => $this->data
        ];
        $this->token = FirebaseJWT::encode($token, $this->secret, $this->algorithm);

        return $this->token;
    }

    /**
     * Validates the JWT token
     */
    public function validate_token(string $token) : bool
    {
        // Reset error
        $this->error = null;
        try {
            $key = new Key($this->secret, $this->algorithm);
            $data = FirebaseJWT::decode($token, $key);
            // Populate JWT object:
            $this->issuer = $data->iss ?? "";
            $this->audience = $data->aud ?? "";
            $this->subject = $data->sub ?? "";
            $this->issued_at = $data->iat;
            $this->expiration = $data->exp - $data->iat;
            $this->not_before = $data->iat - $data->nbf;
            $this->token = $token;
            // Populate data:
            $this->set_data(Arrays::to_array($data->data));
            // Return true
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

    /**
     * Gets the JWT validation error
     */
    public function get_error() : ?string
    {
        return $this->error;
    }

    /**
     * Extends the JWT token expiration time by the given time in seconds or adds the 
     * given time to the current expiration time
     *
     * @param  int $time JWT token expiration time in seconds
     * @param  bool $add_to_current_time If true, adds the given time to the current expiration time
     */
    public function extend_token(?int $time = null, bool $add_to_current_time = false) : string
    {
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