<?php

namespace Picqer\BolRetailerV10\Tests;

use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Picqer\BolRetailerV10\BaseClient;
use Picqer\BolRetailerV10\Exception\Exception;
use Picqer\BolRetailerV10\Exception\RateLimitException;
use Picqer\BolRetailerV10\Exception\ResponseException;
use Picqer\BolRetailerV10\Exception\ServerException;
use Picqer\BolRetailerV10\Exception\UnauthorizedException;
use Picqer\BolRetailerV10\AuthToken;
use Picqer\BolRetailerV10\Model\AbstractModel;
use Psr\Http\Message\ResponseInterface;

class BaseClientTest extends TestCase
{

    /** @var BaseClient */
    private $client;

    /** @var HttpClient */
    private $httpClientMock;

    /** @var string */
    private $modelClass;

    private $validAccessToken;
    private $expiredAccessToken;
    private $validRefreshToken;
    private $expiredRefreshToken;

    public function setup(): void
    {
        $this->httpClientMock = $this->createMock(HttpClient::class);
        $this->client = new BaseClient();
        $this->client->setHttp($this->httpClientMock);

        $stub = new class () extends AbstractModel {
            public $foo;

            public function getModelDefinition(): array
            {
                return [
                    'foo' => [ 'model' => null, 'array' => false ]
                ];
            }
        };
        $this->modelClass = get_class($stub);

        $this->validAccessToken = new AuthToken('xxx', time() + 10);
        $this->expiredAccessToken = new AuthToken('xxx', time() - 10);
        $this->validRefreshToken = new AuthToken('yyy', time() + 10);
        $this->expiredRefreshToken = new AuthToken('yyy', time() - 10);
    }

    public function testClientIsInitiallyNotAuthenticated()
    {
        $this->assertFalse($this->client->isAuthenticated());
    }

    public function testClientIsAuthenticatedByStoredAccessToken()
    {
        $this->client->setAccessToken($this->validAccessToken);

        $this->assertTrue($this->client->isAuthenticated());
    }

    public function testClientIsNotAuthenticatedByExpiredAccessToken()
    {
        $this->client->setAccessToken($this->expiredAccessToken);

        $this->assertFalse($this->client->isAuthenticated());
    }

    public function testAccessTokenExpiredCallbackIsCalledOnExpiredAccessToken()
    {
        $this->client->setAccessToken($this->expiredAccessToken);

        $callbackCalled = false;
        $this->client->setAccessTokenExpiredCallback(function () use (&$callbackCalled) {
            $callbackCalled = true;
        });

        $caughtException = null;
        try {
            $this->client->request('GET', 'dummy', [], []);
        } catch (\Exception $caughtException) {
            // request should be aborted, as the callback does not set a valid token
        }

        $this->assertInstanceOf(UnauthorizedException::class, $caughtException);
        $this->assertTrue($callbackCalled);
    }

    public function testAccessTokenExpiredCallbackIsCalledOnNoAccessToken()
    {
        $callbackCalled = false;
        $this->client->setAccessTokenExpiredCallback(function () use (&$callbackCalled) {
            $callbackCalled = true;
        });

        $caughtException = null;
        try {
            $this->client->request('GET', 'dummy', [], []);
        } catch (\Exception $caughtException) {
            // request should be aborted, as the callback does not set a valid token
        }

        $this->assertInstanceOf(UnauthorizedException::class, $caughtException);
        $this->assertTrue($callbackCalled);
    }

    public function testRequestContinuesAfterSettingValidAccessTokenAfter()
    {
        $this->client->setAccessToken($this->expiredAccessToken);

        $this->client->setAccessTokenExpiredCallback(function (BaseClient $client) use (&$callbackCalled) {
            $client->setAccessToken($this->validAccessToken);
        });

        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-foo'));
        $this->httpClientMock->method('request')
            ->willReturn($response);

        $response = $this->client->request('GET', 'foobar', [], [
            '200' => $this->modelClass
        ]);

        $this->assertInstanceOf($this->modelClass, $response);
        $this->assertEquals('bar', $response->foo);
    }

