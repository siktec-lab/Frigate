<?php

namespace Frigate\Pages;

use Frigate\Tools\Arrays;

class HtmlMeta implements Arrays\ToArrayAccess {

    use Arrays\toArrayTrait;
    
    public ?string $lang           = "en";
    public ?string $charset        = "UTF-8";
    public ?string $title          = "Page";
    public ?string $description    = null;
    public ?string $viewport       = "width=device-width, initial-scale=1.0,  shrink-to-fit=no";
    public ?string $favicon_ico    = null;
    public ?string $favicon_png16  = null;
    public ?string $favicon_png32  = null;
    public ?string $favicon_png180 = null;
    public ?string $manifest       = null;
    public ?string $csrf           = null;
    public ?string $baseurl        = null;
}