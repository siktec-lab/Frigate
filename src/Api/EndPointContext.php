<?php 

namespace Siktec\Frigate\Api;

trait EndPointContext {

    public function get_context(string $key, array $context, mixed $default = null) : mixed
    {
      return array_key_exists($key, $context) ? $context[$key] : $default;
    }
}