    public function testAccessTokenExpiredCallbackIsCalledOnUnauthorizedExpiredTokenResponse()
    {
        $this->client->setAccessToken($this->validAccessToken);

        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/401-jwt-expired'));
        $clientException = new GuzzleClientException(
            'BaseClient error',
            new Request('POST', 'dummy'),
            $response
        );
        $this->httpClientMock
            ->expects($this->once()) // once, as the access token will not be refreshed by the callback
            ->method('request')->willThrowException($clientException);

        $callbackCalled = false;
        $this->client->setAccessTokenExpiredCallback(function () use (&$callbackCalled) {
            $callbackCalled = true;
        });

        $caughtException = null;
        try {
            $this->client->request('GET', 'dummy', [], []);
        } catch (\Exception $caughtException) {
            // request should throw the UnauthorizedException, as a new token was not set
        }

        $this->assertInstanceOf(UnauthorizedException::class, $caughtException);
        $this->assertTrue($callbackCalled);
    }

    public function testRequestIsRetriedAfterSettingNewAccessToken()
    {
        $this->client->setAccessToken($this->validAccessToken);

        $clientException = new GuzzleClientException(
            'BaseClient error',
            new Request('POST', 'dummy'),
            Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/401-jwt-expired'))
        );

        $this->httpClientMock->method('request')->will(
            $this->onConsecutiveCalls(
                $this->throwException($clientException),
                Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-foo'))
            )
        );

        $this->client->setAccessTokenExpiredCallback(function (BaseClient $client) use (&$callbackCalled) {
            $client->setAccessToken(new AuthToken('zzz', time() + 10));
        });

        $response = $this->client->request('GET', 'foobar', [], [
            '200' => $this->modelClass
        ]);

        $this->assertInstanceOf($this->modelClass, $response);
        $this->assertEquals('bar', $response->foo);
    }

    public function testInvalidAccessTokenInExpiredCallBackDoesNotRetryRequest()
    {
        $this->client->setAccessToken($this->validAccessToken);

        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/401-jwt-expired'));
        $clientException = new GuzzleClientException(
            'BaseClient error',
            new Request('POST', 'dummy'),
            $response
        );
        $this->httpClientMock
            ->expects($this->once()) // once, as the access token will be unset
            ->method('request')->willThrowException($clientException);

        $this->client->setAccessTokenExpiredCallback(function (BaseClient $client) use (&$callbackCalled) {
            $client->setAccessToken(null);
        });

        $caughtException = null;
        try {
            $this->client->request('GET', 'dummy', [], []);
        } catch (\Exception $caughtException) {
            // request should throw the UnauthorizedException, as a new token was not set
        }

        $this->assertInstanceOf(UnauthorizedException::class, $caughtException);
    }


