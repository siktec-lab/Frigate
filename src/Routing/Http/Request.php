<?php declare(strict_types=1);

namespace Frigate\Routing\Http;

use Sabre\Uri;

/**
 * The Request class represents a single HTTP request.
 * You can either simply construct the object from scratch, or if you need
 */
class Request extends Message implements RequestInterface {

    /**
     * HTTP Method.
     */
    protected Methods $method;

    /**
     * Request Url.
     */
    protected string $url;

    /**
     * Base url.
     */
    protected string $baseUrl = '/';

    /**
     * Equivalent of PHP's $_POST.
     *
     * @var array<string, string>
     */
    protected array $postData = [];

    /**
     * An array containing the raw _SERVER array.
     *
     * @var array<string, string>
     */
    protected array $rawServerData;

    /**
     * Creates the request object.
     *
     * @param array<string, string>         $headers
     * @param resource|callable|string|null $body
     * @throws \InvalidArgumentException if the method is not a valid HTTP method.
     */
    public function __construct(string|Methods $method, string $url, array $headers = [], $body = null)
    {
        $this->setMethod($method);
        $this->setUrl($url);
        $this->setHeaders($headers);
        $this->setBody($body);
    }

    /**
     * check if the request is a test request
     * NOT IMPLEMENTED by default
     */
    public function isTest() : bool {
        return false;
    }

    /**
     * Returns the current HTTP method.
     */
    public function getMethod() : Methods
    {
        return $this->method;
    }

    /**
     * Sets the HTTP method.
     * @throws \InvalidArgumentException if the method is not a valid HTTP method.
     */
    public function setMethod(string|Methods $method) : self
    {
        $this->method = is_string($method) ? Methods::fromString($method) : $method;
        return $this;
    }

    /**
     * Returns the request url.
     */
    public function getUrl() : string
    {
        return $this->url;
    }

    /**
     * Sets the request url.
     */
    public function setUrl(string $url) : self
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Returns the list of query parameters.
     *
     * This is equivalent to PHP's $_GET superglobal.
     *
     * @return array<string, string>
     */
    public function getQueryParameters() : array
    {
        $url = $this->getUrl();
        if (false === ($index = strpos($url, '?'))) {
            return [];
        }

        parse_str(substr($url, $index + 1), $queryParams);

        return $queryParams;
    }

    protected ?string $absoluteUrl = null;

    /**
     * Sets the absolute url.
     */
    public function setAbsoluteUrl(string $url) : self
    {
        $this->absoluteUrl = $url;
        return $this;
    }

    /**
     * Returns the absolute url.
     */
    public function getAbsoluteUrl() : string
    {
        if (!$this->absoluteUrl) {
            // Guessing we're a http endpoint.
            $this->absoluteUrl = 'http://'.
                ($this->getHeader('Host') ?? 'localhost').
                $this->getUrl();
        }

        return $this->absoluteUrl;
    }

    /**
     * Sets a base url.
     *
     * This url is used for relative path calculations.
     */
    public function setBaseUrl(string $url) : self
    {
        $this->baseUrl = $url;
        return $this;
    }

    /**
     * Returns the current base url.
     */
    public function getBaseUrl() : string
    {
        return $this->baseUrl;
    }

    /**
     * Returns the relative path.
     *
     * This is being calculated using the base url. This path will not start
     * with a slash, so it will always return something like
     * 'example/path.html'.
     *
     * If the full path is equal to the base url, this method will return an
     * empty string.
     *
     * This method will also urldecode the path, and if the url was encoded as
     * ISO-8859-1, it will convert it to UTF-8.
     *
     * If the path is outside of the base url, a LogicException will be thrown.
     */
    public function getPath() : string
    {
        // Removing duplicated slashes.
        $uri = str_replace('//', '/', $this->getUrl());

        $uri = Uri\normalize($uri);
        $baseUri = Uri\normalize($this->getBaseUrl());

        if (0 === strpos($uri, $baseUri)) {
            // We're not interested in the query part (everything after the ?).
            list($uri) = explode('?', $uri);

            return trim(self::decodePath(substr($uri, strlen($baseUri))), '/');
        }

        if ($uri.'/' === $baseUri) {
            return '';
        }
        // A special case, if the baseUri was accessed without a trailing
        // slash, we'll accept it as well.

        throw new \LogicException('Requested uri ('.$this->getUrl().') is out of base uri ('.$this->getBaseUrl().')');
    }

    /**
     * Sets the post data.
     *
     * This is equivalent to PHP's $_POST superglobal.
     *
     * This would not have been needed, if POST data was accessible as
     * php://input, but unfortunately we need to special case it.
     *
     * @param array<string, string> $postData
     */
    public function setPostData(array $postData) : self
    {
        $this->postData = $postData;
        return $this;
    }

    /**
     * Returns the POST data.
     *
     * This is equivalent to PHP's $_POST superglobal.
     *
     * @return array<string, string>
     */
    public function getPostData() : array
    {
        return $this->postData;
    }

