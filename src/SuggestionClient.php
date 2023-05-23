<?php

namespace Suggestion;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Suggestion\Exceptions\SuggestionErrorCodes;
use Suggestion\Exceptions\SuggestionServiceException;

class SuggestionClient
{

    /**
     * @var ClientInterface
     */
    protected $client;

    protected $logger;

    const ACCEPT = 'aceptar';

    const DISCARD = 'descartar';

    /**
     * @param ClientInterface|null $client
     * @param Config|null $config
     * @param LoggerInterface|null $logger
     */
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

    /**
     * @param int $businessUserId
     * @param int $workOfferId
     * @return array|null
     * @throws GuzzleException
     * @throws SuggestionServiceException
     */
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
                    'uuid' => $result['result']['uuid'],
                    'data' => $this->processData($result['result']['results']),
                ];
            }

            $this->logger->error('El estado de código es diferente a 200. Código: ' . $response->getStatusCode());
            throw new SuggestionServiceException('Se produjo un error al procesar tu solicitud. Por favor, intenta nuevamente más tarde.', SuggestionErrorCodes::GENERIC_ERROR);
        } catch (ClientException $exception) {
            $this->handleClientException($exception);
        } catch (ConnectException $exception) {
            $this->handleException(
                $exception,
                SuggestionErrorCodes::CONNECTION_ERROR,
                'Se produjo un error al procesar tu solicitud. Por favor, intenta nuevamente más tarde.'
            );
        }  catch (ServerException  $exception) {
            $this->handleException(
                $exception,
                SuggestionErrorCodes::SERVER_ERROR,
                'Se produjo un error al procesar tu solicitud. Por favor, intenta nuevamente más tarde.'
            );
        } catch (\Exception $exception) {
            $this->handleException(
                $exception,
                SuggestionErrorCodes::UNEXPECTED_ERROR,
                'Se produjo un error al procesar tu solicitud. Por favor, intenta nuevamente más tarde.'
            );
        }
    }

    /**
     * @param string $uuid
     * @param int $businessUserId
     * @param int $matchUserId
     * @param int $workOfferId
     * @return true[]
     * @throws GuzzleException
     * @throws SuggestionServiceException
     */
    public function interested(string $uuid, int $businessUserId, int $matchUserId, int $workOfferId): array
    {
        try {
            return $this->changeInterest(self::ACCEPT, $businessUserId, $uuid, $matchUserId, $workOfferId);
        } catch (ClientException $exception) {
            $this->handleClientException($exception);
        } catch (\Exception $exception) {
            $this->handleException(
                $exception,
                SuggestionErrorCodes::UNEXPECTED_ERROR,
                'Se produjo un error al procesar tu solicitud. Por favor, intenta nuevamente más tarde.'
            );
        }
    }


    /**
     * @param string $uuid
     * @param int $businessUserId
     * @param int $matchUserId
     * @param int $workOfferId
     * @return true[]
     * @throws GuzzleException
     * @throws SuggestionServiceException
     */
    public function noInterested(string $uuid, int $businessUserId, int $matchUserId, int $workOfferId): array
    {
        try {
            return $this->changeInterest(self::DISCARD, $businessUserId, $uuid, $matchUserId, $workOfferId);
        } catch (ClientException $exception) {
            $this->handleClientException($exception);
        } catch (\Exception $exception) {
            $this->handleException(
                $exception,
                SuggestionErrorCodes::UNEXPECTED_ERROR,
                'Se produjo un error al procesar tu solicitud. Por favor, intenta nuevamente más tarde.'
            );
        }
    }

    /**
     * @param string $action
     * @param int $businessUserId
     * @param string $uuid
     * @param int $matchUserId
     * @param int $workOfferId
     * @return true[]
     * @throws GuzzleException
     * @throws SuggestionServiceException
     */
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
            throw new SuggestionServiceException('Se produjo un error al procesar tu solicitud. Por favor, intenta nuevamente más tarde.', 'SUGGESTION_000001');
        } catch (ClientException $exception) {
            $this->handleClientException($exception);
        } catch (ConnectException $exception) {
            $this->handleException(
                $exception,
                SuggestionErrorCodes::CONNECTION_ERROR,
                'Se produjo un error al procesar tu solicitud. Por favor, intenta nuevamente más tarde.'
            );
        }  catch (ServerException  $exception) {
            $this->handleException(
                $exception,
                SuggestionErrorCodes::SERVER_ERROR,
                'Se produjo un error al procesar tu solicitud. Por favor, intenta nuevamente más tarde.'
            );
        } catch (\Exception $exception) {
            $this->handleException(
                $exception,
                SuggestionErrorCodes::UNEXPECTED_ERROR,
                'Se produjo un error al procesar tu solicitud. Por favor, intenta nuevamente más tarde.'
            );
        }
    }

    /**
     * @param array $suggestions
     * @return array
     */
    private function processData(array $suggestions): array
    {

        return [
            'suggestions' => $this->processSuggestions(collect($suggestions)),
        ];
    }

    /**
     * @param Collection $results
     * @return array
     */
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

    /**
     * @param \Exception $exception
     * @param string $errorCode
     * @param string $errorMessage
     * @return void
     * @throws SuggestionServiceException
     */
    private function handleException(\Exception $exception, string $errorCode, string $errorMessage): void
    {
        $this->logger->error('SUGGESTION: Código: ' . $errorCode . ' Error al conectar con la API externa: ' . $exception->getMessage());
        throw new SuggestionServiceException($errorMessage, $errorCode);
    }

    /**
     * @param ClientException $exception
     * @return void
     * @throws SuggestionServiceException
     */
    private function handleClientException(ClientException $exception): void
    {
        $response = $exception->getResponse();
        $statusCode = $response->getStatusCode();

        switch ($statusCode) {
            case 400:
                $this->handleException(
                    $exception,
                    SuggestionErrorCodes::BAD_REQUEST,
                    'La solicitud es inválida. Por favor, revisa tus datos.'
                );
            case 401:
                $this->handleException(
                    $exception,
                    SuggestionErrorCodes::UNAUTHORIZED,
                    'Autenticación incorrecta. Por favor, revisa tus credenciales.'
                );
            case 403:
                $this->handleException(
                    $exception,
                    SuggestionErrorCodes::FORBIDDEN,
                    'No tienes permiso para acceder a este recurso.'
                );
            case 404:
                $this->handleException(
                    $exception,
                    SuggestionErrorCodes::NOT_FOUND,
                    'El recurso solicitado no se encontró.'
                );
            default:
                $this->handleException(
                    $exception,
                    SuggestionErrorCodes::SERVER_EXCEPTION,
                    'Se produjo un error al procesar tu solicitud. Por favor, intenta nuevamente más tarde.'
                );
        }
    }

}
