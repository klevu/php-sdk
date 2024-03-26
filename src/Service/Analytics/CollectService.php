<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Service\Analytics;

use Http\Discovery\Exception\NotFoundException;
use Http\Discovery\Psr18ClientDiscovery;
use Klevu\PhpSDK\Api\Model\ApiResponseInterface;
use Klevu\PhpSDK\Api\Service\Analytics\CollectServiceInterface;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\Analytics\Collect\Event;
use Klevu\PhpSDK\Model\Analytics\Collect\EventIterator;
use Klevu\PhpSDK\Model\ApiResponse;
use Klevu\PhpSDK\Provider\BaseUrlsProvider;
use Klevu\PhpSDK\Provider\BaseUrlsProviderInterface;
use Klevu\PhpSDK\Provider\UserAgentProvider;
use Klevu\PhpSDK\Provider\UserAgentProviderInterface;
use Klevu\PhpSDK\Service\CreateEndpointTrait;
use Klevu\PhpSDK\Traits\MaskSensitiveDataTrait;
use Klevu\PhpSDK\Traits\Psr17FactoryTrait;
use Klevu\PhpSDK\Validator\Analytics\Collect\EventValidator;
use Klevu\PhpSDK\Validator\ValidatorInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Service class handling send of analytics events to the /analytics/collect endpoint
 *
 * @since 1.0.0
 */
class CollectService implements CollectServiceInterface
{
    use CreateEndpointTrait;
    use MaskSensitiveDataTrait;
    use Psr17FactoryTrait;

    /**
     * @var BaseUrlsProviderInterface
     */
    private BaseUrlsProviderInterface $baseUrlsProvider;
    /**
     * @var ClientInterface
     */
    private ClientInterface $httpClient;
    /**
     * @var LoggerInterface|null
     */
    private ?LoggerInterface $logger;
    /**
     * @var ValidatorInterface
     */
    private ValidatorInterface $eventValidator;
    /**
     * @var UserAgentProviderInterface
     */
    private readonly UserAgentProviderInterface $userAgentProvider;

    /**
     * @uses Psr18ClientDiscovery::find()
     *
     * @param ClientInterface|null $httpClient
     *      If null, discovery of compatible clients will be attempted
     * @param LoggerInterface|null $logger
     * @param ValidatorInterface|null $eventValidator
     *      If null, a new instance of {@see EventValidator} is used
     * @param RequestFactoryInterface|null $requestFactory
     * @param ResponseFactoryInterface|null $responseFactory
     * @param UserAgentProviderInterface|null $userAgentProvider
     *      If null, a new instance of {@see UserAgentProvider} is used
     * @param BaseUrlsProviderInterface|null $baseUrlsProvider
     *      If null, a new instance of {@see BaseUrlsProvider} is used
     *
     * @throws NotFoundException Where httpClient is not provided and no PSR-18 compatible ClientInterface
     *      can be automagically discovered
     */
    public function __construct(
        ?BaseUrlsProviderInterface $baseUrlsProvider = null,
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null,
        ?ValidatorInterface $eventValidator = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?ResponseFactoryInterface $responseFactory = null,
        ?UserAgentProviderInterface $userAgentProvider = null,
    ) {
        $this->baseUrlsProvider = $baseUrlsProvider ?: new BaseUrlsProvider();
        $this->httpClient = $httpClient ?: Psr18ClientDiscovery::find();
        $this->logger = $logger;
        $this->eventValidator = $eventValidator ?: new EventValidator();
        $this->requestFactory = $requestFactory;
        $this->responseFactory = $responseFactory;
        $this->userAgentProvider = $userAgentProvider ?: new UserAgentProvider();
    }

    /**
     * Returns the full endpoint for requests made by this service class
     *
     * @example https://stats.klevu.com/analytics/collect
     * @uses BaseUrlsProviderInterface::getAnalyticsUrl()
     * @return string
     * @throws \LogicException On internal errors encountered by the application, such as incorrectly
     *        configured base URLs information
     */
    public function getEndpoint(): string
    {
        return $this->createEndpoint(
            baseUrl: $this->baseUrlsProvider->getAnalyticsUrl(),
            path: '/analytics/collect',
        );
    }

