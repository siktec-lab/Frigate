<?php

namespace Frigate\Helpers\Traits;

trait toArrayTrait
{
    /**
     * @return array
     */
    public function to_array() : array {
        $values = [];
        foreach ($this as $name => $var) {
            $values[$name] = is_object($var) ? $var->to_array() : $var;
        }
        return $values;
    }

}