<?php

namespace Picqer\BolRetailerV9\Model;

// This class is auto generated by OpenApi\ModelGenerator
class SubCategory extends AbstractModel
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
            'name' => [ 'model' => null, 'array' => false ],
            'subcategories' => [ 'model' => SubCategory::class, 'array' => true ],
        ];
    }

    /**
     * @var string The id of the subcategory which the product belongs to.
     */
    public $id;

    /**
     * @var string The name of the subcategory which the product belongs to.
     */
    public $name;

    /**
     * @var SubCategory[] The subcategories which the product belongs to.
     */
    public $subcategories = [];
}
