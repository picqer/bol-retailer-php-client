<?php
namespace Picqer\BolRetailer;

use GuzzleHttp\Exception\ClientException;
use Picqer\BolRetailer\Exception\HttpException;
use Picqer\BolRetailer\Exception\OfferNotFoundException;

class Offer extends Model\Offer
{
    /**
     * Get an offer by its identifier.
     *
     * @param string $id The identifier of the offer to retrieve.
     *
     * @return Offer
     */
    public static function get(string $id): Offer
    {
        try {
            $response = Client::request('GET', "offers/${id}");

            return new Offer(json_decode((string) $response->getBody(), true));
        } catch (ClientException $e) {
            static::handleException($e);
        }
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

            return new ProcessStatus(json_decode((string) $response->getBody(), true));
        } catch (ClientException $e) {
            static::handleException($e);
        }
    }

    /**
     * Refresh the details of the offer.
     */
    public function refresh(): void
    {
        $id = $this->offerId;

        try {
            $response = Client::request('GET', "offers/${id}");

            $this->merge(json_decode((string) $response->getBody(), true));
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

            return new ProcessStatus(json_decode((string) $response->getBody(), true));
        } catch (ClientException $e) {
            static::handleException($e);
        }
    }

    /**
     * Update the stock level of an offer.
     *
     * @param integer $amount            The stock level of the offer.
     * @param bool    $managedByRetailer Configures whether the retailer manages the stock levels or that bol.com
     *                                   should calculate the corrected stock based on actual open orders. In case the
     *                                   configuration is set to `false`, all open orders are used to calculate the
     *                                   corrected stock. In case the configuration is set to `true`, only orders that
     *                                   are placed after the last offer update are taken into account. Default is set
     *                                   to `false`.
     */
    public function updateStock(int $amount, bool $managedByRetailer = true): ProcessStatus
    {
        $id       = $this->offerId;
        $content  = json_encode([ 'amount' => $amount, 'managedByRetailer' => $managedByRetailer ]);

        try {
            $response = Client::request('PUT', "offers/${id}/stock", ['body' => $content]);

            return new ProcessStatus(json_decode((string) $response->getBody(), true));
        } catch (ClientException $e) {
            static::handleException($e);
        }
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

            return new ProcessStatus(json_decode((string) $response->getBody(), true));
        } catch (ClientException $e) {
            static::handleException($e);
        }
    }

    private static function handleException(ClientException $e)
    {
        $response = $e->getResponse();

        if ($response && $response->getStatusCode() === 404) {
            throw new OfferNotFoundException(
                json_decode((string) $response->getBody(), true),
                404,
                $e
            );
        } elseif ($response) {
            throw new HttpException(
                json_decode((string) $response->getBody(), true),
                $response->getStatusCode(),
                $e
            );
        }

        throw $e;
    }
}
