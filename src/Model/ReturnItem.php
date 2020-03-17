<?php
namespace  Picqer\BolRetailer\Model;

use DateTime;

/**
 * @property int           $rmaId
 * @property string        $orderId
 * @property string        $ean
 * @property int           $quantity
 * @property DateTime      $registrationDateTime
 * @property string        $returnReason
 * @property string        $returnReasonComments
 * @property string        $fulfilmentMethod
 * @property bool          $handled
 * @property string|null   $trackAndTrace
 * @property string|null   $title
 * @property string|null   $handlingResult
 * @property string|null   $processingResult
 * @property DateTime|null $processingDateTime
 * @property array|null    $customerDetails
 */
class ReturnItem extends AbstractModel
{
    public const HANDLING_RESULT_RETURN_RECEIVED = 'RETURN_RECEIVED';
    public const HANDLING_RESULT_EXCHANGE_PRODCUT = 'EXCHANGE_PRODUCT';
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
}