    /**
     * Returns the object responsible for handling User-Agent provision for this service
     *
     * Method provided to allow entry point for injecting and modifying user agent strings
     *
     * @return UserAgentProviderInterface|null
     */
    public function getUserAgentProvider(): ?UserAgentProviderInterface
    {
        return $this->userAgentProvider;
    }

    /**
     * @param EventIterator $events
     *
     * @return ApiResponseInterface
     * @throws ValidationException Where one or more provided events contain invalid information and fail internal
     *       validation. API request is NOT sent
     * @throws BadRequestException Where the Klevu service rejects the request as invalid (4xx response code)
     * @throws BadResponseException Where the Klevu service does not return a valid response (timeouts, 5xx response)
     */
    public function send(EventIterator $events): ApiResponseInterface
    {
        $events->walk([$this->eventValidator, 'execute']);

        $request = $this->buildRequest($events);

        $requestBody = $request->getBody();
        $requestBodyContents = $requestBody->getContents();
        $requestBody->rewind();

        $this->logger?->debug('Request for Klevu analytics collect', [
            'headers' => $this->maskHttpHeaders($request->getHeaders()),
            'body' => $requestBodyContents,
        ]);

        try {
            $startTime = microtime(true);
            $response = $this->httpClient->sendRequest($request);
            $endTime = microtime(true);

            $responseBody = $response->getBody();
            $responseBodyContents = $responseBody->getContents();
            $responseBody->rewind();

            $this->logger?->debug('Response from Klevu analytics collect', [
                'status_code' => $response->getStatusCode(),
                'response_time' => $endTime - $startTime,
                'headers' => $this->maskHttpHeaders($response->getHeaders()),
                'body' => $responseBodyContents,
            ]);
        } catch (RequestExceptionInterface $exception) {
            throw new BadRequestException(
                message: $exception->getMessage(),
                code: $exception->getCode(),
                previous: $exception,
            );
        } catch (ClientExceptionInterface | NetworkExceptionInterface $exception) {
            throw new BadResponseException(
                message: $exception->getMessage(),
                code: $exception->getCode(),
                previous: $exception,
            );
        }

        $this->checkResponse($response->getStatusCode(), $responseBodyContents);

        return new ApiResponse(
            responseCode: $response->getStatusCode(),
        );
    }

    /**
     * @param EventIterator $events
     *
     * @return RequestInterface
     */
    private function buildRequest(EventIterator $events): RequestInterface
    {
        $psr17Factory = $this->getPsr17Factory();
        $request = $psr17Factory->createRequest('POST', $this->getEndpoint());
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withHeader('User-Agent', $this->userAgentProvider->execute());

        $payload = array_map(
            static function (Event $event): array {
                $return = [
                    'event' => $event->event->value,
                    'event_apikey' => $event->apikey,
                    'event_version' => $event->version,
                    'event_data' => $event->data,
                ];

                if ($event->userProfile) {
                    $return['user_profile'] = array_filter([
                        'ip_address' => $event->userProfile->ipAddress,
                        'email' => $event->userProfile->email,
                    ]);
                }

                return $return;
            },
            $events->toArray(),
        );

        $requestBody = $psr17Factory->createStream(json_encode($payload) ?: '');
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $request = $request->withBody($requestBody);

        return $request;
    }

    /**
     * @param int $responseCode
     * @param string $responseBody
     *
     * @return void
     * @throws BadResponseException
     * @throws BadRequestException
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    private function checkResponse(int $responseCode, string $responseBody): void
    {
        if (200 === $responseCode) {
            return;
        }

        if (404 === $responseCode || 499 <= $responseCode) {
            throw new BadResponseException(
                message: 'Unexpected Response Code ' . $responseCode,
                code: $responseCode,
            );
        }

        throw new BadRequestException(
            message: 'API request rejected by Klevu API',
            code: $responseCode,
        );
    }
}
