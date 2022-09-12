<?php

declare(strict_types=1);

namespace Siktec\Frigate\Routing\Http;

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
     * @param string|int $status
     *
     * @throws \InvalidArgumentException
     */
    public function setStatus($status) : void;
}