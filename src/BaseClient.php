<?php

namespace Picqer\BolRetailerV8;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ConnectException as GuzzleConnectException;
use Picqer\BolRetailerV8\Exception\RateLimitException;
use Picqer\BolRetailerV8\Exception\ServerException;
use Picqer\BolRetailerV8\Model\AbstractModel;
use Picqer\BolRetailerV8\Exception\ConnectException;
use Picqer\BolRetailerV8\Exception\Exception;
use Picqer\BolRetailerV8\Exception\ResponseException;
use Picqer\BolRetailerV8\Exception\UnauthorizedException;
use Picqer\BolRetailerV8\Model\Authentication\TokenResponse;
use Picqer\BolRetailerV8\Model\Authentication\TokenRequest;
use Psr\Http\Message\ResponseInterface;

class BaseClient
{
    protected const API_TOKEN_URI = 'https://login.bol.com/token';
    protected const API_ENDPOINT = 'https://api.bol.com/';
    protected const API_CONTENT_TYPE_JSON = 'application/vnd.retailer.v8+json';

    /**
     * @var bool Whether request will be sent to the demo endpoint.
     */
    protected $isDemoMode = false;

    /** @var HttpClient|null */
    protected $http = null;

    /** @var ?JWTToken */
    protected $accessToken = null;

    /** @var ?callable */
    private $accessTokenExpiredCallback = null;

    /**
     * BaseClient constructor.
     */
    public function __construct()
    {
        $this->setHttp(new HttpClient());
    }

    /**
     * Set the Guzzle HTTP client used for API calls.
     *
     * @param HttpClient $http The Guzzle HTTP client used for API calls.
     */
    public function setHttp(HttpClient $http): void
    {
        $this->http = $http;
    }

