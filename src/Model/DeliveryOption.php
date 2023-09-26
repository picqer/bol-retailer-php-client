<?php

namespace Picqer\BolRetailerV9\Model;

// This class is auto generated by OpenApi\ModelGenerator
class DeliveryOption extends AbstractModel
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
            'shippingLabelOfferId' => [ 'model' => null, 'array' => false ],
            'recommended' => [ 'model' => null, 'array' => false ],
            'validUntilDate' => [ 'model' => null, 'array' => false ],
            'transporterCode' => [ 'model' => null, 'array' => false ],
            'labelType' => [ 'model' => null, 'array' => false ],
            'labelDisplayName' => [ 'model' => null, 'array' => false ],
            'labelPrice' => [ 'model' => LabelPrice::class, 'array' => false ],
            'packageRestrictions' => [ 'model' => PackageRestrictions::class, 'array' => false ],
            'handoverDetails' => [ 'model' => HandoverDetails::class, 'array' => false ],
        ];
    }

    /**
     * @var string Unique identifier for the shipping label offer.
     */
    public $shippingLabelOfferId;

    /**
     * @var bool Indicates whether this delivery option is recommended to be the best option to ship your order item(s)
     * with.
     */
    public $recommended;

    /**
     * @var string The date until the delivery option (incl total price) is valid.
     */
    public $validUntilDate;

    /**
     * @var string A code representing the transporter which is being used for transportation.
     */
    public $transporterCode;

    /**
     * @var string The type of the label, representing the way an item is being transported. MAILBOX is a mailbox
     * package with delivery scan. MAILBOX_LIGHT is a mailbox package without delivery scan. PARCEL is a normal package.
     */
    public $labelType;

    /**
     * @var string The display name of the shipping label.
     */
    public $labelDisplayName;

    /**
     * @var LabelPrice
     */
    public $labelPrice;

    /**
     * @var PackageRestrictions
     */
    public $packageRestrictions;

    /**
     * @var HandoverDetails
     */
    public $handoverDetails;

    /**
     * Returns totalPrice from labelPrice.
     * @return float TotalPrice from labelPrice.
     */
    public function getLabelPriceTotalPrice(): float
    {
        return $this->labelPrice->totalPrice;
    }

    /**
     * Sets labelPrice by totalPrice.
     * @param float $totalPrice TotalPrice for labelPrice.
     */
    public function setLabelPriceTotalPrice(float $totalPrice): void
    {
        $this->labelPrice = LabelPrice::constructFromArray(['totalPrice' => $totalPrice]);
    }
}
