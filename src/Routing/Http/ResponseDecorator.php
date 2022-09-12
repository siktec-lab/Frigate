<?php

declare(strict_types=1);

namespace Siktec\Frigate\Routing\Http;

/**
 * Response Decorator.
 *
 * This helper class allows you to easily create decorators for the Response
 * object.
 */
class ResponseDecorator implements ResponseInterface
{
    use MessageDecoratorTrait;
    
    /**
     * The inner request object.
     *
     * All method calls will be forwarded here.
     */
    protected ResponseInterface $inner;

    /**
     * Constructor.
     */
    public function __construct(ResponseInterface $inner)
    {
        $this->inner = $inner;
    }

    /**
     * Returns the current HTTP status code.
     */
    public function getStatus() : int
    {
        return $this->inner->getStatus();
    }

    /**
     * Returns the human-readable status string.
     *
     * In the case of a 200, this may for example be 'OK'.
     */
    public function getStatusText() : string
    {
        return $this->inner->getStatusText();
    }

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
     */
    public function setStatus($status) : void
    {
        $this->inner->setStatus($status);
    }

    /**
     * Serializes the request object as a string.
     *
     * This is useful for debugging purposes.
     */
    public function __toString() : string
    {
        return $this->inner->__toString();
    }
}