<?php

namespace Picqer\BolRetailer;

trait CustomMethods
{
    /**
     * Gets a shipping label metadata by shipping label id.
     * @param string $shippingLabelId The shipping label id.
     * @return Model\ShippingLabelMetadata|null
     * @throws Exception\ConnectException when an error occurred in the HTTP connection.
     * @throws Exception\ResponseException when an unexpected response was received.
     * @throws Exception\UnauthorizedException when the request was unauthorized.
     * @throws Exception\RateLimitException when the throttling limit has been reached for the API user.
     * @throws Exception\Exception when something unexpected went wrong.
     */
    public function getShippingLabelMetaData(string $shippingLabelId): ?Model\ShippingLabelMetadata
    {
        $url = "shipping-labels/${shippingLabelId}";
        $options = [
            'produces' => 'application/vnd.retailer.v4+pdf',
            'response-headers-mapping' => [
                'X-Transporter-Code' => 'transporterCode',
                'X-Track-And-Trace-Code' => 'trackAndTrace',
            ],
        ];
        $responseTypes = [
            '200' => Model\ShippingLabelMetadata::class,
            '404' => 'null',
        ];

        return $this->request('HEAD', $url, $options, $responseTypes);
    }
}
