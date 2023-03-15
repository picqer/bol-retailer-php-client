<?php

namespace Picqer\BolRetailerV8\Tests;

use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Picqer\BolRetailerV8\BaseClient;
use Picqer\BolRetailerV8\Exception\Exception;
use Picqer\BolRetailerV8\Exception\RateLimitException;
use Picqer\BolRetailerV8\Exception\ResponseException;
use Picqer\BolRetailerV8\Exception\ServerException;
use Picqer\BolRetailerV8\Exception\UnauthorizedException;
use Picqer\BolRetailerV8\JWTToken;
use Picqer\BolRetailerV8\Model\AbstractModel;
use Psr\Http\Message\ResponseInterface;

class BaseClientTest extends TestCase
{

    /** @var BaseClient */
    private $client;

    /** @var HttpClient */
    private $httpClientMock;

    /** @var string */
    private $modelClass;

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
    }

    public function testClientIsInitiallyNotAuthenticated()
    {
        $this->assertFalse($this->client->isAuthenticated());
    }

    protected function constructToken(array $claims): JWTToken
    {
        return new JWTToken('x.' . base64_encode(json_encode($claims)) . '.y');
    }

    public function testClientIsAuthenticatedByStoredAccessToken()
    {
        $token = $this->constructToken(['exp' => time() + 10]);
        $this->client->setAccessToken($token);

        $this->assertTrue($this->client->isAuthenticated());
    }

    public function testClientIsNotAuthenticatedByExpiredAccessToken()
    {
        $token = $this->constructToken(['exp' => time() - 10]);
        $this->client->setAccessToken($token);

        $this->assertFalse($this->client->isAuthenticated());
    }

    public function testAccessTokenExpiredCallbackIsCalledOnExpiredAccessToken()
    {
        $token = $this->constructToken(['exp' => time() - 10]);
        $this->client->setAccessToken($token);

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

    public function testRequestContinuesAfterSettingValidAccessToken()
    {
        $token = $this->constructToken(['exp' => time() - 10]);
        $this->client->setAccessToken($token);

        $this->client->setAccessTokenExpiredCallback(function (BaseClient $client) use (&$callbackCalled) {
            $token = $this->constructToken(['exp' => time() + 10]);
            $client->setAccessToken($token);
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

    protected function authenticateByClientCredentials($response = null)
    {
        if ($response === null) {
            $response = file_get_contents(__DIR__ . '/Fixtures/http/200-token');
        }

        if (is_string($response)) {
            $response = str_replace('<access_token>', $this->constructToken(['exp' => time() + 10])->encode(), $response);
            $response = Message::parseResponse($response);
        }

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
    }

    public function testClientAcceptsLowercaseScopeInAccessToken()
    {
        $this->authenticateByClientCredentials(file_get_contents(__DIR__ . '/Fixtures/http/200-token-lowercase-scope'));

        $this->assertTrue($this->client->isAuthenticated());
    }

    public function testClientAcceptsLowercaseTokenTypeInAccessToken()
    {
        $this->authenticateByClientCredentials(file_get_contents(__DIR__ . '/Fixtures/http/200-token-lowercase-type'));

        $this->assertTrue($this->client->isAuthenticated());
    }

    public function testAccessTokenIsExpired()
    {
        $response = file_get_contents(__DIR__ . '/Fixtures/http/200-token-expires-immediately');
        $response = str_replace('<access_token>', $this->constructToken(['exp' => time()-10])->encode(), $response);

        $this->authenticateByClientCredentials($response);

        $this->assertFalse($this->client->isAuthenticated());
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

    protected function authenticateByAuthorizationCode(?ResponseInterface $response = null): JWTToken
    {
        if ($response === null) {
            $response = file_get_contents(__DIR__ . '/Fixtures/http/200-authorization-code-token');
        }

        if (is_string($response)) {
            $response = str_replace('<access_token>', $this->constructToken(['exp' => time() + 10])->encode(), $response);
            $response = Message::parseResponse($response);
        }

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
        $this->assertEquals('eyJhbGciOiJub25lIn0.eyJleHAiOjE1NTM5MzY4MTQsImp0aSI6IjZhYmQ1NWNiLWFhOWQtNGM1Zi04OTczLWU5OTYwYjc4MmMyYiJ9.', $refreshToken->encode());
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

    protected function authenticateByRefreshToken(?ResponseInterface $response = null): JWTToken
    {
        if ($response === null) {
            $response = file_get_contents(__DIR__ . '/Fixtures/http/200-authorization-code-token');
        }

        if (is_string($response)) {
            $response = str_replace('<access_token>', $this->constructToken(['exp' => time() + 10])->encode(), $response);
            $response = Message::parseResponse($response);
        }

        $httpClientMock = $this->createMock(HttpClient::class);

        $credentials = base64_encode('secret_id' . ':' . 'somesupersecretvaluethatshouldnotbeshared');
        $refreshToken = $this->constructToken(['exp' => time() + 10]);

        $httpClientMock->method('request')->with('POST', 'https://login.bol.com/token', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . $credentials
            ],
            'query' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken->encode(),
            ]
        ])->willReturn($response);

        // use the HttpClient mock created in this method for authentication, put the original one back afterwards
        $prevHttpClient = $this->client->getHttp();
        $this->client->setHttp($httpClientMock);


        $refreshToken = $this->client->authenticateByRefreshToken('secret_id', 'somesupersecretvaluethatshouldnotbeshared', $refreshToken);

        $this->client->setHttp($prevHttpClient);

        return $refreshToken;
    }

    public function testAccessTokenIsRefreshed()
    {
        $token = $this->constructToken(['exp' => time() + 10]);
        $this->client->setAccessToken($token);

        $refreshToken = $this->authenticateByRefreshToken();

        $this->assertTrue($this->client->isAuthenticated());
        $this->assertEquals('eyJhbGciOiJub25lIn0.eyJleHAiOjE1NTM5MzY4MTQsImp0aSI6IjZhYmQ1NWNiLWFhOWQtNGM1Zi04OTczLWU5OTYwYjc4MmMyYiJ9.', $refreshToken->encode());
    }

    public function testAccessTokenWithExpiredRefreshTokenCannotBeRefreshed()
    {
        $token = $this->constructToken(['exp' => time() + 10]);
        $this->client->setAccessToken($token);

        $this->expectException(Exception::class);

        $refreshToken = $this->constructToken(['exp' => time() - 10]);
        $this->client->authenticateByRefreshToken('secret_id', 'somesupersecretvaluethatshouldnotbeshared', $refreshToken);
    }

    public function testRefreshTokenThrowsUnauthorizedExceptionWhenUsingWithBadCredentials()
    {
        $token = $this->constructToken(['exp' => time() + 10]);
        $this->client->setAccessToken($token);

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
        $this->client->authenticateByRefreshToken('secret_id', 'somesupersecretvaluethatshouldnotbeshared', $this->constructToken(['exp' => time() + 10]));
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
                return isset($options['headers']['Authorization']) && $options['headers']['Authorization'] === 'Bearer ' . $this->client->getAccessToken()->encode();
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
