<?php
namespace Picqer\BolRetailer\Model;

/**
 * Customer details of an order.
 *
 * @property AddressDetails $billingDetails
 * @property AddressDetails $shipmentDetails
 */
class OrderCustomerDetails extends AbstractModel
{
    protected function getBillingDetails(): AddressDetails
    {
        return new AddressDetails($this->data['billingDetails']);
    }

    protected function getShipmentDetails(): AddressDetails
    {
        return new AddressDetails($this->data['shipmentDetails']);
    }
}
