<?php


namespace Picqer\BolRetailerV4;

// TODO Auto generate this class based on openAPI
use Picqer\BolRetailerV4\Model\ReducedOrders;

class Client extends BaseClient
{
    /**
     * Gets a paginated list of all open orders sorted by date in descending order.
     * @param int $page The requested page number with a page size of 50 items.
     * @param string $fulfilmentMethod The fulfilment method. Fulfilled by the retailer (FBR) or fulfilled by bol.com
     * (FBB).
     * @return ReducedOrders
     * @throws Exception\ResponseException
     */
    public function getOrders(int $page = 1, string $fulfilmentMethod = 'FBR'): ReducedOrders
    {
        // This is an example method that could be generated from the OpenAPI specs
        $query = ['page' => $page, 'fulfilment-method' => $fulfilmentMethod];

        /** @var Model\ReducedOrders $reducedOrders */
        return $this->request('GET', 'orders', ['query' => $query], 'ReducedOrders');
    }
}
