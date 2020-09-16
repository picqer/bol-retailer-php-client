<?php

namespace Picqer\BolRetailer;

use GuzzleHttp\Exception\ClientException;
use Picqer\BolRetailer\Exception\HttpException;
use Picqer\BolRetailer\Exception\OfferNotFoundException;
use Picqer\BolRetailer\Exception\RateLimitException;

class Offer extends Model\Offer
{
    /**
     * Get an offer by its identifier.
     *
     * @param string $id The identifier of the offer to retrieve.
     *
     * @return self
     */
    public static function get(string $id): Offer
    {
        try {
            $response = Client::request('GET', "offers/${id}");
        } catch (ClientException $e) {
            static::handleException($e);
        }

        return new Offer(json_decode((string)$response->getBody(), true));
    }

    /**
     * Create a new offer.
     *
     * @param array $data The data of the offer to create.
     *
     * @return ProcessStatus
     */
    public static function create(array $data): ProcessStatus
    {
        try {
            $response = Client::request('POST', "offers", ['body' => json_encode($data)]);
        } catch (ClientException $e) {
            static::handleException($e);
        }

        return new ProcessStatus(json_decode((string)$response->getBody(), true));
    }

    /**
     * Refresh the details of the offer.
     */
    public function refresh(): void
    {
        $id = $this->offerId;

        try {
            $response = Client::request('GET', "offers/${id}");

            $this->merge(json_decode((string)$response->getBody(), true));
        } catch (ClientException $e) {
            static::handleException($e);
        }
    }

    /**
     * Update the details of an offer.
     *
     * @param array $data The new details of the offer.
     *
     * @return ProcessStatus
     */
    public function update(array $data): ProcessStatus
    {
        $id = $this->offerId;

        try {
            $response = Client::request('PUT', "offers/${id}", ['body' => json_encode($data)]);
        } catch (ClientException $e) {
            static::handleException($e);
        }

        return new ProcessStatus(json_decode((string)$response->getBody(), true));
    }

    /**
     * Update the stock level of an offer.
     *
     * @param integer $amount The stock level of the offer.
     * @param bool $managedByRetailer Configures whether the retailer manages the stock levels or that bol.com
     *                                   should calculate the corrected stock based on actual open orders. In case the
     *                                   configuration is set to `false`, all open orders are used to calculate the
     *                                   corrected stock. In case the configuration is set to `true`, only orders that
     *                                   are placed after the last offer update are taken into account. Default is set
     *                                   to `false`.
     */
    public function updateStock(int $amount, bool $managedByRetailer = true): ProcessStatus
    {
        $id = $this->offerId;
        $content = json_encode(['amount' => $amount, 'managedByRetailer' => $managedByRetailer]);

        try {
            $response = Client::request('PUT', "offers/${id}/stock", ['body' => $content]);
        } catch (ClientException $e) {
            static::handleException($e);
        }

        return new ProcessStatus(json_decode((string)$response->getBody(), true));
    }

    /**
     * Update pricing of an offer.
     *
     * @param array $bundlePrices The bundle prices of the offer.
     */
    public function updatePricing(array $bundlePrices): ProcessStatus
    {
        $id = $this->offerId;
        $content = json_encode(['pricing' => ['bundlePrices'=> $bundlePrices]]);

        try {
            $response = Client::request('PUT', "offers/${id}/price", ['body' => $content]);
        } catch (ClientException $e) {
            static::handleException($e);
        }

        return new ProcessStatus(json_decode((string)$response->getBody(), true));
    }

    /**
     * Delete an existing offer.
     *
     * @return ProcessStatus
     */
    public function delete(): ProcessStatus
    {
        $id = $this->offerId;

        try {
            $response = Client::request('DELETE', "offers/${id}");
        } catch (ClientException $e) {
            static::handleException($e);
        }

        return new ProcessStatus(json_decode((string)$response->getBody(), true));
    }

    /**
     * Exports all offers
     *
     * @param string $format The format to export the offers to.
     *
     * @return ProcessStatus
     */
    public static function export(string $format = 'CSV'): ProcessStatus
    {
        $content = json_encode(['format' => $format]);

        try {
            $response = Client::request('POST', "offers/export", ['body' => $content]);
        } catch (ClientException $e) {
            static::handleException($e);
        }

        return new ProcessStatus(json_decode((string)$response->getBody(), true));
    }

    /**
     * Returns offer export
     *
     * @param string $id The identifier of the offer to get the export for.
     *
     * @return string
     */
    public static function getExport(string $id): string
    {
        $headers = ['Accept' => 'application/vnd.retailer.v3+csv'];

        try {
            $response = Client::request('GET', "offers/export/${id}", ['headers' => $headers]);
        } catch (ClientException $e) {
            static::handleException($e);
        }

        return (string)$response->getBody();
    }

    private static function handleException(ClientException $e): void
    {
        $response = $e->getResponse();

        if ($response && $response->getStatusCode() === 404) {
            throw new OfferNotFoundException(
                json_decode((string)$response->getBody(), true),
                404,
                $e
            );
        } elseif ($response && $response->getStatusCode() === 429) {
            throw new RateLimitException(
                json_decode((string)$response->getBody(), true),
                429,
                $e
            );
        } elseif ($response) {
            throw new HttpException(
                json_decode((string)$response->getBody(), true),
                $response->getStatusCode(),
                $e
            );
        }

        throw $e;
    }
}
