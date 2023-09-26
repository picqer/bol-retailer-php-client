<?php

namespace Picqer\BolRetailerV9\Model;

// This class is auto generated by OpenApi\ModelGenerator
class PriceStarBoundaryLevels extends AbstractModel
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
            'level' => [ 'model' => null, 'array' => false ],
            'boundaryPrice' => [ 'model' => null, 'array' => false ],
        ];
    }

    /**
     * @var int The level of the price star boundary.
     */
    public $level;

    /**
     * @var float The boundary price of the corresponding level.
     */
    public $boundaryPrice;
}
