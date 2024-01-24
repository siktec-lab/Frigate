<?php

declare(strict_types=1);

namespace Frigate\Exceptions;

class FrigatePathException extends FrigateBaseException
{
    public const CODE_FRIGATE_PATH_GENERAL = 420;
    public const CODE_FRIGATE_PATH_MULTIPLE_ARGS = 421;
    public const CODE_FRIGATE_REDEFINE_PATH = 422;
    public const CODE_FRIGATE_EXTRA_PATH_AFTER_PATH_TYPE = 423;
    protected array $messages = [
        self::CODE_FRIGATE_PATH_GENERAL => [
            'Frigate Path general error', 0
        ],
        self::CODE_FRIGATE_PATH_MULTIPLE_ARGS => [
            "Cannot have same level arguments of the same type in path : '%s'", 1
        ],
        self::CODE_FRIGATE_REDEFINE_PATH => [
            "Cannot redefine path : '%s'", 1
        ],
        self::CODE_FRIGATE_EXTRA_PATH_AFTER_PATH_TYPE => [
            "Cannot have a path after a 'path' argument : '%s'", 1
        ],
    ];

}
