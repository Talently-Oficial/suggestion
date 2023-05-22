<?php

namespace Suggestion;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class SuggestionClient
{

    /**
     * @var ClientInterface
     */
    protected $client;

    protected $logger;

    const ACCEPT = 'aceptar';
    const DISCARD = 'descartar';

    public function __construct(ClientInterface $client = null, Config $config = null, LoggerInterface $logger = null)
    {
        $this->client = $client ?? new Client([
            'base_uri' => $config->get('suggestion.url.' . $config->get('suggestion.environment')),
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $config->get('suggestion.api_key'),
            ],
        ]);
        $this->logger = $logger ?? new NullLogger();
    }

    public function get(int $businessUserId, int $workOfferId): ?array
    {
        try {
            $response = $this->client->request('POST', 'affinity-ml-hire', [
                'json' => [
                    'business_user_id' => $businessUserId,
                    'work_offer_id' => $workOfferId,
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                $result = json_decode($response->getBody(), true);

                return [
                    'uuid' => $result['uuid'],
                    'data' => $this->processData($result),
                ];
            }

            $this->logger->error('El estado de código es diferente a 200. Código: ' . $response->getStatusCode());

            return [
                'error' => 'Se produjo un error al procesar tu solicitud. Por favor, intenta nuevamente más tarde.',
            ];
        } catch (ClientException $exception) {

            return $this->handleClientException($exception);
        } catch (ConnectException $exception) {

            return $this->handleException($exception, 'No se pudo conectar con la API externa. Por favor, intenta nuevamente más tarde.');
        }  catch (ServerException  $exception) {

            return $this->handleException($exception, 'Se produjo un error en el servidor. Por favor, intenta nuevamente más tarde.');
        } catch (\Exception $exception) {

            return $this->handleException($exception, 'Se produjo un error al procesar tu solicitud. Por favor, intenta nuevamente más tarde.');
        }
    }

    public function interested(string $uuid, int $businessUserId, int $matchUserId, int $workOfferId): array
    {

        return $this->changeInterest(self::ACCEPT, $businessUserId, $uuid, $matchUserId, $workOfferId);
    }

    public function noInterested(string $uuid, int $businessUserId, int $matchUserId, int $workOfferId): array
    {

        return $this->changeInterest(self::DISCARD, $businessUserId, $uuid, $matchUserId, $workOfferId);
    }

    private function changeInterest(string $action, int $businessUserId, string $uuid, int $matchUserId, int $workOfferId): array
    {
        try {
            $response = $this->client->request('POST', 'affinity-ml-hire/change', [
                'json' => [
                    'action' => $action,
                    'business_user_id' => $businessUserId,
                    'match_user_id' => $matchUserId,
                    'uuid' => $uuid,
                    'work_offer_id' => $workOfferId,
                ]
            ]);

            if ($response->getStatusCode() === 200) {

                return [
                    'result' => true,
                ];
            }

            $this->logger->error('El estado de código es diferente a 200. Código: ' . $response->getStatusCode());

            return [
                'error' => 'Se produjo un error al procesar tu solicitud. Por favor, intenta nuevamente más tarde.',
            ];
        } catch (ClientException $exception) {

            return $this->handleClientException($exception);
        } catch (ConnectException $exception) {

            return $this->handleException($exception, 'No se pudo conectar con la API externa. Por favor, intenta nuevamente más tarde.');
        }  catch (ServerException  $exception) {

            return $this->handleException($exception, 'Se produjo un error en el servidor. Por favor, intenta nuevamente más tarde.');
        } catch (\Exception $exception) {

            return $this->handleException($exception, 'Se produjo un error al procesar tu solicitud. Por favor, intenta nuevamente más tarde.');
        }

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


    private function handleException(\Exception $exception, string $errorMessage): array
    {
        $this->logger->error('SUGGESTION: Error al conectar con la API externa: ' . $exception->getMessage());

        return [
            'error' => $errorMessage,
        ];
    }

    private function handleClientException(ClientException $exception): array
    {
        $this->logger->error('SUGGESTION: Error al conectar con la API externa: ' . $exception->getMessage());

        $response = $exception->getResponse();
        $statusCode = $response->getStatusCode();

        switch ($statusCode) {
            case 403:

                return [
                    'error' => 'No tienes permiso para acceder a este recurso.',
                ];
            case 404:

                return [
                    'error' => 'El recurso solicitado no se encontró.',
                ];
            default:

                return [
                    'error' => 'Se produjo un error al procesar tu solicitud. Por favor, intenta nuevamente más tarde.',
                ];
        }
    }

}
