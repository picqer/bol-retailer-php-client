<?php
namespace Picqer\BolRetailer;

use GuzzleHttp\Client as Http;
use GuzzleHttp\ClientInterface as HttpInterface;
use Psr\Http\Message\ResponseInterface;

class Client
{
    /** @var HttpInterface|null */
    private static $http;

    /** @var array|null */
    private static $token = null;

    /** @var bool */
    private static $isDemoMode = false;

    /**
     * Set the API credentials of the client.
     *
     * @param string $clientId     The client ID to use for authentication.
     * @param string $clientSecret The client secret to use for authentication.
     */
    public static function setCredentials(string $clientId, string $clientSecret): void
    {
        $params  = [ 'client_id' => $clientId, 'client_secret' => $clientSecret, 'grant_type' => 'client_credentials' ];
        $headers = [ 'Accept' => 'application/json' ];

        $response = static::getHttp()->request('POST', 'https://login.bol.com/token', [
            'headers'     => $headers,
            'form_params' => $params
        ]);

        $token               = json_decode((string) $response->getBody(), true);
        $token['expires_at'] = time() + $token['expires_in'] ?? 0;

        static::$token = $token;
    }

    /**
     * Clear the credentials of the client. This will effectively sign out.
     */
    public static function clearCredentials(): void
    {
        static::$token = null;
    }

    /**
     * Check if the client is authenticated.
     *
     * @return bool
     */
    public static function isAuthenticated(): bool
    {
        if (!is_array(static::$token)) {
            return false;
        }

        if (!isset(static::$token['expires_at']) || !isset(static::$token['access_token'])) {
            return false;
        }

        return static::$token['expires_at'] > time();
    }

    /**
     * Configure whether the demo endpoints or real endpoints should be used.
     *
     * @param bool $enabled Set to `true` to enable demo mode, `false` otherwise.
     */
    public static function setDemoMode(bool $enabled): void
    {
        static::$http       = null;
        static::$isDemoMode = $enabled;
    }

    /**
     * Perform an API call.
     *
     * @param string $method  The HTTP method used for the API call.
     * @param string $uri     The URI to call.
     * @param array  $options The request options.
     *
     * @return ResponseInterface
     */
    public static function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        $options = static::addAuthenticationOptions($options);

        return static::getHttp()->request($method, $uri, $options);
    }

    /**
     * Set the HTTP client used for API calls.
     *
     * @param HttpInterface $http
     */
    public static function setHttp(HttpInterface $http): void
    {
        static::$http = $http;
    }

    private static function addAuthenticationOptions(array $options): array
    {
        if (!static::isAuthenticated() || !is_array(static::$token)) {
            return $options;
        }

        $authorization = [
            'Authorization' => sprintf('Bearer %s', static::$token['access_token']),
        ];

        $options['headers'] = array_merge($options['headers'] ?? [], $authorization);

        return $options;
    }

    private static function getHttp(): HttpInterface
    {
        if (!static::$http instanceof HttpInterface) {
            $baseUri = static::$isDemoMode ? 'https://api.bol.com/retailer-demo/' : 'https://api.bol.com/retailer/';

            static::$http = new Http([
                'base_uri' => $baseUri,
                'headers' => [
                    'Accept' => 'application/vnd.retailer.v3+json',
                    'Content-Type' => 'application/vnd.retailer.v3+json',
                ]
            ]);
        }

        return static::$http;
    }
}
