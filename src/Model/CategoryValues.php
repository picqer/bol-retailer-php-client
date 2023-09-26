<?php

namespace Picqer\BolRetailerV9\Model;

// This class is auto generated by OpenApi\ModelGenerator
class CategoryValues extends AbstractModel
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
            'categoryValueId' => [ 'model' => null, 'array' => false ],
            'categoryValueName' => [ 'model' => null, 'array' => false ],
        ];
    }

    /**
     * @var string The id of the category. This id can be used in other endpoints, like Get product list.
     */
    public $categoryValueId;

    /**
     * @var string The name of the category.
     */
    public $categoryValueName;
}