    /**
     * Returns the Guzzle HTTP client used for API calls.
     *
     * @return HttpClient The Guzzle HTTP client used for API calls.
     */
    public function getHttp(): HttpClient
    {
        return $this->http;
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
     * Sets a callback which is called at the start of a request when an access token is set, but expired. This callback
     * may attempt to refresh the access token. If the token is valid after the callback, the request will continue,
     * otherwise an UnauthorizedException will be thrown.
     * WARNING: when using the 'Code flow' for authentication where every refresh of an access token results in a new
     * refresh token (named 'method 2' by Bol.com), only the last refresh token is valid. So if multiple processes may
     * refresh the tokens at the same time, you need a locking mechanism such as a mutex to make sure you only store the
     * last retrieved refresh token.
     *
     * @param callable $callback The callback to be called when the access token is expired.
     */
    public function setAccessTokenExpiredCallback(callable $callback): void
    {
        $this->accessTokenExpiredCallback = $callback;
    }

    /**
     * Check if the client is authenticated.
     *
     * @return bool Whether the client is authenticated.
     */
    public function isAuthenticated(): bool
    {
        if ($this->accessToken === null) {
            return false;
        }

        return ! $this->accessToken->isExpired();
    }

    /**
     * Returns the authentication token.
     * @return ?JWTToken Authentication token.
     */
    public function getAccessToken(): ?JWTToken
    {
        return $this->accessToken;
    }

    /**
     * Sets the authentication token.
     * @param ?JWTToken $accessToken Authentication token.
     */
    public function setAccessToken(?JWTToken $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    /**
     * Authenticates with Bol.com Retailer API Server.
     *
     * @param string $clientId The client ID to use for authentication.
     * @param string $clientSecret The client secret to use for authentication.
     *
     * @throws ConnectException when an error occurred in the HTTP connection.
     * @throws ResponseException when an unexpected response was received.
     * @throws UnauthorizedException when authentication failed.
     * @throws RateLimitException when the throttling limit has been reached for the API user.
     * @throws Exception when something unexpected went wrong.
     */
    public function authenticate(string $clientId, string $clientSecret): void
    {
        $tokenRequest = TokenRequest::constructFromArray([
            'grant_type' => 'client_credentials',
        ]);

        $this->requestToken($clientId, $clientSecret, $tokenRequest, 'RETAILER');
    }

    /**
     * Authenticates with Bol.com Retailer API Server using an authorization code.
     *
     * @param string $clientId The client ID to use for authentication.
     * @param string $clientSecret The client secret to use for authentication.
     * @param string $code Authorization code received from Bol.com.
     * @param string $redirectUri The redirect URI used for the authorization code request.
     * @return JWTToken The refresh token.
     *
     * @throws ConnectException
     * @throws Exception
     * @throws RateLimitException
     * @throws ResponseException
     * @throws UnauthorizedException
     */
    public function authenticateByAuthorizationCode(string $clientId, string $clientSecret, string $code, string $redirectUri): JWTToken
    {
        $tokenRequest = TokenRequest::constructFromArray([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ]);

        $tokenResponse = $this->requestToken($clientId, $clientSecret, $tokenRequest);

        return new JWTToken($tokenResponse->refresh_token);
    }

    /**
     * Refreshes the authentication token. This requires an existing token with a refresh token.
     *
     * @param string $clientId The client ID to use for authentication.
     * @param string $clientSecret The client secret to use for authentication.
     * @param JWTToken $refreshToken The refresh token.
     * @return JWTToken The (current or new) refresh token, depending on the authentication settings managed by Bol.com
     * for your account.
     *
     * @throws ConnectException
     * @throws Exception
     * @throws RateLimitException
     * @throws ResponseException
     * @throws UnauthorizedException
     */
    public function refreshToken(string $clientId, string $clientSecret, JWTToken $refreshToken): JWTToken
    {
        if ($refreshToken->isExpired()) {
            throw new Exception('The refresh token is expired.');
        }

        $tokenRequest = TokenRequest::constructFromArray([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken->getEncoded(),
        ]);

        $tokenResponse = $this->requestToken($clientId, $clientSecret, $tokenRequest);

        return new JWTToken($tokenResponse->refresh_token);
    }

    /**
     * Executes the request to the token endpoint.
     *
     * @param string $clientId The client ID to use for authentication.
     * @param string $clientSecret The client secret to use for authentication.
     * @param TokenRequest $token The token request.
     * @param $expectedScope ?string The expected scope of the token.
     * @return TokenResponse The token response.
     *
     * @throws ConnectException when an error occurred in the HTTP connection.
     * @throws ResponseException when an unexpected response was received.
     * @throws UnauthorizedException when authentication failed.
     * @throws RateLimitException when the throttling limit has been reached for the API user.
     * @throws Exception when something unexpected went wrong.
     */
    protected function requestToken(string $clientId, string $clientSecret, TokenRequest $token, ?string $expectedScope = null): TokenResponse
    {
        $credentials = base64_encode(sprintf('%s:%s', $clientId, $clientSecret));
        $response = $this->rawRequest('POST', static::API_TOKEN_URI, [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => sprintf('Basic %s', $credentials)
            ],
            'query' => $token->toArray()
        ]);

        $responseTypes = [
            '200' => TokenResponse::class
        ];

        $tokenResponse = $this->decodeResponse($response, $responseTypes, static::API_TOKEN_URI);
        $this->validateTokenResponse($tokenResponse, $expectedScope);
        $this->accessToken = new JWTToken($tokenResponse->access_token);

        return $tokenResponse;
    }

    /**
     * @param $tokenResponse TokenResponse data from HTTP response.
     * @param $expectedScope ?string The expected scope of the token.
     *
     * @throws ResponseException when the token data is invalid.
     */
    protected function validateTokenResponse(TokenResponse $tokenResponse, ?string $expectedScope = null): void
    {

        if ($tokenResponse->access_token === null || $tokenResponse->access_token === '') {
            throw new ResponseException('Missing access_token');
        }

        if ($tokenResponse->expires_in === null || $tokenResponse->expires_in === '') {
            throw new ResponseException('Missing expires_in');
        }

        if (strtolower($tokenResponse->token_type) !== 'bearer') {
            throw new ResponseException(
                sprintf('Unexpected token_type \'%s\', expected \'Bearer\'', $tokenResponse->token_type)
            );
        }

        if ($expectedScope !== null && strtolower($tokenResponse->scope) !== strtolower($expectedScope)) {
            throw new ResponseException(
                sprintf('Unexpected token_type \'%s\', expected \'%s\'', $tokenResponse->scope, $expectedScope)
            );
        }
    }

    /**
     * @param string $method HTTP Method
     * @param string $url Url
     * @param array $options Request options to apply
     * @param array $responseTypes Expected response type per HTTP status code
     * @return AbstractModel|string|null Model or array representing response
     * @throws ConnectException when an error occurred in the HTTP connection.
     * @throws UnauthorizedException when the request was unauthorized.
     * @throws ResponseException when no suitable responseType could be applied.
     * @throws RateLimitException when the throttling limit has been reached for the API user.
     * @throws Exception when something unexpected went wrong.
     */
    public function request(string $method, string $url, array $options, array $responseTypes)
    {
        $this->checkToken();

        $url = $this->getEndpoint($url);

        $httpOptions = [];
        $httpOptions['headers'] = [
            'Accept' => $options['produces'] ?? static::API_CONTENT_TYPE_JSON,
            'Authorization' => sprintf('Bearer %s', $this->accessToken->getEncoded()),
        ];

        // encode the body if a model is supplied for it
        if (isset($options['body']) && $options['body'] instanceof AbstractModel) {
            $httpOptions['headers']['Content-Type'] = static::API_CONTENT_TYPE_JSON;
            $httpOptions['body'] = json_encode($options['body']->toArray(true));
        }

        // pass through query parameters without null values
        if (isset($options['query'])) {
            $httpOptions['query'] = array_filter($options['query'], function ($value) {
                return $value !== null;
            });
        }

        $response = $this->rawRequest($method, $url, $httpOptions);
        return $this->decodeResponse($response, $responseTypes, $url);
    }

    /**
     * Checks the existence of the token and its expiration. If the token is expired, the tokenExpiredCallback is
     * called.
     *
     * @throws UnauthorizedException
     */
    private function checkToken()
    {
        if ($this->isAuthenticated()) {
            return;
        }

        if ($this->accessToken !== null && $this->accessTokenExpiredCallback !== null) {
            ($this->accessTokenExpiredCallback)($this);

            if ($this->isAuthenticated()) {
                return;
            }
        }

        throw new UnauthorizedException('No or expired token, please authenticate first');
    }

    /**
     * Decodes an HTTP response into a value: null, string or model.
     * @param ResponseInterface $response HTTP Response
     * @param array $responseTypes Expected response type per HTTP status code
     * @param string $url Url
     * @return string|null
     * @throws ResponseException
     */
    private function decodeResponse(ResponseInterface $response, array $responseTypes, string $url)
    {
        $statusCode = $response->getStatusCode();

        if (!array_key_exists($statusCode, $responseTypes)) {
            throw new ResponseException("No model specified for '{$url}' with status '{$statusCode}'");
        }

        $responseType = $responseTypes[$statusCode];

        // return null if responseType is null (e.g. 404)
        if ($responseType === 'null') {
            return null;
        }

        // return raw body if response type is string
        if ($responseType == 'string') {
            return (string)$response->getBody();
        }

        // create new instance of model and fill it with the response data
        $data = $this->jsonDecodeBody($response);
        return $responseType::constructFromArray($data);
    }

    /**
     * Returns the url of the endpoint, taking demo mode into account.
     *
     * @param string $url The relative url of the endpoint.
     * @return string The url of the endpoint.
     */
    protected function getEndpoint(string $url): string
    {
        if ($this->isDemoMode) {
            // add '-demo' to the first path item of the url
            $url = preg_replace('/^([^\/]+)/', '$1-demo', $url);
        }
        return static::API_ENDPOINT . $url;
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
     * @throws RateLimitException when the throttling limit has been reached for the API user.
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
        } catch (BadResponseException $badResponseException) {
            $response = $badResponseException->getResponse();

            $data = [];
            try {
                $data = $this->jsonDecodeBody($response);
            } catch (ResponseException $responseException) {
            }

            $statusCode = $response->getStatusCode();

            $message = $data['detail'] ??
                $data['error_description'] ??
                $statusCode . ' ' . $response->getReasonPhrase();

            if ($statusCode == 401) {
                throw new UnauthorizedException($message, $statusCode);
            } if ($statusCode == 429) {
                $retryAfter = null;
                if ($response->hasHeader('Retry-After')) {
                    $retryAfter = (int) $response->getHeader('Retry-After')[0];
                }

                throw new RateLimitException($message, $statusCode, null, $retryAfter);
            } elseif (in_array($statusCode, [500, 502, 503, 504, 507])) {
                throw new ServerException($message, $statusCode);
            } elseif ($statusCode != 404) {
                throw new ResponseException($message, $statusCode);
            }
        } catch (GuzzleException $guzzleException) {
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
