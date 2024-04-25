<?php

namespace Picqer\BolRetailerV10\Model;

use Picqer\BolRetailerV10\Enum;

// This class is auto generated by OpenApi\ModelGenerator
class CommissionRate extends AbstractModel
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
            'ean' => [ 'model' => null, 'enum' => null, 'array' => false ],
            'dateRanges' => [ 'model' => CommissionDateRange::class, 'enum' => null, 'array' => true ],
        ];
    }

    /**
     * @var string The EAN number associated with this product.
     */
    public $ean;

    /**
     * @var CommissionDateRange[] An array of objects, each describing a period during which certain commission rates
     * apply.
     */
    public $dateRanges = [];
}