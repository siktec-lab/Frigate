<?php

namespace Frigate\Pages;

use Frigate\Helpers;

class PageSources implements Helpers\Interfaces\ToArrayAccess {

    use Helpers\Traits\toArrayTrait;
    
    public HtmlHeadIncludes $head;
    public HtmlBodyIncludes $body;

    public function __construct()
    {
        $this->head = new HtmlHeadIncludes();
        $this->body = new HtmlBodyIncludes();
    }
    
}