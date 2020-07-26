<?php

namespace Picqer\BolRetailer;

use GuzzleHttp\Exception\ClientException;
use Picqer\BolRetailer\Exception\HttpException;
use Picqer\BolRetailer\Exception\ReturnNotFoundException;

class ReturnItem extends Model\ReturnItem
{
    /**
     * Get a single return.
     *
     * @param int $id The RMA id that identifies this particular return.
     *
     * @return self|null
     */
    public static function get(int $rmaId): ?ReturnItem
    {
        try {
            $response = Client::request('GET', "returns/${rmaId}");
        } catch (ClientException $e) {
            static::handleException($e);
        }

        return new self(json_decode((string)$response->getBody(), true));
    }

    /**
     * Get all returns.
     *
     * @param int $page The requested page number with a pagesize of 50
     * @param bool $handled The status of the returns you wish to see, shows either handled or unhandled returns.
     * @param string $method The fulfilment method. Fulfilled by the retailer (FBR) or fulfilled by bol.com (FBB).
     *
     * @return ReturnItem[]
     */
    public static function all(int $page = 1, bool $handled = false, string $method = 'FBR'): array
    {
        $query = ['page' => $page, 'handled' => $handled, 'fulfilment-method' => $method];

        try {
            $response = Client::request('GET', 'returns', ['query' => $query]);
            $response = json_decode((string)$response->getBody(), true);
        } catch (ClientException $e) {
            static::handleException($e);
        }

        /** @var array<array-key, mixed> */
        $returns = $response['returns'] ?? [];

        return array_map(function (array $data) {
            return new self($data);
        }, $returns);
    }

    /**
     * Handle the return.
     *
     * @param int $rmaId The RMA id that identifies this particular return.
     * @param string $handlingResult The return item handling type.
     * @param int $quantityReturned The amount of items returned.
     *
     * @return Model\ProcessStatus
     */
    public static function handle(int $rmaId, string $handlingResult, int $quantityReturned): Model\ProcessStatus
    {
        $data = ['handlingResult' => $handlingResult, 'quantityReturned' => $quantityReturned];

        try {
            $response = Client::request('PUT', "returns/${rmaId}", ['body' => json_encode($data)]);
        } catch (ClientException $e) {
            static::handleException($e);
        }

        return new ProcessStatus(json_decode((string)$response->getBody(), true));
    }

    private static function handleException(ClientException $e): void
    {
        $response = $e->getResponse();

        if ($response && $response->getStatusCode() === 404) {
            throw new ReturnNotFoundException(
                json_decode((string)$response->getBody(), true),
                404,
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
