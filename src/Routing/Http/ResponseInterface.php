<?php

declare(strict_types=1);

namespace Frigate\Routing\Http;

/**
 * This interface represents a HTTP response.
 */
interface ResponseInterface extends MessageInterface
{
    /**
     * Returns the current HTTP status code.
     */
    public function getStatus() : int;

    /**
     * Returns the human-readable status string.
     *
     * In the case of a 200, this may for example be 'OK'.
     */
    public function getStatusText() : string;

    /**
     * Sets the HTTP status code.
     *
     * This can be either the full HTTP status code with human-readable string,
     * for example: "403 I can't let you do that, Dave".
     *
     * Or just the code, in which case the appropriate default message will be
     * added.
     *
     * @param string|int $status The HTTP status code or code with text
     * @param string|null $text The human-readable status string
     * @throws \InvalidArgumentException
     */
    public function setStatus(string|int $code, string|null $text = null) : void;
    
    /**
     * Sets the body to a JSON string.
     */
    public function setBodyJson(array|string $body, bool $pretty = false) : void;

    /**
     * Returns the body as an array.
     */
    public function getBodyArray() : ?array;
}