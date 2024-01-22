<?php

namespace Frigate\Pages;

use Frigate\Tools\Arrays;

class PageSources implements Arrays\ToArrayAccess {

    use Arrays\toArrayTrait;
    
    public HtmlHeadIncludes $head;
    public HtmlBodyIncludes $body;

    public function __construct()
    {
        $this->head = new HtmlHeadIncludes();
        $this->body = new HtmlBodyIncludes();
    }
    
}