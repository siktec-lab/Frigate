<?php

namespace Frigate\Pages;

use Twig\Loader\FilesystemLoader;
use Twig\Environment;

abstract class Page {

    protected ?FilesystemLoader $template_loader = null;
    protected ?Environment $templating  = null;

    public HtmlMeta $meta;
    public HtmlOpenGraphMeta $opengraph;

    public PageSources $sources;

    public string $url_base = "";

    public function __construct(string $url_base = "/")
    {
        $this->meta          = new HtmlMeta();
        $this->opengraph    = new HtmlOpenGraphMeta();
        $this->sources       = new PageSources();
        $this->url_base      = rtrim($url_base, " \n\t\r\0\x0B/\\");
        $this->meta->baseurl = $this->url_base;
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

    public function tokenize(bool $reuse = false) : void {
        if ($reuse) {
            $this->meta->csrf = $_SESSION["page-csrf"] ?? md5(uniqid(mt_rand(), true));
            $_SESSION["page-csrf"] = $this->meta->csrf;
        } else {
            $_SESSION['page-csrf'] = md5(uniqid(mt_rand(), true));
            $this->meta->csrf = $_SESSION['page-csrf'];
        }
    }

    abstract function compile() : string;

    public function render() : void {
        echo $this->compile();
    }

}

