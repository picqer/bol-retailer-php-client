<?php


namespace Picqer\BolRetailerV5\Tests;

use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Picqer\BolRetailerV5\BaseClient;
use Picqer\BolRetailerV5\Exception\RateLimitException;
use Picqer\BolRetailerV5\Exception\ResponseException;
use Picqer\BolRetailerV5\Exception\ServerException;
use Picqer\BolRetailerV5\Exception\UnauthorizedException;
use Picqer\BolRetailerV5\Model\AbstractModel;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;

class BaseClientTest extends TestCase
{

    /** @var BaseClient */
    private $client;

    /** @var ObjectProphecy */
    private $httpProphecy;

    /** @var ObjectProphecy */
    private $modelClass;

    public function setup(): void
    {
        $this->httpProphecy = $this->prophesize(HttpClient::class);
        $this->client = new BaseClient();
        $this->client->setHttp($this->httpProphecy->reveal());

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

    protected function authenticate(?ResponseInterface $response = null)
    {
        $response = $response ?? Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-token'));

        $credentials = base64_encode('secret_id' . ':' . 'somesupersecretvaluethatshouldnotbeshared');
        $this->httpProphecy->request('POST', 'https://login.bol.com/token', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . $credentials
            ],
            'query' => [
                'grant_type' => 'client_credentials'
            ]
        ])->willReturn($response);

