<?php 

namespace Frigate\Api\Traits;

trait ContextHelpers {
    public function getFromContext(string $key, array $context, mixed $default = null) : mixed
    {
        return array_key_exists($key, $context) ? $context[$key] : $default;
    }
}