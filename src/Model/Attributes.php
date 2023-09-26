<?php

namespace Picqer\BolRetailerV9\Model;

// This class is auto generated by OpenApi\ModelGenerator
class Attributes extends AbstractModel
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
            'id' => [ 'model' => null, 'array' => false ],
            'values' => [ 'model' => Values::class, 'array' => true ],
        ];
    }

    /**
     * @var string The identifier of the attribute.
     */
    public $id;

    /**
     * @var Values[]
     */
    public $values = [];
}