        $this->client->authenticate('secret_id', 'somesupersecretvaluethatshouldnotbeshared');
    }

    public function testClientIsAuthenticatedAfterSuccessfulAuthentication()
    {
        $this->authenticate();

        $this->assertTrue($this->client->isAuthenticated());
    }

    public function testClientAcceptsLowercaseScopeInAccessToken()
    {
        $this->authenticate(Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-token-lowercase-scope')));

        $this->assertTrue($this->client->isAuthenticated());
    }

    public function testClientAcceptsLowercaseTokenTypeInAccessToken()
    {
        $this->authenticate(Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-token-lowercase-type')));

        $this->assertTrue($this->client->isAuthenticated());
    }

    public function testAuthenticateThrowsUnauthorizedExceptionWhenAuthenticatingWithBadCredentials()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/401-unauthorized'));
        $clientException = new GuzzleClientException(
            'BaseClient error',
            new Request('POST', 'dummy'),
            $response
        );

        $credentials = base64_encode('secret_id' . ':' . 'somesupersecretvaluethatshouldnotbeshared');
        $this->httpProphecy->request('POST', 'https://login.bol.com/token', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . $credentials
            ],
            'query' => [
                'grant_type' => 'client_credentials'
            ]
        ])->willThrow($clientException);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionCode(401);
        $this->expectExceptionMessage("Bad client credentials");
        $this->client->authenticate('secret_id', 'somesupersecretvaluethatshouldnotbeshared');
    }

    public function testAuthenticateThrowsRateLimitExceptionWhenTooManyRequests()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/429-too-many-requests'));
        $clientException = new GuzzleClientException(
            'BaseClient error',
            new Request('POST', 'dummy'),
            $response
        );

        $credentials = base64_encode('secret_id' . ':' . 'somesupersecretvaluethatshouldnotbeshared');
        $this->httpProphecy->request('POST', 'https://login.bol.com/token', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . $credentials
            ],
            'query' => [
                'grant_type' => 'client_credentials'
            ]
        ])->willThrow($clientException);

        $this->expectException(RateLimitException::class);
        $this->expectExceptionCode(429);
        $this->expectExceptionMessage("Too many requests, retry in 4 seconds.");
        $this->client->authenticate('secret_id', 'somesupersecretvaluethatshouldnotbeshared');
    }

    public function testAuthenticateThrowsResponseExceptionAtForbidden()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/403-forbidden-account_is_not_active'));
        $clientException = new GuzzleClientException(
            'BaseClient error',
            new Request('POST', 'dummy'),
            $response
        );

        $credentials = base64_encode('secret_id' . ':' . 'somesupersecretvaluethatshouldnotbeshared');
        $this->httpProphecy->request('POST', 'https://login.bol.com/token', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . $credentials
            ],
            'query' => [
                'grant_type' => 'client_credentials'
            ]
        ])->willThrow($clientException);

        $this->expectException(ResponseException::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage("Account is not active, access denied. Please contact partnerservice if this is unexpected.");
        $this->client->authenticate('secret_id', 'somesupersecretvaluethatshouldnotbeshared');
    }

    public function testAuthenticateThrowsServerExceptionAtInternalServerError()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/500-internal-server-error'));
        $clientException = new GuzzleClientException(
            'BaseClient error',
            new Request('POST', 'dummy'),
            $response
        );

        $credentials = base64_encode('secret_id' . ':' . 'somesupersecretvaluethatshouldnotbeshared');
        $this->httpProphecy->request('POST', 'https://login.bol.com/token', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . $credentials
            ],
            'query' => [
                'grant_type' => 'client_credentials'
            ]
        ])->willThrow($clientException);

        $this->expectException(ServerException::class);
        $this->expectExceptionCode(500);
        $this->client->authenticate('secret_id', 'somesupersecretvaluethatshouldnotbeshared');
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
    public function testAuthenticateThrowsResponseExceptionWhenTokenIsMalformed($response)
    {
        $credentials = base64_encode('secret_id' . ':' . 'somesupersecretvaluethatshouldnotbeshared');
        $this->httpProphecy->request('POST', 'https://login.bol.com/token', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . $credentials
            ],
            'query' => [
                'grant_type' => 'client_credentials'
            ]
        ])->willReturn($response);

        $this->expectException(ResponseException::class);
        $this->client->authenticate('secret_id', 'somesupersecretvaluethatshouldnotbeshared');
    }

    public function testRequestReturnsModel()
    {
        $this->authenticate();

        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-foo'));
        $this->httpProphecy->request(Argument::cetera())->willReturn($response);

        $response = $this->client->request('GET', 'foobar', [], [
            '200' => $this->modelClass
        ]);

        $this->assertInstanceOf($this->modelClass, $response);
        $this->assertEquals('bar', $response->foo);
    }

    public function testRequestReturnsString()
    {
        $this->authenticate();

        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-string'));
        $this->httpProphecy->request(Argument::cetera())->willReturn($response);

        $response = $this->client->request('GET', 'foobar', [], [
            '200' => 'string'
        ]);

        $this->assertEquals("This is a test string\n", $response);
    }

    public function testRequestReturnsNullAt404()
    {
        $this->authenticate();

        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/404-not-found'));
        $this->httpProphecy->request(Argument::cetera())->willReturn($response);

        $response = $this->client->request('GET', 'foobar', [], [
            '404' => 'null'
        ]);

        $this->assertNull($response);
    }

    public function testRequestThrowsResponseExceptionAtUnknownResponseType()
    {
        $this->authenticate();

        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-string'));
        $this->httpProphecy->request(Argument::cetera())->willReturn($response);

        $this->expectException(ResponseException::class);
        $this->client->request('GET', 'foobar', [], []);
    }

    public function testRequestAddsProducesAsAcceptHeader()
    {
        $this->authenticate();

        $actualArgs = null;
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-string'));
        $this->httpProphecy
            ->request(Argument::cetera())
            ->will(function ($args) use ($response, &$actualArgs) {
                $actualArgs = $args[2];
                return $response;
            })
            ->shouldBeCalled();

        $this->client->request('GET', 'foobar', [
            'produces' => 'application/vnd.retailer.v5+pdf'
        ], [
            '200' => 'string'
        ]);

        $this->assertArrayHasKey('headers', $actualArgs);
        $this->assertArrayHasKey('Accept', $actualArgs['headers']);
        $this->assertEquals('application/vnd.retailer.v5+pdf', $actualArgs['headers']['Accept']);
    }

    public function testRequestJsonEncodesBodyModelIntoBody()
    {
        $this->authenticate();

        $model = new $this->modelClass();
        $model->foo = 'bar';

        $actualArgs = null;
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-string'));
        $this->httpProphecy
            ->request(Argument::cetera())
            ->will(function ($args) use ($response, &$actualArgs) {
                $actualArgs = $args[2];
                return $response;
            })
            ->shouldBeCalled();

        $this->client->request('GET', 'foobar', [
            'body' => $model,
        ], [
            '200' => 'string'
        ]);

        $this->assertArrayHasKey('body', $actualArgs);
        $this->assertEquals(json_encode(['foo' => 'bar']), $actualArgs['body']);
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

        $this->authenticate();

        $model = new $modelClass();
        $model->foo = 'bar';

        $actualArgs = null;
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-string'));
        $this->httpProphecy
            ->request(Argument::cetera())
            ->will(function ($args) use ($response, &$actualArgs) {
                $actualArgs = $args[2];
                return $response;
            })
            ->shouldBeCalled();

        $this->client->request('GET', 'foobar', [
            'body' => $model,
        ], [
            '200' => 'string'
        ]);

        $this->assertArrayHasKey('body', $actualArgs);
        $this->assertEquals(json_encode(['foo' => 'bar']), $actualArgs['body']);
    }

    public function testRequestConstructsEndpoint()
    {
        $this->authenticate();

        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-string'));
        $this->httpProphecy
            ->request('GET', 'https://api.bol.com/retailer/foobar', Argument::cetera())
            ->willReturn($response)
            ->shouldBeCalled();

        $this->client->request('GET', 'foobar', [], [
            '200' => 'string'
        ]);
    }

    public function testRequestThrowsUnauthorizedExceptionWhenNotAuthenticated()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-foo'));
        $this->httpProphecy->request(Argument::cetera())->willReturn($response);

        $this->expectException(UnauthorizedException::class);
        $this->client->request('GET', 'foobar', [], []);
    }

    public function testQueryParameterIsSentInRequest()
    {
        $this->authenticate();

        $actualArgs = null;
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-string'));
        $this->httpProphecy
            ->request(Argument::cetera())
            ->will(function ($args) use ($response, &$actualArgs) {
                $actualArgs = $args[2];
                return $response;
            })
            ->shouldBeCalled();

        $this->client->request('GET', 'foobar', [
            'query' => [
                'foo' => 'bar'
            ],
        ], [
            '200' => 'string'
        ]);

        $this->assertArrayHasKey('query', $actualArgs);
        $this->assertEquals(['foo' => 'bar'], $actualArgs['query']);
    }

    public function testQueryParameterWithValueNullIsNotSentInRequest()
    {
        $this->authenticate();

        $actualArgs = null;
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-string'));
        $this->httpProphecy
            ->request(Argument::cetera())
            ->will(function ($args) use ($response, &$actualArgs) {
                $actualArgs = $args[2];
                return $response;
            })
            ->shouldBeCalled();

        $this->client->request('GET', 'foobar', [
            'query' => [
                'page' => null,
                'foo' => 'bar',
            ],
        ], [
            '200' => 'string'
        ]);

        $this->assertArrayHasKey('query', $actualArgs);
        $this->assertEquals(['foo' => 'bar'], $actualArgs['query']);
    }
}
