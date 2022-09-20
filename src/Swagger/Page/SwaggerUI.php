<?php

namespace Siktec\Frigate\Swagger\Page;

use \Siktec\Frigate\Pages\Page as PageBuilder;

class SwaggerUI extends PageBuilder {

    public string $base;
    public string $lib;
    public string $vendor;


    public string $state = "";

    public function __construct(string $state = "default")
    {
        parent::__construct(APP_BASE_URL);

        $this->use_templates(__DIR__.DS."templates");

        //Set meta:
        $this->meta->title          = "Api Documentation";
        $this->meta->description    = "SwaggerUI";
        $this->meta->viewport       = "width=device-width, initial-scale=1.0,  shrink-to-fit=no";
      
        //Sources:
        $this->sources->head->include_links("https://unpkg.com/swagger-ui-dist@4.5.0/swagger-ui.css");
        
        $this->sources->head->include_script("https://unpkg.com/swagger-ui-dist@4.5.0/swagger-ui-bundle.js", crossorigin: "anonymous");
                
        //State : 
        $this->state = $state;

    }

    public function compile() : string
    {
        $context = [
            "meta"      => $this->meta->to_array(),
            "opengraph" => $this->opengraph->to_array(),
            "sources"   => $this->sources->to_array(),
            "state"     => $this->state
        ];
        return $this->templating->render("swagger-basic.twig", $context);
    }
}


