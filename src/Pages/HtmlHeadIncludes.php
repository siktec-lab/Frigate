<?php

namespace Frigate\Pages;

use Frigate\Helpers;

class HtmlHeadIncludes implements Helpers\Interfaces\ToArrayAccess {
    
    use Helpers\Traits\toArrayTrait;

    public array $scripts   = [];
    public array $links     = [];
    public array $css       = [];

    public function include_script(
        string  $src, 
        string  $type = "text/javascript",
        string  $id = "",
        ?string $charset = "UTF-8", 
        bool    $async = false,
        bool    $defer = false,
        ?string $crossorigin = null,
    ) : void {
        $this->scripts[] = compact("src","type","id","charset","async","defer","crossorigin"); 
    }

    public function include_links(
        string  $href, 
        string  $type = "text/css", 
        ?string $rel = "stylesheet",
        string  $id = "",
        ?string $sizes = null, 
        ?string $charset = null,
        ?string $crossorigin = null
    ) : void {
        $this->css[] = compact("href","type","rel","id","sizes","charset","crossorigin"); 
    }

}