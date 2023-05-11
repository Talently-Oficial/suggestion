<?php

namespace Suggestion;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Collection;

class SuggestionClient
{

    /**
     * @var ClientInterface
     */
    protected $client;

    public function __construct(ClientInterface $client = null, Config $config = null)
    {
        $this->client = $client ?? new Client([
            'base_uri' => $config->get('suggestion.url.' . $config->get('suggestion.environment')),
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $config->get('suggestion.api_key'),
            ],
        ]);
    }

    public function get(int $trsId, int $workOfferId): array
    {

        $response = $this->client->post('affinity-ml', [
            'json' => [
                'work_offer_ids' => (string)$workOfferId,
                'trs_id' => $trsId,
            ]
        ]);

        $result = json_decode($response->getBody(), true);

        return [
            'uuid' => $result['uuid'],
            'data' => $this->processData($result),
        ];
    }

    public function interested(string $uuid, int $matchUserId, int $workOfferId): bool
    {
        return $this->changeInterest('aceptar', $uuid, $matchUserId, $workOfferId);
    }

    public function noInterested(string $uuid, int $matchUserId, int $workOfferId): bool
    {
        return $this->changeInterest('descartar', $uuid, $matchUserId, $workOfferId);
    }

    private function changeInterest(string $action, string $uuid, int $matchUserId, int $workOfferId): bool
    {
        $response = $this->client->post('affinity-ml/change', [
            'json' => [
                'uuid' => $uuid,
                'match_user_id' => $matchUserId,
                'work_offer_ids' => $workOfferId,
                'action' => $action,
            ]
        ]);

        return $response->getStatusCode() === 200;
    }

    private function processData(array $result): array
    {
        return [
            'suggestions' => $this->processSuggestions(collect($result['results'])),
        ];
    }

    private function processSuggestions(Collection $results): array
    {
        return $results
            ->map(function ($suggestion) {
                return [
                    'match_user_id' => $suggestion['match_user_id'],
                    'affinity' => $suggestion['affinity'],
                    'rank' => $suggestion['rank'],
                ];
            })
            ->toArray();
    }

}
