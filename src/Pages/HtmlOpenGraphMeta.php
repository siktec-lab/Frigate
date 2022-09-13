<?php

namespace Siktec\Frigate\Pages;

use \Siktec\Frigate\Tools\Arrays;

class HtmlOpenGraphMeta implements Arrays\ToArrayAccess {

    /**
     * @return array
     */
    public function to_array(string $prefix = "og:") : array {
        $values = [];
        foreach ($this as $name => $var) {
            $values[$prefix.$name] = is_object($var) ? $var->to_array() : $var;
        }
        return $values;
    }

    public ?string $title        = null;
    public ?string $description  = null;
    public ?string $url          = null;
    public ?string $type         = null;
    public ?string $image        = null;
    public ?string $locale       = null;

}