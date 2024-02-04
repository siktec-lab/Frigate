<?php

declare(strict_types=1);

namespace Frigate\Exceptions;

class FrigateCliException extends FrigateBaseException
{
    public const CODE_FRIGATE_CLI_GENERAL = 650;
    public const CODE_FRIGATE_CLI_COM_CONSTANT = 651;

    protected array $messages = [
        self::CODE_FRIGATE_CLI_GENERAL => [
            'Frigate CLI runtime error', 0
        ],
        self::CODE_FRIGATE_CLI_COM_CONSTANT => [
            "CLI Command must implement COMMAND constant in : '%s'", 1
        ]
    ];
}
