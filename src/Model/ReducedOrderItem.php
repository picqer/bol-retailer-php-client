<?php
namespace Picqer\BolRetailer\Model;

/**
 * @property string       $orderItemId   The id for the order item (1 order can have multiple order items).
 * @property string       $ean           The EAN number associated with this product.
 * @property integer      $quantity      Amount of the product being ordered.
 * @property ReducedOrder $order         The order the order item belongs to.
 * @property bool         $cancelRequest Indicates whether the order was cancelled on request of the customer
 *                                       before the retailer has shipped it.
 */
class ReducedOrderItem extends AbstractModel
{
    /** @var ReducedOrder */
    private $order;

    /**
     * Constructor.
     *
     * @param ReducedOrder $order The order the order item is for.
     * @param array        $data  The data of the order item.
     */
    public function __construct(ReducedOrder $order, array $data)
    {
        parent::__construct($data);

        $this->order = $order;
    }

    protected function getOrder(): ReducedOrder
    {
        return $this->order;
    }

    protected function getCancelRequest(): bool
    {
        return (bool) $this->data['cancelRequest'];
    }
}
