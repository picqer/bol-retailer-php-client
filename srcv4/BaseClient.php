<?php

namespace Picqer\BolRetailerV4;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface as HttpInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Exception\ConnectException as GuzzleConnectException;
use Picqer\BolRetailerV4\Model\AbstractModel;
use Picqer\BolRetailerV4\Exception\AuthenticationException;
use Picqer\BolRetailerV4\Exception\ConnectException;
use Picqer\BolRetailerV4\Exception\Exception;
use Picqer\BolRetailerV4\Exception\ResponseException;
use Picqer\BolRetailerV4\Exception\UnauthorizedException;
use Picqer\BolRetailerV4\OpenApi\ModelCreator;
use Psr\Http\Message\ResponseInterface;

class BaseClient
{
    protected const API_TOKEN_URI = 'https://login.bol.com/token';
    protected const API_ENDPOINT = 'https://api.bol.com/retailer/';
    protected const API_DEMO_ENDPOINT = 'https://api.bol.com/retailer-demo/';
    protected const API_CONTENT_TYPE_JSON = 'application/vnd.retailer.v4+json';

    /**
     * @var bool Whether request will be sent to the demo endpoint.
     */
    protected $isDemoMode = false;

    /** @var HttpInterface|null */
    protected $http = null;

    /** @var array|null */
    protected $token = null;

    /**
     * BaseClient constructor.
     */
    public function __construct()
    {
        $this->setHttp(new HttpClient());
    }

    /**
     * Set the HTTP client used for API calls.
     *
     * @param HttpInterface $http
     */
    public function setHttp(HttpInterface $http): void
    {
        $this->http = $http;
    }

    /**
     * Configure whether the demo endpoints or real endpoints should be used.
     *
     * @param bool $enabled Set to `true` to enable demo mode, `false` otherwise.
     */
    public function setDemoMode(bool $enabled): void
    {
        $this->isDemoMode = $enabled;
    }

    /**
     * Check if the client is authenticated.
     *
     * @return bool Whether the client is authenticated.
     */
    public function isAuthenticated(): bool
    {
        if (! is_array($this->token)) {
            return false;
        }

        return $this->token['expires_at'] > time();
    }

    /**
     * Authenticates with Bol.com Retailer API Server.
     *
     * @param string $clientId The client ID to use for authentication.
     * @param string $clientSecret The client secret to use for authentication.
     *
     * @throws ConnectException when an error occurred in the HTTP connection.
     * @throws ResponseException when response could not be decoded.
     * @throws UnauthorizedException when authentication failed.
     * @throws Exception when something unexpected went wrong.
     */
    public function authenticate(string $clientId, string $clientSecret): void
    {
        $credentials = base64_encode(sprintf('%s:%s', $clientId, $clientSecret));
        $response = $this->rawRequest('POST', static::API_TOKEN_URI, [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => sprintf('Basic %s', $credentials)
            ],
            'query' => [
                'grant_type' => 'client_credentials'
            ]
        ]);

        $token = $this->jsonDecodeBody($response);
        $this->validateToken($token);

