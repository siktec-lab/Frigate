<?php

declare(strict_types=1);

namespace Frigate\Exceptions;

use Exception;

class FrigateBaseException extends Exception
{

    public const CODE_UNKNOWN_ERROR = 600;
    
    protected const MESSAGE_UNKNOWN_ERROR = "Unknown error";

    protected array $messages = [];

    /**
     * FrigateBaseException constructor.
     *
     * @param int $code 0 will be an unknown error (self::UNKNOWN_ERROR = 140)
     * @param array<mixed> $args
     * @param Exception|null $previous
     * @param string|null $message a custom message for the exception will override the default message
     */
    public function __construct(int $code = 0, array $args = [], ?Exception $previous = null, ?string $message = null)
    {

        // Prepare the message:
        [$code, $message] = $message ?? $this->buildMessage($code, $args);

        // make sure everything is assigned properly
        parent::__construct($message ?? "", $code, $previous);
    }

    // custom string representation of object
    /**
     * Build the message from the code and the arguments
     * @param int $code
     * @param array<mixed> $args
     * @return array{int,string} [code, message]
     */
    private function buildMessage(int $code, array $args = []) : array
    {

        // Code 0 is an unknown error
        $code = $code === 0 ? self::CODE_UNKNOWN_ERROR : $code;

        // Get the message and the number of arguments
        [$message, $num_args] = $this->messages[$code] ?? [self::MESSAGE_UNKNOWN_ERROR, 0];

        $apply = [];
        for ($i = 0; $i < $num_args; $i++) {
            $apply[] = $args[$i] ?? "";
        }
        return [$code, sprintf($message, ...$apply)];
    }

    public function __toString() : string
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
