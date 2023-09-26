<?php

namespace Picqer\BolRetailerV9\Model;

// This class is auto generated by OpenApi\ModelGenerator
class ProductListResponse extends AbstractModel
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
            'products' => [ 'model' => ProductListProduct::class, 'array' => true ],
            'sort' => [ 'model' => null, 'array' => false ],
        ];
    }

    /**
     * @var ProductListProduct[] The list of products that is associated with the given search term or category and
     * filters.
     */
    public $products = [];

    /**
     * @var string Determines the order of the products.
     */
    public $sort;
}