        $token['expires_at'] = time() + $token['expires_in'] ?? 0;
        $this->token = $token;
    }

    /**
     * @param $token Token data from HTTP response.
     *
     * @throws ResponseException when the token data is invalid.
     */
    protected function validateToken($token): void
    {
        if (! is_array($token)) {
            throw new ResponseException('Token is not an array');
        }

        if (empty($token['access_token'])) {
            throw new ResponseException('Missing access_token');
        }

        if (empty($token['expires_in'])) {
            throw new ResponseException('Missing expires_in');
        }

        if ($token['token_type'] != 'Bearer') {
            throw new ResponseException(
                sprintf('Unexpected token_type \'%s\', expected \'Bearer\'', $token['token_type'])
            );
        }

        if ($token['scope'] != 'RETAILER') {
            throw new ResponseException(
                sprintf('Unexpected token_type \'%s\', expected \'RETAILER\'', $token['scope'])
            );
        }
    }

    /**
     * @param string $method HTTP Method
     * @param string $url Url
     * @param array $options Request options to apply
     * @param array $responses Name of the response model per http status
     * @return AbstractModel|string|null Model or array representing response
     * @throws ConnectException when an error occurred in the HTTP connection.
     * @throws UnauthorizedException when request was unauthorized.
     * @throws Exception when something unexpected went wrong.
     */
    protected function request(string $method, string $url, array $options, array $responses)
    {
        // TODO check if authenticated

        $url = $this->getEndpoint() . $url;

        $options['headers'] = [
            'Accept' => $options['produces'],
            'Authorization' => sprintf('Bearer %s', $this->token['access_token']),
        ];

        // encode the body if a model is supplied for it
        if (isset($options['body'])) {
            $options['headers']['Content-Type'] = static::API_CONTENT_TYPE_JSON;
            if ($options['body'] instanceof AbstractModel) {
                $options['body'] = json_encode($options['body']);
            }
        }

        $response = $this->rawRequest($method, $url, $options);
        $statusCode = $response->getStatusCode();

        if (!array_key_exists($statusCode, $responses)) {
            throw new ResponseException("No model specified for '{$url}' with status '{$statusCode}'");
        }

        $responseType = $responses[$statusCode];

        // return null if responseType is equal to null (e.g. 404)
        if ($responseType === null) {
            return null;
        }

        // return raw body if response type is string
        if ($responseType == 'string') {
            return (string)$response->getBody();
        }

        // create new instance of model and fill it with the response data
        $modelFQN = __NAMESPACE__ . '\Model\\' . $responseType;
        $data = $this->jsonDecodeBody($response);
        return $modelFQN::fromData($data);
    }

    /**
     * Returns the url of the endpoint, taking demo mode into account.
     *
     * @return string The url of the endpoint.
     */
    protected function getEndpoint(): string
    {
        if ($this->isDemoMode) {
            return static::API_DEMO_ENDPOINT;
        }
        return static::API_ENDPOINT;
    }

    /**
     * Executes a specified HTTP request and returns the HTTP response. Error situations are converted to Exceptions.
     *
     * @param string $method HTTP Method
     * @param string $url Url
     * @param array $options Request options to apply
     * @return ResponseInterface HTTP response
     *
     * @throws ConnectException when an error occurred in the HTTP connection.
     * @throws UnauthorizedException when request was unauthorized.
     * @throws Exception when something unexpected went wrong.
     */
    protected function rawRequest(string $method, string $url, array $options = []): ResponseInterface
    {
        try {
            $response = $this->http->request($method, $url, $options);
        } catch (GuzzleConnectException $connectException) {
            throw new ConnectException(
                $connectException->getMessage(),
                $connectException->getCode(),
                $connectException
            );
        } catch (GuzzleClientException $clientException) {
            $response = $clientException->getResponse();

            $data = [];
            try {
                $data = $this->jsonDecodeBody($response);
            } catch (ResponseException $responseException) {
            }

            //TODO possible add more situations
            //400 will return data of type 'Problem'

            switch ($response->getStatusCode()) {
                case 401:
                    throw new UnauthorizedException($data['error_description'] ?? 'No description provided');
            }
        } catch (GuzzleException $guzzleException) {
            // TODO Uncaught GuzzleHttp\Exception\ServerException: Server error:
            // `GET https://api.bol.com/retailer-demo//orders/B3K8290LP0x` resulted in a `500 Internal Server Error`
            throw new Exception(
                "Unexpected Guzzle exception: " . $guzzleException->getMessage(),
                0,
                $guzzleException
            );
        }

        return $response;
    }

    /**
     * Validates the response and returns the JSON decoded body of an HTTP response.
     *
     * @param ResponseInterface $response Response to decode.
     * @return mixed Json decoded response.
     * @throws ResponseException when the response could not be decoded.
     */
    protected function jsonDecodeBody(?ResponseInterface $response)
    {
        if ($response === null) {
            throw new ResponseException('No body received');
        }

        $data = json_decode((string)$response->getBody(), true);

        if (json_last_error() != JSON_ERROR_NONE) {
            throw new ResponseException('Body contains invalid JSON');
        }

        return $data;
    }

    /**
     * Prints the raw response. Useful to see what is received in edge cases.
     *
     * @param ResponseInterface $response Response to print.
     */
    protected function printResponse(ResponseInterface $response)
    {
        echo $response->getStatusCode() . ' ' . $response->getReasonPhrase() . "\n";
        foreach ($response->getHeaders() as $name => $values) {
            echo "{$name}: {$values[0]}\n";
        }
        echo "\n";
        echo $response->getBody() . "\n";
    }
}
