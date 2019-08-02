<?php
namespace Picqer\BolRetailer;

use GuzzleHttp\Exception\ClientException;
use Picqer\BolRetailer\Exception\HttpException;
use Picqer\BolRetailer\Exception\ProcessStillPendingException;
use Picqer\BolRetailer\Exception\ProcessStatusNotFoundException;

class ProcessStatus extends Model\ProcessStatus
{
    /**
     * Get the status of an asynchronous process.
     *
     * @param string $id The identifier of the process to get.
     *
     * @return self|null
     */
    public static function get(string $id): ?ProcessStatus
    {
        try {
            $response = Client::request('GET', "process-status/${id}");

            return new self(json_decode((string) $response->getBody(), true));
        } catch (ClientException $e) {
            static::handleException($e);
        }
    }

    /**
     * Refresh the details of the current process status.
     */
    public function refresh(): void
    {
        $id = $this->id;

        try {
            $response = Client::request('GET', "process-status/${id}");

            $this->merge(json_decode((string) $response->getBody(), true));
        } catch (ClientException $e) {
            static::handleException($e);
        }
    }

    /**
     * Wait until the process is marked as something other than `PENDING`.
     *
     * An exception will be thrown if the process status is not something other than `PENDING` after the given number
     * of `maxRetries`.
     *
     * @param int $maxRetries The maximum number of times the process status should be refreshed.
     * @param int $timeout    The number of seconds to wait between fetching updates from the server.
     *
     * @throws ProcessStillPendingException when the maximum number of retries is reached and the process is still
     *                                      in the `PENDING` status.
     */
    public function waitUntilComplete(int $maxRetries = 20, int $timeout = 3): void
    {
        for ($i = 0; $i < $maxRetries && $this->isPending; $i++) {
            $this->refresh();
            sleep($timeout);
        }

        if ($this->isPending) {
            throw new ProcessStillPendingException($this);
        }
    }

    private static function handleException(ClientException $e)
    {
        $response = $e->getResponse();

        if ($response && $response->getStatusCode() === 404) {
            throw new ProcessStatusNotFoundException(
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
