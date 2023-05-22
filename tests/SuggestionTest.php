<?php

namespace Tests;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use Illuminate\Contracts\Config\Repository as Config;
use PHPUnit\Framework\TestCase;
use Suggestion\SuggestionClient;

class SuggestionTest extends TestCase
{
    protected $suggestionClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(ClientInterface::class);
        $this->config = $this->createMock(Config::class);
        $this->suggestionClient = new SuggestionClient($this->client, $this->config);
    }

    public function testGet(): void
    {
        $businessUserId = 1;
        $workOfferId = 2;

        $responseBody = [
            'uuid' => 'abc',
            'results' => [
                [
                    'match_user_id' => 123,
                    'affinity' => 0.78,
                    'rank' => 1,
                ]
            ],
        ];

        $this->client
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'affinity-ml-hire',
                [
                    'json' => [
                        'work_offer_id' => (string)$workOfferId,
                        'business_user_id' => $businessUserId,
                    ]
                ]
            )
            ->willReturn(new Response(200, [], json_encode($responseBody)));

        $expectedResult = [
            'uuid' => 'abc',
            'data' => [
                'suggestions' => [
                    [
                        'match_user_id' => 123,
                        'affinity' => 0.78,
                        'rank' => 1,
                    ]
                ],
            ],
        ];

        $this->assertSame($expectedResult, $this->suggestionClient->get($businessUserId, $workOfferId));
    }

    public function testGetHandlesClientException(): void
    {
        $exceptionMessage = 'Error de cliente';
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->will($this->throwException(new RequestException($exceptionMessage, new Request('POST', 'test'))));

        $config = $this->createMock(Config::class);
        $suggestionClient = new SuggestionClient($client, $config);

        $businessUserId = 123;
        $workOfferId = 456;
        $result = $suggestionClient->get($businessUserId, $workOfferId);

        $expectedResult = [
            'error' => 'Se produjo un error al procesar tu solicitud. Por favor, intenta nuevamente más tarde.',
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetHandlesConnectException()
    {
        $exceptionMessage = 'Error de conexión';
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->willThrowException(new ConnectException($exceptionMessage, new Request('POST', 'test')));

        $config = $this->createMock(Config::class);
        $suggestionClient = new SuggestionClient($client, $config);

        $businessUserId = 123;
        $workOfferId = 456;
        $result = $suggestionClient->get($businessUserId, $workOfferId);

        $expectedResult = [
            'error' => 'No se pudo conectar con la API externa. Por favor, intenta nuevamente más tarde.',
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetHandlesServerException()
    {
        $exceptionMessage = 'Error del servidor';
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->willThrowException(new ServerException($exceptionMessage, new Request('POST', 'test'), new Response(500)));

        $config = $this->createMock(Config::class);
        $suggestionClient = new SuggestionClient($client, $config);

        $businessUserId = 123;
        $workOfferId = 456;
        $result = $suggestionClient->get($businessUserId, $workOfferId);

        $expectedResult = [
            'error' => 'Se produjo un error en el servidor. Por favor, intenta nuevamente más tarde.',
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetHandlesOtherExceptions()
    {
        $exceptionMessage = 'Error genérico';
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception($exceptionMessage));

        $config = $this->createMock(Config::class);
        $suggestionClient = new SuggestionClient($client, $config);

        $businessUserId = 123;
        $workOfferId = 456;
        $result = $suggestionClient->get($businessUserId, $workOfferId);

        $expectedResult = [
            'error' => 'Se produjo un error al procesar tu solicitud. Por favor, intenta nuevamente más tarde.',
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testInterestedHandlesSuccess()
    {
        $uuid = '8057ce6f-6e70-4d12-8f07-97f3a8bff06d';
        $businessUserId = 123;
        $matchUserId = 456;
        $workOfferId = 789;

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'affinity-ml-hire/change',
                [
                    'json' => [
                        'uuid' => $uuid,
                        'match_user_id' => $matchUserId,
                        'work_offer_id' => $workOfferId,
                        'business_user_id' => $businessUserId,
                        'action' => 'aceptar',
                    ],
                ]
            )
            ->willReturn(new Response(200));

        $config = $this->createMock(Config::class);
        $suggestionClient = new SuggestionClient($client, $config);

        $result = $suggestionClient->interested($uuid, $businessUserId, $matchUserId, $workOfferId);

        $expectedResult = [
            'result' => true,
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testNoInterestedHandlesSuccess()
    {
        $uuid = '8057ce6f-6e70-4d12-8f07-97f3a8bff06d';
        $businessUserId = 123;
        $matchUserId = 456;
        $workOfferId = 789;

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'affinity-ml-hire/change',
                [
                    'json' => [
                        'uuid' => $uuid,
                        'match_user_id' => $matchUserId,
                        'work_offer_id' => $workOfferId,
                        'business_user_id' => $businessUserId,
                        'action' => 'descartar',
                    ],
                ]
            )
            ->willReturn(new Response(200));

        $config = $this->createMock(Config::class);
        $suggestionClient = new SuggestionClient($client, $config);

        $result = $suggestionClient->noInterested($uuid, $businessUserId, $matchUserId, $workOfferId);

        $expectedResult = [
            'result' => true,
        ];

        $this->assertEquals($expectedResult, $result);
    }
}