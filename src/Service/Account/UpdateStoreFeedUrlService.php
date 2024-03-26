<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Service\Account;

use Http\Discovery\Exception\NotFoundException;
use Http\Discovery\Psr18ClientDiscovery;
use Klevu\PhpSDK\Api\Model\ApiResponseInterface;
use Klevu\PhpSDK\Api\Service\Account\UpdateStoreFeedUrlServiceInterface;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\AccountCredentials;
use Klevu\PhpSDK\Model\ApiResponse;
use Klevu\PhpSDK\Provider\BaseUrlsProvider;
use Klevu\PhpSDK\Provider\BaseUrlsProviderInterface;
use Klevu\PhpSDK\Provider\UserAgentProvider;
use Klevu\PhpSDK\Provider\UserAgentProviderInterface;
use Klevu\PhpSDK\Service\CreateEndpointTrait;
use Klevu\PhpSDK\Traits\MaskSensitiveDataTrait;
use Klevu\PhpSDK\Traits\Psr17FactoryTrait;
use Klevu\PhpSDK\Validator\Account\UpdateStoreFeedUrlPayloadValidator;
use Klevu\PhpSDK\Validator\AccountCredentialsValidator;
use Klevu\PhpSDK\Validator\JsApiKeyValidator;
use Klevu\PhpSDK\Validator\RestAuthKeyValidator;
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
 * Service used to update the XML data feed endpoint for accounts using the Klevu Feed Monitor service
 *
 * @since 1.0.0
 */
class UpdateStoreFeedUrlService implements UpdateStoreFeedUrlServiceInterface
{
    use CreateEndpointTrait;
    use MaskSensitiveDataTrait;
    use Psr17FactoryTrait;

    /**
     * Header key used to provide the JavaScript API Key (public key) in requests to update store feed API
     *
     * @var string
     */
    final public const API_HEADER_KEY_JSAPIKEY = 'X-KLEVU-JSAPIKEY';
    /**
     * Header key used to provide the REST AUTH Key (secret key) in requests to update store feed API
     *
     * @var string
     */
    final public const API_HEADER_KEY_RESTAPIKEY = 'X-KLEVU-RESTAPIKEY';

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
    private ValidatorInterface $accountCredentialsValidator;
    /**
     * @var ValidatorInterface
     */
    private ValidatorInterface $updateStoreFeedUrlPayloadValidator;
    /**
     * @var UserAgentProviderInterface
     */
    private readonly UserAgentProviderInterface $userAgentProvider;

    /**
     * @uses Psr18ClientDiscovery::find()
     *
     * @param BaseUrlsProviderInterface|null $baseUrlsProvider
     *      If null, a new instance of {@see BaseUrlsProvider} is used
     * @param ClientInterface|null $httpClient
     *      If null, discovery of compatible clients will be attempted
     * @param LoggerInterface|null $logger
     * @param ValidatorInterface|null $accountCredentialsValidator
     *      If null, a new instance of {@see AccountCredentialsValidator} is used
     * @param ValidatorInterface|null $updateStoreFeedUrlPayloadValidator
     *      If null, a new instance of {@see UpdateStoreFeedUrlPayloadValidator} is used
     * @param RequestFactoryInterface|null $requestFactory
     * @param ResponseFactoryInterface|null $responseFactory
     * @param UserAgentProviderInterface|null $userAgentProvider
     *      If null, a new instance of {@see UserAgentProvider} is used
     *
     * @throws NotFoundException Where httpClient is not provided and no PSR-18 compatible ClientInterface
     *        can be automagically discovered
     */
    public function __construct(
        ?BaseUrlsProviderInterface $baseUrlsProvider = null,
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null,
        ?ValidatorInterface $accountCredentialsValidator = null,
        ?ValidatorInterface $updateStoreFeedUrlPayloadValidator = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?ResponseFactoryInterface $responseFactory = null,
        ?UserAgentProviderInterface $userAgentProvider = null,
    ) {
        $this->baseUrlsProvider = $baseUrlsProvider ?: new BaseUrlsProvider();
        $this->httpClient = $httpClient ?: Psr18ClientDiscovery::find();
        $this->logger = $logger;
        $this->accountCredentialsValidator = $accountCredentialsValidator ?: new AccountCredentialsValidator(
            new JsApiKeyValidator(),
            new RestAuthKeyValidator(),
        );
        $this->updateStoreFeedUrlPayloadValidator = $updateStoreFeedUrlPayloadValidator
            ?: new UpdateStoreFeedUrlPayloadValidator();
        $this->requestFactory = $requestFactory;
        $this->responseFactory = $responseFactory;
        $this->userAgentProvider = $userAgentProvider ?: new UserAgentProvider();
    }

