<?php
namespace Picqer\BolRetailer\Model;

use DateTime;

/**
 * Order item details of an order.
 *
 * @property string        $orderItemId        The id for the order item (1 order can have multiple order items).
 * @property string        $offerReference     Value provided by retailer through Offer API as `referenceCode`.
 * @property string        $ean                The EAN number associated with this product.
 * @property string        $title              Title of the product as shown on the webshop.
 * @property float         $offerPrice         The total price for this order (item price multiplied by the quantity).
 * @property float         $transactionFee.    Fee of the transaction.
 * @property DateTime|null $latestDeliveryDate Result of the date the order was placed combined with the delivery
 *                                             promise made by the retailer.
 * @property string        $offerCondition     Condition of the offer.
 * @property integer       $quantity           Amount of the product being ordered.
 * @property Order         $order              The order the order item belongs to.
 * @property bool          $cancelRequest      Indicates whether the order was cancelled on request of the customer
 *                                             before the retailer has shipped it.
 * @property string       $fulfilmentMethod    Specifies whether this shipment has been fulfilled by the retailer
 *                                             (`FBR`) or fulfilled by bol.com (`FBB`). Defaults to `FBR`.
 */
class OrderItem extends AbstractModel
{
    /** @var Order */
    private $order;

    /**
     * Constructor.
     *
     * @param Order $order The order the order item is for.
     * @param array $data  The data of the order item.
     */
    public function __construct(Order $order, array $data)
    {
        parent::__construct($data);

        $this->order = $order;
    }

    protected function getOrder(): Order
    {
        return $this->order;
    }

    protected function getLatestDeliveryDate(): ?DateTime
    {
        if (empty($this->data['latestDeliveryDate'])) {
            return null;
        }

        return DateTime::createFromFormat('Y-m-d', $this->data['latestDeliveryDate']);
    }
}
