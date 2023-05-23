<?php

namespace Tests;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use Illuminate\Contracts\Config\Repository as Config;
use PHPUnit\Framework\TestCase;
use Suggestion\Exceptions\SuggestionServiceException;
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

    /**
     * @return void
     * @throws SuggestionServiceException
     * @throws GuzzleException
     */
    public function testGet(): void
    {
        $businessUserId = 1;
        $workOfferId = 2;

        $responseBody = [
            'success' => true,
            'message' => 'Se retorna las afinidades',
            'result' => [
                'uuid' => 'abc',
                'results' => [
                    [
                        'match_user_id' => 123,
                        'affinity' => 0.78,
                        'rank' => 1,
                    ]
                ],
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
                        'work_offer_id' => $workOfferId,
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

    /**
     * @return void
     * @throws GuzzleException
     * @throws SuggestionServiceException
     */
    public function testGetHandlesClientException(): void
    {
        $exceptionMessage = 'Error de cliente';
        $exception = new ClientException(
            $exceptionMessage,
            new Request('POST', 'test'),
            new Response(400)
        );

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->will($this->throwException($exception));

        $config = $this->createMock(Config::class);

        $this->expectException(SuggestionServiceException::class);

        $suggestionClient = new SuggestionClient($client, $config);
        $businessUserId = 123;
        $workOfferId = 456;
        $suggestionClient->get($businessUserId, $workOfferId);
    }

    /**
     * @return void
     * @throws GuzzleException
     * @throws SuggestionServiceException
     */
    public function testGetHandlesConnectException(): void
    {
        $exceptionMessage = 'Error de conexión';
        $exception = new ClientException(
            $exceptionMessage,
            new Request('POST', 'test'),
            new Response(400)
        );

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->will($this->throwException($exception));

        $config = $this->createMock(Config::class);

        $this->expectException(SuggestionServiceException::class);

        $suggestionClient = new SuggestionClient($client, $config);
        $businessUserId = 123;
        $workOfferId = 456;
        $suggestionClient->get($businessUserId, $workOfferId);
    }

    /**
     * @return void
     * @throws GuzzleException
     * @throws SuggestionServiceException
     */
    public function testGetHandlesServerException(): void
    {
        $exceptionMessage = 'Error del servidor';
        $exception = new ClientException(
            $exceptionMessage,
            new Request('POST', 'test'),
            new Response(400)
        );

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->will($this->throwException($exception));

        $config = $this->createMock(Config::class);

        $this->expectException(SuggestionServiceException::class);

        $suggestionClient = new SuggestionClient($client, $config);
        $businessUserId = 123;
        $workOfferId = 456;
        $suggestionClient->get($businessUserId, $workOfferId);
    }

    /**
     * @return void
     * @throws GuzzleException
     * @throws SuggestionServiceException
     */
    public function testGetHandlesOtherExceptions(): void
    {
        $exceptionMessage = 'Error genérico';
        $exception = new ClientException(
            $exceptionMessage,
            new Request('POST', 'test'),
            new Response(400)
        );

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->will($this->throwException($exception));

        $config = $this->createMock(Config::class);

        $this->expectException(SuggestionServiceException::class);

        $suggestionClient = new SuggestionClient($client, $config);
        $businessUserId = 123;
        $workOfferId = 456;
        $suggestionClient->get($businessUserId, $workOfferId);
    }

    /**
     * @return void
     * @throws GuzzleException
     * @throws SuggestionServiceException
     */
    public function testInterestedHandlesSuccess(): void
    {
        $uuid = 'abc';
        $businessUserId = 3;
        $matchUserId = 1;
        $workOfferId = 2;

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
                        'action' => SuggestionClient::ACCEPT,
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

    /**
     * @return void
     * @throws GuzzleException
     * @throws SuggestionServiceException
     */
    public function testNoInterestedHandlesSuccess(): void
    {
        $uuid = 'abc';
        $businessUserId = 3;
        $matchUserId = 1;
        $workOfferId = 2;

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
                        'action' => SuggestionClient::DISCARD,
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