    /**
     * Returns an item from the _SERVER array.
     *
     * If the value does not exist in the array, null is returned.
     */
    public function getRawServerValue(string $valueName) : ?string
    {
        return $this->rawServerData[$valueName] ?? null;
    }

    /**
     * Sets the _SERVER array.
     *
     * @param array<string, string> $data
     */
    public function setRawServerData(array $data) : self
    {
        $this->rawServerData = $data;
        return $this;
    }

    /**
     * Decodes a url-encoded path segment.
     */
    public static function decodePath(string $path) : string
    {
        $path = rawurldecode($path);
        if (!mb_check_encoding($path, 'UTF-8') && mb_check_encoding($path, 'ISO-8859-1')) {
            $path = mb_convert_encoding($path, 'UTF-8', 'ISO-8859-1');
        }
        return $path;
    }

    /**
     * This static method will create a new Request object, based on a PHP $_SERVER array.
     * REQUEST_URI and REQUEST_METHOD are required.
     *
     * @param array<string, string> $serverArray
     * @return self
     * @throws \InvalidArgumentException if the _SERVER array is invalid.
     */
    public function initFromServerArray(array $serverArray) : self
    {

        $headers        = [];
        $method         = null;
        $url            = null;
        $httpVersion    = '1.1';
        $protocol       = 'http';
        $hostName       = 'localhost';

        foreach ($serverArray as $key => $value) {
            $key = (string) $key;
            switch ($key) {
                case 'SERVER_PROTOCOL':
                    if ('HTTP/1.0' === $value) {
                        $httpVersion = '1.0';
                    } elseif ('HTTP/2.0' === $value) {
                        $httpVersion = '2.0';
                    }
                    break;
                case 'REQUEST_METHOD':
                    $method = $value;
                    break;
                case 'REQUEST_URI':
                    $url = $value;
                    break;
                    // These sometimes show up without a HTTP_ prefix
                case 'CONTENT_TYPE':
                    $headers['Content-Type'] = $value;
                    break;
                case 'CONTENT_LENGTH':
                    $headers['Content-Length'] = $value;
                    break;
                    // mod_php on apache will put credentials in these variables.
                    // (fast)cgi does not usually do this, however.
                case 'PHP_AUTH_USER':
                    if (isset($serverArray['PHP_AUTH_PW'])) {
                        $headers['Authorization'] = 'Basic '.base64_encode($value.':'.$serverArray['PHP_AUTH_PW']);
                    }
                    break;
                    // Similarly, mod_php may also screw around with digest auth.
                case 'PHP_AUTH_DIGEST':
                    $headers['Authorization'] = 'Digest '.$value;
                    break;
                    // Apache may prefix the HTTP_AUTHORIZATION header with
                    // REDIRECT_, if mod_rewrite was used.
                case 'REDIRECT_HTTP_AUTHORIZATION':
                    $headers['Authorization'] = $value;
                    break;

                case 'HTTP_HOST':
                    $hostName = $value;
                    $headers['Host'] = $value;
                    break;
                case 'HTTPS':
                    if (!empty($value) && 'off' !== $value) {
                        $protocol = 'https';
                    }
                    break;
                default:
                    if ('HTTP_' === substr($key, 0, 5)) {
                        // It's a HTTP header
                        // Normalizing it to be prettier
                        $header = strtolower(substr($key, 5));
                        // Transforming dashes into spaces, and upper-casing
                        // every first letter.
                        $header = ucwords(str_replace('_', ' ', $header));
                        // Turning spaces into dashes.
                        $header = str_replace(' ', '-', $header);
                        $headers[$header] = $value;
                    }
                    break;
            }
        }

        if (is_null($url)) {
            throw new \InvalidArgumentException('The _SERVER array must have a REQUEST_URI key');
        }

        if (is_null($method)) {
            throw new \InvalidArgumentException('The _SERVER array must have a REQUEST_METHOD key');
        }

        $this->setMethod($method)
                ->setUrl($url)
                ->setHeaders($headers)
                ->setHttpVersion($httpVersion);
                
        $this->setRawServerData($serverArray)
             ->setAbsoluteUrl($protocol.'://'.$hostName.$url);

        return $this;
    }

    /**
     * Serializes the request object as a string.
     *
     * This is useful for debugging purposes.
     */
    public function __toString() : string
    {
        $out = $this->getMethod()->value.' '.$this->getUrl().' HTTP/'.$this->getHttpVersion()."\r\n";

        foreach ($this->getHeaders() as $key => $value) {
            foreach ($value as $v) {
                if ('Authorization' === $key) {
                    list($v) = explode(' ', $v, 2);
                    $v .= ' REDACTED';
                }
                $out .= $key.': '.$v."\r\n";
            }
        }
        $out .= "\r\n";
        //NOTE: Shlomi removed this to prevent consuming the body
        // $out .= $this->getBodyAsString(); 

        return $out;
    }

}
