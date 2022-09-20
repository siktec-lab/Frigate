<?php

namespace Siktec\Frigate\Swagger;


use \OpenApi\Generator;

use \Siktec\Frigate\Pages\Page as PageBuilder;

$openapi = \OpenApi\Generator::scan([
    "./api",
]);

header('Content-Type: application/json');

echo $openapi->toJSON();

class Parser {

    private array $sources;
    private string $output;
    private array $options;

    const OUTPUT_JSON = "json";
    const OUTPUT_YAML = "yaml";

    public function __construct(iterable $scan = [], string $output = "json", array $options = [])
    {
        $this->sources = $scan;
        $this->output  = $output;
        $this->options = $options;

    }

    public function generate() : string
    {
        $openapi = \OpenApi\Generator::scan(
            $this->sources,
            $this->options
        );
        if ($this->output === self::OUTPUT_JSON) {
            return $openapi->toJSON();
        } else {
            return $openapi->toYaml();
        }
    }
}


