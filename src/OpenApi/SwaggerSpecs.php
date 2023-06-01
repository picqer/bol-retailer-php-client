<?php

namespace Picqer\BolRetailerV10\OpenApi;

class SwaggerSpecs
{
    private $specs = [];

    public function __construct($specs = [])
    {
        $this->specs = $specs;
    }

    public function load(string $file): SwaggerSpecs
    {
        $content = file_get_contents($file);
        $content = $this->replaceErroneousCharacters($content);

        $this->specs = json_decode($content, true);

        return $this;
    }

    private function replaceErroneousCharacters(string $content): string
    {
        $replacements = [
            hex2bin('e28082') => ' ', // 'ENSP' space
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    public function getSpecs(): array
    {
        return $this->specs;
    }

    public function merge(SwaggerSpecs $specs): SwaggerSpecs
    {
        $resultSpecs = $this->specs;
        $otherSpecs = $specs->getSpecs();

        $resultSpecs['paths'] = array_merge($resultSpecs['paths'], $otherSpecs['paths']);
        $resultSpecs['definitions'] = array_merge($resultSpecs['definitions'], $otherSpecs['definitions']);

        return new SwaggerSpecs($resultSpecs);
    }
}
