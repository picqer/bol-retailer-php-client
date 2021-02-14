<?php


namespace Picqer\BolRetailerV4\Tests;

use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Picqer\BolRetailerV4\BaseClient;
use Picqer\BolRetailerV4\Exception\ResponseException;
use Picqer\BolRetailerV4\Exception\UnauthorizedException;
use Picqer\BolRetailerV4\Model\AbstractModel;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class BaseClientTest extends TestCase
{
    use ProphecyTrait;

    /** @var BaseClient */
    private $client;

    /** @var ObjectProphecy */
    private $httpProphecy;

    /** @var ObjectProphecy */
    private $modelClass;

    public function setup(): void
    {
        $this->httpProphecy = $this->prophesize(ClientInterface::class);
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

    protected function authenticate()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-token'));

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

        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-string'));
        $this->httpProphecy
            ->request('GET', 'https://api.bol.com/retailer/foobar', [
                'headers' => [
                    'Accept' => 'application/vnd.retailer.v4+pdf',
                    'Authorization' => 'Bearer ' . $this->client->getToken()['access_token']
                ]
            ])
            ->willReturn($response)
            ->shouldBeCalled();

        $this->client->request('GET', 'foobar', [
            'produces' => 'application/vnd.retailer.v4+pdf'
        ], [
            '200' => 'string'
        ]);
    }

    public function testRequestJsonEncodesBodyModelIntoBody()
    {
        $this->authenticate();

        $model = new $this->modelClass();
        $model->foo = 'bar';

        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-string'));
        $this->httpProphecy
            ->request('GET', 'https://api.bol.com/retailer/foobar', [
                'headers' => [
                    'Accept' => 'application/vnd.retailer.v4+json',
                    'Authorization' => 'Bearer ' . $this->client->getToken()['access_token'],
                    'Content-Type' => 'application/vnd.retailer.v4+json'
                ],
                'body' => json_encode($model)
            ])
            ->willReturn($response)
            ->shouldBeCalled();

        $this->client->request('GET', 'foobar', [
            'body' => $model,
        ], [
            '200' => 'string'
        ]);
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
}
