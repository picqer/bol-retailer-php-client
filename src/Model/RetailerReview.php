<?php

namespace Picqer\BolRetailerV9\Model;

// This class is auto generated by OpenApi\ModelGenerator
class RetailerReview extends AbstractModel
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
            'totalReviewCount' => [ 'model' => null, 'array' => false ],
            'approvalPercentage' => [ 'model' => null, 'array' => false ],
            'positiveReviewCount' => [ 'model' => null, 'array' => false ],
            'neutralReviewCount' => [ 'model' => null, 'array' => false ],
            'negativeReviewCount' => [ 'model' => null, 'array' => false ],
        ];
    }

    /**
     * @var int The total amount of customer review during that rating method period.
     */
    public $totalReviewCount;

    /**
     * @var int The percentage of the amount of customer that rated the retailer either neutral or positive during the
     * rating method period.
     */
    public $approvalPercentage;

    /**
     * @var int The amount of positive customer reviews during that rating method period.
     */
    public $positiveReviewCount;

    /**
     * @var int The amount of neutral customer reviews during that rating method period.
     */
    public $neutralReviewCount;

    /**
     * @var int The amount of negative customer reviews during that rating method period.
     */
    public $negativeReviewCount;
}
