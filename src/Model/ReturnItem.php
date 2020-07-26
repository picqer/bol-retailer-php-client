<?php

namespace Picqer\BolRetailer\Model;

use DateTime;

/**
 * @property int $rmaId                The RMA (Return Merchandise Authorization) id that identifies this particular return.
 * @property string $orderId              The id of the customer order this return item is in.
 * @property string $ean                  The EAN number associated with this product.
 * @property int $quantity             The quantity that is returned by the customer.
 * @property DateTime $registrationDateTime The date and time when this return was registered.
 * @property string $returnReason         The reason why the customer returned this product.
 * @property string $returnReasonComments Additional details from the customer as to why this item was returned.
 * @property string $fulfilmentMethod     Specifies whether this shipment has been fulfilled by the retailer (FBR) or fulfilled by bol.com (FBB). Defaults to FBR.
 * @property bool $handled              Indicates if this return item has been handled (by the retailer).
 * @property string|null $trackAndTrace        The track and trace code that is associated with this transport.
 * @property string|null $title                The product title.
 * @property string|null $handlingResult       The handling result requested by the retailer.
 * @property string|null $processingResult     The processing result of the return.
 * @property DateTime|null $processingDateTime   The date and time when the return was processed.
 * @property AddressDetails|null $customerDetails      The customer details
 */
class ReturnItem extends AbstractModel
{
    public const HANDLING_RESULT_RETURN_RECEIVED = 'RETURN_RECEIVED';
    public const HANDLING_RESULT_EXCHANGE_PRODUCT = 'EXCHANGE_PRODUCT';
    public const HANDLING_RESULT_RETURN_DOES_NOT_MEET_CONDITIONS = "RETURN_DOES_NOT_MEET_CONDITIONS";
    public const HANDLING_RESULT_REPAIR_PRODUCT = "REPAIR_PRODUCT";
    public const HANDLING_RESULT_CUSTOMER_KEEPS_PRODUCT_PAID = "CUSTOMER_KEEPS_PRODUCT_PAID";
    public const HANDLING_RESULT_STILL_APPROVED = "STILL_APPROVED";

    protected function getRegistrationDateTime(): ?DateTime
    {
        if (empty($this->data['registrationDateTime'])) {
            return null;
        }

        return DateTime::createFromFormat(DateTime::ATOM, $this->data['registrationDateTime']);
    }

    protected function getProcessingDateTime(): ?DateTime
    {
        if (empty($this->data['processingDateTime'])) {
            return null;
        }

        return DateTime::createFromFormat(DateTime::ATOM, $this->data['processingDateTime']);
    }

    protected function getCustomerDetails(): ?AddressDetails
    {
        if (empty($this->data['customerDetails'])) {
            return null;
        }

        return new AddressDetails($this->data['customerDetails']);
    }
}