    protected function authenticateByClientCredentials(?ResponseInterface $response = null)
    {
        $response = $response ?? Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-token'));
        $httpClientMock = $this->createMock(HttpClient::class);

        $credentials = base64_encode('secret_id' . ':' . 'somesupersecretvaluethatshouldnotbeshared');
        $httpClientMock->method('request')->with('POST', 'https://login.bol.com/token', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . $credentials
            ],
            'query' => [
                'grant_type' => 'client_credentials'
            ]
        ])->willReturn($response);

        // use the HttpClient mock created in this method for authentication, put the original one back afterwards
        $prevHttpClient = $this->client->getHttp();
        $this->client->setHttp($httpClientMock);

        $this->client->authenticateByClientCredentials('secret_id', 'somesupersecretvaluethatshouldnotbeshared');

        $this->client->setHttp($prevHttpClient);
    }

    public function testClientIsAuthenticatedAfterSuccessfulAuthentication()
    {
        $this->authenticateByClientCredentials();

        $this->assertTrue($this->client->isAuthenticated());
        $this->assertEquals('sometoken', $this->client->getAccessToken()->getToken());
    }

    public function testClientAcceptsLowercaseScopeInAccessToken()
    {
        $this->authenticateByClientCredentials(Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-token-lowercase-scope')));

        $this->assertTrue($this->client->isAuthenticated());
    }

    public function testClientAcceptsLowercaseTokenTypeInAccessToken()
    {
        $this->authenticateByClientCredentials(Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-token-lowercase-type')));

        $this->assertTrue($this->client->isAuthenticated());
    }

    public function testAuthenticateByClientCredentialsThrowsUnauthorizedExceptionWhenAuthenticatingWithBadCredentials()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/401-unauthorized'));
        $clientException = new GuzzleClientException(
            'BaseClient error',
            new Request('POST', 'dummy'),
            $response
        );

        $this->httpClientMock->method('request')->willThrowException($clientException);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionCode(401);
        $this->expectExceptionMessage("Bad client credentials");
        $this->client->authenticateByClientCredentials('secret_id', 'somesupersecretvaluethatshouldnotbeshared');
    }

    public function testAuthenticateByClientCredentialsThrowsRateLimitExceptionWhenTooManyRequests()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/429-too-many-requests'));
        $clientException = new GuzzleClientException(
            'BaseClient error',
            new Request('POST', 'dummy'),
            $response
        );

        $this->httpClientMock->method('request')->willThrowException($clientException);

        $actualException = null;
        try {
            $this->client->authenticateByClientCredentials('secret_id', 'somesupersecretvaluethatshouldnotbeshared');
        } catch (RateLimitException $actualException) {
        }

        $this->assertInstanceOf(RateLimitException::class, $actualException);
        $this->assertEquals(429, $actualException->getCode());
        $this->assertEquals(4, $actualException->getRetryAfter());
    }

    public function testRateLimitWithoutRetryAfter()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/429-too-many-requests-without-retry-after'));
        $clientException = new GuzzleClientException(
            'BaseClient error',
            new Request('POST', 'dummy'),
            $response
        );

        $this->httpClientMock->method('request')->willThrowException($clientException);

        $actualException = null;
        try {
            $this->client->authenticateByClientCredentials('secret_id', 'somesupersecretvaluethatshouldnotbeshared');
        } catch (RateLimitException $actualException) {
        }

        $this->assertInstanceOf(RateLimitException::class, $actualException);
        $this->assertEquals(429, $actualException->getCode());
        $this->assertNull($actualException->getRetryAfter());
    }

    public function testAuthenticateByClientCredentialsThrowsResponseExceptionAtForbidden()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/403-forbidden-account_is_not_active'));
        $clientException = new GuzzleClientException(
            'BaseClient error',
            new Request('POST', 'dummy'),
            $response
        );

        $this->httpClientMock->method('request')->willThrowException($clientException);

        $this->expectException(ResponseException::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage("Account is not active, access denied. Please contact partnerservice if this is unexpected.");
        $this->client->authenticateByClientCredentials('secret_id', 'somesupersecretvaluethatshouldnotbeshared');
    }

    public function testAuthenticateByClientCredentialsThrowsServerExceptionAtInternalServerError()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/500-internal-server-error'));
        $clientException = new GuzzleClientException(
            'BaseClient error',
            new Request('POST', 'dummy'),
            $response
        );

        $this->httpClientMock->method('request')->willThrowException($clientException);

        $this->expectException(ServerException::class);
        $this->expectExceptionCode(500);
        $this->client->authenticateByClientCredentials('secret_id', 'somesupersecretvaluethatshouldnotbeshared');
    }

    protected function authenticateByAuthorizationCode(?ResponseInterface $response = null): AuthToken
    {
        $response = $response ?? Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-authorization-code-token'));
        $httpClientMock = $this->createMock(HttpClient::class);

        $credentials = base64_encode('secret_id' . ':' . 'somesupersecretvaluethatshouldnotbeshared');
        $httpClientMock->method('request')->with('POST', 'https://login.bol.com/token', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . $credentials
            ],
            'query' => [
                'grant_type' => 'authorization_code',
                'code' => '123456',
                'redirect_uri' => 'http://someserver.xxx/redirect',
            ]
        ])->willReturn($response);

        // use the HttpClient mock created in this method for authentication, put the original one back afterwards
        $prevHttpClient = $this->client->getHttp();
        $this->client->setHttp($httpClientMock);

        $refreshToken = $this->client->authenticateByAuthorizationCode('secret_id', 'somesupersecretvaluethatshouldnotbeshared', '123456', 'http://someserver.xxx/redirect');

        $this->client->setHttp($prevHttpClient);

        return $refreshToken;
    }

    public function testClientIsAuthenticatedAfterSuccessfulAuthenticationByAuthorizationCode()
    {
        $refreshToken = $this->authenticateByAuthorizationCode();

        $this->assertTrue($this->client->isAuthenticated());
        $this->assertEquals('eyJhbGciOiJub25lIn0.eyJleHAiOjE1NTM5MzY4MTQsImp0aSI6IjZhYmQ1NWNiLWFhOWQtNGM1Zi04OTczLWU5OTYwYjc4MmMyYiJ9.', $refreshToken->getToken());
    }

    public function testAuthenticateByAuthorizationCodeThrowsUnauthorizedExceptionWhenAuthenticatingWithBadCredentials()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/401-unauthorized'));
        $clientException = new GuzzleClientException(
            'BaseClient error',
            new Request('POST', 'dummy'),
            $response
        );

        $this->httpClientMock->method('request')->willThrowException($clientException);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionCode(401);
        $this->expectExceptionMessage("Bad client credentials");
        $this->client->authenticateByAuthorizationCode('secret_id', 'somesupersecretvaluethatshouldnotbeshared', '123456', 'http://someserver.xxx/redirect');
    }

    protected function authenticateByRefreshToken(?ResponseInterface $response = null): AuthToken
    {
        $response = $response ?? Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-authorization-code-token'));
        $httpClientMock = $this->createMock(HttpClient::class);

        $credentials = base64_encode('secret_id' . ':' . 'somesupersecretvaluethatshouldnotbeshared');

        $httpClientMock->method('request')->with('POST', 'https://login.bol.com/token', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . $credentials
            ],
            'query' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->validRefreshToken->getToken()
            ]
        ])->willReturn($response);

        // use the HttpClient mock created in this method for authentication, put the original one back afterwards
        $prevHttpClient = $this->client->getHttp();
        $this->client->setHttp($httpClientMock);


        $refreshToken = $this->client->authenticateByRefreshToken('secret_id', 'somesupersecretvaluethatshouldnotbeshared', $this->validRefreshToken);

        $this->client->setHttp($prevHttpClient);

        return $refreshToken;
    }

    public function testAccessTokenIsRefreshed()
    {
        $this->client->setAccessToken($this->validAccessToken);

        $refreshToken = $this->authenticateByRefreshToken();

        $this->assertTrue($this->client->isAuthenticated());
        $this->assertEquals('eyJhbGciOiJub25lIn0.eyJleHAiOjE1NTM5MzY4MTQsImp0aSI6IjZhYmQ1NWNiLWFhOWQtNGM1Zi04OTczLWU5OTYwYjc4MmMyYiJ9.', $refreshToken->getToken());
    }

    public function testAccessTokenWithExpiredRefreshTokenCannotBeRefreshed()
    {
        $this->client->setAccessToken($this->validAccessToken);

        $this->expectException(Exception::class);

        $this->client->authenticateByRefreshToken('secret_id', 'somesupersecretvaluethatshouldnotbeshared', $this->expiredRefreshToken);
    }

    public function testRefreshTokenThrowsUnauthorizedExceptionWhenUsingWithBadCredentials()
    {
        $this->client->setAccessToken($this->validAccessToken);

        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/401-unauthorized'));
        $clientException = new GuzzleClientException(
            'BaseClient error',
            new Request('POST', 'dummy'),
            $response
        );

        $this->httpClientMock->method('request')->willThrowException($clientException);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionCode(401);
        $this->expectExceptionMessage("Bad client credentials");
        $this->client->authenticateByRefreshToken('secret_id', 'somesupersecretvaluethatshouldnotbeshared', $this->validRefreshToken);
    }

    public function providerMalformedTokenResponses()
    {
        $files = [
            '200-token-missing-access_token',
            '200-token-empty-access_token',
            '200-token-missing-expires_in',
            '200-token-empty-expires_in',
            '200-token-unexpected-bearer',
            '200-token-unexpected-scope',
            '200-token-empty-body'
        ];

        return array_map(function ($file) {
            return [
                Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/' . $file))
            ];
        }, $files);
    }

    /**
     * @dataProvider providerMalformedTokenResponses
     */
    public function testAuthenticateByClientCredentialsThrowsResponseExceptionWhenTokenIsMalformed($response)
    {
        $credentials = base64_encode('secret_id' . ':' . 'somesupersecretvaluethatshouldnotbeshared');
        $this->httpClientMock->method('request')->willReturn($response);

        $this->expectException(ResponseException::class);
        $this->client->authenticateByClientCredentials('secret_id', 'somesupersecretvaluethatshouldnotbeshared');
    }

    public function testRequestReturnsModel()
    {
        $this->authenticateByClientCredentials();

        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-foo'));
        $this->httpClientMock->method('request')
            ->with($this->anything(), $this->anything(), $this->callback(function ($options) {
                return isset($options['headers']['Authorization']) && $options['headers']['Authorization'] === 'Bearer ' . $this->client->getAccessToken()->getToken();
            }))
            ->willReturn($response);

        $response = $this->client->request('GET', 'foobar', [], [
            '200' => $this->modelClass
        ]);

        $this->assertInstanceOf($this->modelClass, $response);
        $this->assertEquals('bar', $response->foo);
    }

    public function testRequestReturnsString()
    {
        $this->authenticateByClientCredentials();

        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-string'));
        $this->httpClientMock->method('request')->willReturn($response);

        $response = $this->client->request('GET', 'foobar', [], [
            '200' => 'string'
        ]);

        $this->assertEquals("This is a test string\n", $response);
    }

    public function testRequestReturnsNullAt404()
    {
        $this->authenticateByClientCredentials();

        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/404-not-found'));
        $this->httpClientMock->method('request')->willReturn($response);

        $response = $this->client->request('GET', 'foobar', [], [
            '404' => 'null'
        ]);

        $this->assertNull($response);
    }

    public function testRequestThrowsResponseExceptionAtUnknownResponseType()
    {
        $this->authenticateByClientCredentials();

        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-string'));
        $this->httpClientMock->method('request')->willReturn($response);

        $this->expectException(ResponseException::class);
        $this->client->request('GET', 'foobar', [], []);
    }

    public function testRequestAddsProducesAsAcceptHeader()
    {
        $this->authenticateByClientCredentials();

        $actualOptions = null;
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-string'));
        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function ($method, $uri, $options) use ($response, &$actualOptions) {
                $actualOptions = $options;
                return $response;
            });

        $this->client->request('GET', 'foobar', [
            'produces' => 'application/vnd.retailer.v8+pdf'
        ], [
            '200' => 'string'
        ]);

        $this->assertArrayHasKey('headers', $actualOptions);
        $this->assertArrayHasKey('Accept', $actualOptions['headers']);
        $this->assertEquals('application/vnd.retailer.v8+pdf', $actualOptions['headers']['Accept']);
    }

    public function testRequestJsonEncodesBodyModelIntoBody()
    {
        $this->authenticateByClientCredentials();

        $model = new $this->modelClass();
        $model->foo = 'bar';

        $actualOptions = null;
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-string'));
        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function ($method, $uri, $options) use ($response, &$actualOptions) {
                $actualOptions = $options;
                return $response;
            });

        $this->client->request('GET', 'foobar', [
            'body' => $model,
        ], [
            '200' => 'string'
        ]);

        $this->assertArrayHasKey('body', $actualOptions);
        $this->assertEquals(json_encode(['foo' => 'bar']), $actualOptions['body']);
    }

    public function testRequestJsonEncodesBodyModelWithoutNullValuesIntoBody()
    {
        $stub = new class () extends AbstractModel {
            public $foo;
            public $foo2;

            public function getModelDefinition(): array
            {
                return [
                    'foo' => [ 'model' => null, 'array' => false ],
                    'foo2' => [ 'model' => null, 'array' => false ]
                ];
            }
        };
        $modelClass = get_class($stub);

        $this->authenticateByClientCredentials();

        $model = new $modelClass();
        $model->foo = 'bar';

        $actualOptions = null;
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-string'));
        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function ($method, $uri, $options) use ($response, &$actualOptions) {
                $actualOptions = $options;
                return $response;
            });

        $this->client->request('GET', 'foobar', [
            'body' => $model,
        ], [
            '200' => 'string'
        ]);

        $this->assertArrayHasKey('body', $actualOptions);
        $this->assertEquals(json_encode(['foo' => 'bar']), $actualOptions['body']);
    }

    public function testRequestConstructsEndpoint()
    {
        $this->authenticateByClientCredentials();

        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-string'));
        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.bol.com/retailer/foobar')
            ->willReturn($response);

        $this->client->request('GET', 'retailer/foobar', [], [
            '200' => 'string'
        ]);
    }

    public function testDemoModeConstructsDemoEndpoint()
    {
        $this->client->setDemoMode(true);
        $this->authenticateByClientCredentials();

        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-string'));
        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.bol.com/foobar-demo/some-resource')
            ->willReturn($response);

        $this->client->request('GET', 'foobar/some-resource', [
            'query' => [
                'page' => null,
                'foo' => 'bar',
            ],
        ], [
            '200' => 'string'
        ]);
    }

    public function testRequestThrowsUnauthorizedExceptionWhenNotAuthenticated()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-foo'));
        $this->httpClientMock->method('request')->willReturn($response);

        $this->expectException(UnauthorizedException::class);
        $this->client->request('GET', 'foobar', [], []);
    }

    public function testQueryParameterIsSentInRequest()
    {
        $this->authenticateByClientCredentials();

        $actualOptions = null;
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-string'));
        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function ($method, $uri, $options) use ($response, &$actualOptions) {
                $actualOptions = $options;
                return $response;
            });

        $this->client->request('GET', 'foobar', [
            'query' => [
                'foo' => 'bar'
            ],
        ], [
            '200' => 'string'
        ]);

        $this->assertArrayHasKey('query', $actualOptions);
        $this->assertEquals(['foo' => 'bar'], $actualOptions['query']);
    }

    public function testQueryParameterWithValueNullIsNotSentInRequest()
    {
        $this->authenticateByClientCredentials();

        $actualOptions = null;
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-string'));
        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function ($method, $uri, $options) use ($response, &$actualOptions) {
                $actualOptions = $options;
                return $response;
            });

        $this->client->request('GET', 'foobar', [
            'query' => [
                'page' => null,
                'foo' => 'bar',
            ],
        ], [
            '200' => 'string'
        ]);

        $this->assertArrayHasKey('query', $actualOptions);
        $this->assertEquals(['foo' => 'bar'], $actualOptions['query']);
    }
}
