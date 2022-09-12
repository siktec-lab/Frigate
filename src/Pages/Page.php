<?php

namespace Siktec\Frigate\Pages;

use \Twig\Loader\FilesystemLoader;
use \Twig\Environment;

abstract class Page {

    protected ?FilesystemLoader $template_loader = null;
    protected ?Environment $templating  = null;

    public HtmlMeta $meta;

    public PageSources $sources;

    public function __construct()
    {
        $this->meta = new HtmlMeta();
        $this->sources = new PageSources();
    }

    public function use_templates(string|array $templates, ?string $cache_path = null) : void {
        $this->template_loader = new FilesystemLoader($templates);
        $this->templating = new Environment($this->template_loader,[
            "cache"         => rtrim($cache_path ?? $templates, "\\/ ")."/cache",
            "auto_reload"   => true,
            "charset"       => "UTF-8",
            'autoescape'    => 'html',
            'optimizations' => -1 // all optimizations are enabled 
        ]);
    }

    abstract function compile() : string;

    public function render() : void {
        echo $this->compile();
    }

}

