<?php

declare(strict_types=1);

namespace Frigate\Exceptions;

class FrigateException extends FrigateBaseException
{
    public const CODE_FRIGATE_GENERAL = 410;

    protected array $messages = [
        self::CODE_FRIGATE_GENERAL => ['Frigate general error', 0],
    ];

}
