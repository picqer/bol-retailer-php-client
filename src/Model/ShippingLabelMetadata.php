<?php

namespace Picqer\BolRetailer\Model;

class ShippingLabelMetadata extends AbstractModel
{
    public function getModelDefinition(): array
    {
        return [
            'transporterCode' => [ 'model' => null, 'array' => false ],
            'trackAndTrace' => [ 'model' => null, 'array' => false ],
        ];
    }

    /**
     * @var string Transporter that will carry out the shipment.
     */
    public $transporterCode;

    /**
     * @var string The track and trace code.
     */
    public $trackAndTrace;
}
