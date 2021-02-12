<?php

namespace Picqer\BolRetailerV4\OpenApi;

use Picqer\BolRetailerV4\Model\AbstractModel;

class ModelCreator
{
    protected $specs;

    public function __construct()
    {
        $this->specs = json_decode(file_get_contents(__DIR__ . '/apispec.json'), true);
    }

    public function createInstance($type, $data): AbstractModel
    {
        // TODO throw exception if data is not there

        $modelDefinition = $this->specs['definitions'][$type];
        $modelFqName = $this->getModelNamespace() . '\\' . $type;
        $model = new $modelFqName();

        foreach ($modelDefinition['properties'] as $name => $propDefinition) {
            if (isset($propDefinition['type'])) {
                if ($propDefinition['type'] == 'array' && isset($propDefinition['items']['$ref'])) {
                    // Array of Models
                    $propType = $this->getType($propDefinition['items']['$ref']);
                    $model->$name = array_map(function ($data) use ($propType) {
                        return $this->createInstance($propType, $data);
                    }, $data[$name]);
                } else {
                    // Scalar type or simple array
                    $model->$name = $data[$name];
                }
            } elseif (isset($propDefinition['$ref'])) {
                // Model
                $propType = $this->getType($propDefinition['$ref']);
                $model->$name = $this->createInstance($propType, $data[$name]);
            } else {
                // TODO create exception class for this one
                throw new \Exception('Unknown property definition');
            }
        }

        return $model;
    }

    public function getResponseType($method, $url, $status): ?string
    {
        $method = strtolower($method);
        $status = (string) $status;

        foreach ($this->specs['paths'] as $path => $methodSpecs) {
            if (! $this->urlMatchesPath($url, $path)) {
                continue;
            }

            if (! isset($methodSpecs[$method])) {
                return null;
            }

            if (! isset($methodSpecs[$method]['responses'][$status])) {
                return null;
            }

            $ref = $methodSpecs[$method]['responses'][$status]['schema']['$ref'];
            return $this->getType($ref);
        }

        return null;
    }

    protected function getType(string $ref): string
    {
        //strip #/definitions/
        return substr($ref, strrpos($ref, '/') + 1);
    }

    protected function urlMatchesPath($url, $path): bool
    {
        $urlPattern = preg_replace('/\{[^\}]+\}/', '__wildcard__', $path);
        $urlPattern = preg_quote($urlPattern, '/');
        $urlPattern = '/^' . str_replace('__wildcard__', '[^\/]+', $urlPattern) . '$/';

        return preg_match($urlPattern, $url);
    }

    protected function getModelNamespace(): string
    {
        $namespace = substr(__NAMESPACE__, 0, strrpos(__NAMESPACE__, '\\'));
        return $namespace . '\Model';
    }
}
