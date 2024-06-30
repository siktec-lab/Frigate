<?php

declare(strict_types=1);

namespace Frigate\Routing\Http;

/**
 * The RequestInterface represents a HTTP request.
 */
interface RequestInterface extends MessageInterface
{

    /**
     * Whether the request is a test request.
     */
    public function isTest() : bool;

    /**
     * Returns the current HTTP method.
     */
    public function getMethod() : Methods;

    /**
     * Sets the HTTP method.
     * @throws \InvalidArgumentException
     */
    public function setMethod(string|Methods $method) : RequestInterface;

    /**
     * Returns the request url.
     */
    public function getUrl() : string;

    /**
     * Sets the request url.
     */
    public function setUrl(string $url) : RequestInterface;

    /**
     * Returns the absolute url.
     */
    public function getAbsoluteUrl() : string;

    /**
     * Sets the absolute url.
     */
    public function setAbsoluteUrl(string $url) : RequestInterface;

    /**
     * Returns the current base url.
     */
    public function getBaseUrl() : string;

    /**
     * Sets a base url.
     *
     * This url is used for relative path calculations.
     *
     * The base url should default to /
     */
    public function setBaseUrl(string $url) : RequestInterface;

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
    public function getPath() : string;

    /**
     * Returns the list of query parameters.
     *
     * This is equivalent to PHP's $_GET superglobal.
     *
     * @return array<string, string>
     */
    public function getQueryParameters() : array;

    /**
     * Returns the POST data.
     *
     * This is equivalent to PHP's $_POST superglobal.
     *
     * @return array<string, string>
     */
    public function getPostData() : array;

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
    public function setPostData(array $postData) : RequestInterface;

    /**
     * Returns an item from the _SERVER array.
     *
     * If the value does not exist in the array, null is returned.
     */
    public function getRawServerValue(string $valueName) : ?string;

    /**
     * Sets the _SERVER array.
     *
     * @param array<string, string> $data
     */
    public function setRawServerData(array $data) : RequestInterface;

    /**
     * Loads the request from the _SERVER array.
     */
    public function initFromServerArray(array $serverArray) : RequestInterface;

}
