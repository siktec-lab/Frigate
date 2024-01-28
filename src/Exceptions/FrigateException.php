<?php

declare(strict_types=1);

namespace Frigate\Exceptions;

class FrigateException extends FrigateBaseException
{
    public const CODE_FRIGATE_GENERAL = 410;
    public const CODE_FRIGATE_ENV_ERROR = 411;

    protected array $messages = [
        self::CODE_FRIGATE_GENERAL => ['Frigate general error', 0],
        self::CODE_FRIGATE_ENV_ERROR => ['Frigate environment variable error : %s', 1]
    ];

}