    /**
     * Returns the full endpoint for requests made by this service class
     *
     * @example https://api.ksearchnet.com/user-account/public/platform/account/details/indexingUrl
     *
     * @return string
     * @throws \LogicException On internal errors encountered by the application, such as incorrectly
     *      configured base URLs information
     * @uses BaseUrlsProviderInterface::getApiUrl()
     */
    public function getEndpoint(): string
    {
        return $this->createEndpoint(
            baseUrl: $this->baseUrlsProvider->getApiUrl(),
            path: '/user-account/public/platform/account/details/indexingUrl',
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
     * @param AccountCredentials $accountCredentials
     * @param string $indexingUrl The new URL from which XML data feeds should be collected
     * @param string $storeType The store type. See \Klevu\PhpSDK\Model\Platforms for reference
     * @param string $storeUrl The URL, name, or other identifier for the store as configured in the Klevu account
     *
     * @return ApiResponseInterface
     * @throws ValidationException Where provided credentials fail internal validation. API request is NOT sent
     * @throws BadRequestException Where the Klevu service rejects the request as invalid (4xx response code)
     * @throws BadResponseException Where the Klevu service does not return a valid response (timeouts, 5xx response)
     */
    public function execute(
        AccountCredentials $accountCredentials,
        string $indexingUrl,
        string $storeType,
        string $storeUrl,
    ): ApiResponseInterface {
        $this->accountCredentialsValidator->execute($accountCredentials);

        $request = $this->buildRequest($accountCredentials, $indexingUrl, $storeType, $storeUrl);

        $requestBody = $request->getBody();
        $requestBodyContents = $requestBody->getContents();
        $requestBody->rewind();

        $this->logger?->debug('Request to update store feed URL', [
            'js_api_key' => $accountCredentials->jsApiKey,
            'indexing_url' => $indexingUrl,
            'store_type' => $storeType,
            'store_url' => $storeUrl,
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

            $this->logger?->debug('Response from update store feed URL request', [
                'js_api_key' => $accountCredentials->jsApiKey,
                'status_code' => $response->getStatusCode(),
                'response_time' => $endTime - $startTime,
                'indexing_url' => $indexingUrl,
                'store_type' => $storeType,
                'store_url' => $storeUrl,
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
     * @param AccountCredentials $accountCredentials
     * @param string $indexingUrl
     * @param string $storeType
     * @param string $storeUrl
     *
     * @return RequestInterface
     * @throws ValidationException
     */
    private function buildRequest(
        AccountCredentials $accountCredentials,
        string $indexingUrl,
        string $storeType,
        string $storeUrl,
    ): RequestInterface {
        $payload = [
            self::PAYLOAD_KEY_INDEXING_URL => $indexingUrl,
            self::PAYLOAD_KEY_STORE_TYPE => $storeType,
            self::PAYLOAD_KEY_STORE_URL => $storeUrl,
        ];
        $this->updateStoreFeedUrlPayloadValidator->execute($payload);

        $psr17Factory = $this->getPsr17Factory();
        $request = $psr17Factory->createRequest('PUT', $this->getEndpoint());
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withHeader('User-Agent', $this->userAgentProvider->execute());
        $request = $request->withHeader(self::API_HEADER_KEY_JSAPIKEY, $accountCredentials->jsApiKey);
        $request = $request->withHeader(self::API_HEADER_KEY_RESTAPIKEY, $accountCredentials->restAuthKey);

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
     * @throws BadRequestException
     * @throws BadResponseException
     */
    private function checkResponse(int $responseCode, string $responseBody): void
    {
        $comparableResponseBody = strtolower(trim($responseBody));

        switch (true) {
            case str_starts_with($comparableResponseBody, 'invalid feed url'):
            case str_starts_with($comparableResponseBody, 'invalid storetype'):
            case str_starts_with($comparableResponseBody, 'invalid store name'):
            case 400 === $responseCode:
            case 401 === $responseCode:
            case 403 === $responseCode:
            case 405 === $responseCode:
                throw new BadRequestException(
                    message: 'API request rejected by Klevu API',
                    code: $responseCode,
                );

            case 200 === $responseCode && '' === $comparableResponseBody: // Nothing back from server
            case 200 !== $responseCode: // Route not found, timeouts, server errors
                throw new BadResponseException(
                    message: 'Unexpected Response Code ' . $responseCode,
                    code: $responseCode,
                );
        }
    }
}
