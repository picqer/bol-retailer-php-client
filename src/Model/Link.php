<?php

namespace Picqer\BolRetailerV9\Model;

// This class is auto generated by OpenApi\ModelGenerator
class Link extends AbstractModel
{
    /**
     * Returns the definition of the model: an associative array with field names as key and
     * field definition as value. The field definition contains of
     * model: Model class or null if it is a scalar type
     * array: Boolean whether it is an array
     * @return array The model definition
     */
    public function getModelDefinition(): array
    {
        return [
            'rel' => [ 'model' => null, 'array' => false ],
            'href' => [ 'model' => null, 'array' => false ],
            'method' => [ 'model' => null, 'array' => false ],
        ];
    }

    /**
     * @var string The link relation.
     */
    public $rel;

    /**
     * @var string The URI for the resource linked to.
     */
    public $href;

    /**
     * @var string The HTTP method to use when accessing the link.
     */
    public $method;
}
