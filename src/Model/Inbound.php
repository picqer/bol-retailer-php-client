<?php

namespace Picqer\BolRetailerV4\Model;

// This class is auto generated by OpenApi\ModelGenerator
class Inbound extends AbstractModel
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
            'inboundId' => [ 'model' => null, 'array' => false ],
            'reference' => [ 'model' => null, 'array' => false ],
            'creationDateTime' => [ 'model' => null, 'array' => false ],
            'state' => [ 'model' => null, 'array' => false ],
            'labellingService' => [ 'model' => null, 'array' => false ],
            'announcedBSKUs' => [ 'model' => null, 'array' => false ],
            'announcedQuantity' => [ 'model' => null, 'array' => false ],
            'receivedBSKUs' => [ 'model' => null, 'array' => false ],
            'receivedQuantity' => [ 'model' => null, 'array' => false ],
            'timeSlot' => [ 'model' => TimeSlot::class, 'array' => false ],
            'products' => [ 'model' => Product::class, 'array' => true ],
            'stateTransitions' => [ 'model' => StateTransition::class, 'array' => true ],
            'inboundTransporter' => [ 'model' => Transporter::class, 'array' => false ],
        ];
    }

    /**
     * @var int A unique identifier for an inbound shipment.
     */
    public $inboundId;

    /**
     * @var string A user defined reference to identify the inbound shipment.
     */
    public $reference;

    /**
     * @var string The date and time the inbound shipment was created, in ISO 8601 format.
     */
    public $creationDateTime;

    /**
     * @var string The current state of the inbound shipment.
     */
    public $state;

    /**
     * @var bool Indicates whether the inbound will be labeled by bol.com or not.
     */
    public $labellingService;

    /**
     * @var int The number of announced BSKU‘s.
     */
    public $announcedBSKUs;

    /**
     * @var int The number of announced items.
     */
    public $announcedQuantity;

    /**
     * @var int Number of lines that were scanned in our warehouse. This value does not provide the unique number of received bsku's.
     */
    public $receivedBSKUs;

    /**
     * @var int The number of received items.
     */
    public $receivedQuantity;

    /**
     * @var TimeSlot The timeslot within which your shipment is expected to arrive at the warehouse.
     */
    public $timeSlot;

    /**
     * @var Product[] List of products.
     */
    public $products = [];

    /**
     * @var StateTransition[] List of state transitions.
     */
    public $stateTransitions = [];

    /**
     * @var Transporter Transporter for the inbound shipment.
     */
    public $inboundTransporter;

    public function getCreationDateTime(): ?\DateTime
    {
        if (empty($this->creationDateTime)) {
            return null;
        }

        return \DateTime::createFromFormat(\DateTime::ATOM, $this->creationDateTime);
    }
}