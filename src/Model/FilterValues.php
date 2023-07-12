<?php

namespace Picqer\BolRetailerV10\Model;

// This class is auto generated by OpenApi\ModelGenerator
class FilterValues extends AbstractModel
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
            'filterValueId' => [ 'model' => null, 'array' => false ],
            'filterValueName' => [ 'model' => null, 'array' => false ],
        ];
    }

    /**
     * @var string The unique identifier of the filter value.
     */
    public $filterValueId;

    /**
     * @var string The name of the filter value.
     */
    public $filterValueName;
}