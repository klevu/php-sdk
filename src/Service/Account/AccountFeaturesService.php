<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Service\Account;

use Http\Discovery\Exception\NotFoundException;
use Http\Discovery\Psr18ClientDiscovery;
use Klevu\PhpSDK\Api\Service\Account\AccountFeaturesServiceInterface;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ApiExceptionInterface;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\Account\AccountFeatures;
use Klevu\PhpSDK\Model\Account\AccountFeaturesFactory;
use Klevu\PhpSDK\Model\Account\AccountFeaturesOptions;
use Klevu\PhpSDK\Model\AccountCredentials;
use Klevu\PhpSDK\Provider\BaseUrlsProvider;
use Klevu\PhpSDK\Provider\BaseUrlsProviderInterface;
use Klevu\PhpSDK\Provider\UserAgentProvider;
use Klevu\PhpSDK\Provider\UserAgentProviderInterface;
use Klevu\PhpSDK\Service\CreateEndpointTrait;
use Klevu\PhpSDK\Traits\MaskSensitiveDataTrait;
use Klevu\PhpSDK\Traits\Psr17FactoryTrait;
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
 * Service class handling interactions with the Klevu Account Features endpoint
 *
 * @since 1.0.0
 */
class AccountFeaturesService implements AccountFeaturesServiceInterface
{
    use CreateEndpointTrait;
    use MaskSensitiveDataTrait;
    use Psr17FactoryTrait;

    /**
     * Header key used to provide the REST AUTH Key (secret key) in requests to account features API
     *
     * @var string
     */
    final public const API_HEADER_KEY_RESTAPIKEY = 'restApiKey';

    /**
     * @var BaseUrlsProviderInterface
     */
    private readonly BaseUrlsProviderInterface $baseUrlsProvider;
    /**
     * @var ClientInterface
     */
    private readonly ClientInterface $httpClient;
    /**
     * @var LoggerInterface|null
     */
    private readonly ?LoggerInterface $logger;
    /**
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $accountCredentialsValidator;
    /**
     * @var UserAgentProviderInterface
     */
    private readonly UserAgentProviderInterface $userAgentProvider;
    /**
     * @var AccountFeaturesFactory|null
     */
    private ?AccountFeaturesFactory $accountFeaturesFactory = null;

    /**
     * @uses Psr18ClientDiscovery::find()
     *
     * @param ClientInterface|null $httpClient
     *      If null, discovery of compatible clients will be attempted
     * @param LoggerInterface|null $logger
     * @param ValidatorInterface|null $accountCredentialsValidator
     *      If null, a new instance of {@see AccountCredentialsValidator} is used
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
        ?ValidatorInterface $accountCredentialsValidator = null,
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
        $this->requestFactory = $requestFactory;
        $this->responseFactory = $responseFactory;
        $this->userAgentProvider = $userAgentProvider ?: new UserAgentProvider();
    }

    /**
     * Returns the full endpoint for requests made by this service class
     *
     * @example https://tiers.klevu.com/uti/getFeatureValues
     * @uses BaseUrlsProviderInterface::getTiersUrl()
     * @return string
     * @throws \LogicException On internal errors encountered by the application, such as incorrectly
     *       configured base URLs information
     */
    public function getEndpoint(): string
    {
        return $this->createEndpoint(
            baseUrl: $this->baseUrlsProvider->getTiersUrl(),
            path: '/uti/getFeatureValues',
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
     * @param string[]|null $features List of feature flag strings to query values for
     *      If null, checks all features in \Klevu\PhpSDK\Model\Account\AccountFeaturesOptions
     *
     * @return AccountFeatures
     * @throws ValidationException Where provided credentials and/or feature list contains invalid information and
     *       fails internal validation. API request is NOT sent
     * @throws ApiExceptionInterface
     * @throws BadRequestException Where the Klevu service rejects the request as invalid (4xx response code)
     * @throws BadResponseException Where the Klevu service does not return a valid response (timeouts, 5xx response)
     */
    public function execute(
        AccountCredentials $accountCredentials,
        ?array $features = null,
    ): AccountFeatures {
        $this->accountCredentialsValidator->execute($accountCredentials);

        if (null === $features) {
            $features = array_column(AccountFeaturesOptions::cases(), 'value');
        }

        $request = $this->buildRequest($accountCredentials, $features);

        $requestBody = $request->getBody();
        $requestBodyContents = $requestBody->getContents();
        $requestBody->rewind();

        $this->logger?->debug('Request for Klevu account features check', [
            'js_api_key' => $accountCredentials->jsApiKey,
            'features' => $features,
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

            $this->logger?->debug('Response from Klevu account features check', [
                'js_api_key' => $accountCredentials->jsApiKey,
                'features' => $features,
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

        $parsedResponseBody = $this->parseResponseBody($responseBodyContents);
        $this->checkResponse($response->getStatusCode(), $parsedResponseBody);

        $accountFeaturesFactory = $this->getAccountFeaturesFactory();
        $featureFlags = $this->extractFeatureFlagsFromResponseBody($parsedResponseBody, $features);

        return $accountFeaturesFactory->create($featureFlags);
    }

    /**
     * @param AccountCredentials $accountCredentials
     * @param string[] $features
     *
     * @return RequestInterface
     */
    private function buildRequest(
        AccountCredentials $accountCredentials,
        array $features,
    ): RequestInterface {
        $psr17Factory = $this->getPsr17Factory();
        $request = $psr17Factory->createRequest('POST', $this->getEndpoint());
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        $request = $request->withHeader('User-Agent', $this->userAgentProvider->execute());
        $request = $request->withHeader(self::API_HEADER_KEY_RESTAPIKEY, $accountCredentials->restAuthKey);

        $requestBody = $psr17Factory->createStream(http_build_query([
            'restApiKey' => $accountCredentials->restAuthKey,
            'features' => implode(',', $features),
        ]));
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $request = $request->withBody($requestBody);

        return $request;
    }

    /**
     * @param string|null $responseBody
     *
     * @return \SimpleXMLElement
     * @throws BadResponseException
     */
    private function parseResponseBody(?string $responseBody): \SimpleXMLElement
    {
        if (!$responseBody) {
            throw new BadResponseException(
                message: 'No response body',
                code: 0,
            );
        }

        $libXmlInternalErrorsOriginalValue = libxml_use_internal_errors(true);
        $xmlException = null;
        try {
            $parsedResponseBody = new \SimpleXMLElement($responseBody);
        } catch (\Exception $xmlException) {
            $parsedResponseBody = false;
        }
        libxml_use_internal_errors($libXmlInternalErrorsOriginalValue);

        if (false === $parsedResponseBody) {
            throw new BadResponseException(
                message: 'Response body is not valid XML',
                code: $xmlException->getCode(),
                previous: $xmlException,
            );
        }

        return $parsedResponseBody;
    }

    /**
     * @param int $responseCode
     * @param \SimpleXMLElement $parsedResponseBody
     *
     * @return void
     * @throws BadRequestException
     * @throws BadResponseException
     */
    private function checkResponse(int $responseCode, \SimpleXMLElement $parsedResponseBody): void
    {
        $errors = [];
        if ($parsedResponseBody->error ?? null) {
            $errors[] = (string)$parsedResponseBody->error;
        }

        switch (true) {
            case 400 === $responseCode:
            case 401 === $responseCode:
            case 403 === $responseCode:
            case 405 === $responseCode:
            case 200 === $responseCode && $errors:
                throw new BadRequestException(
                    message: 'API request rejected by Klevu API',
                    code: $responseCode,
                    errors: $errors,
                );

            case !($parsedResponseBody->feature ?? null):
                throw new BadResponseException(
                    message: 'No feature information returned by Klevu API',
                    code: $responseCode,
                    errors: $errors,
                );

            case 200 !== $responseCode: // Route not found, timeouts, server errors
                throw new BadResponseException(
                    message: 'Unexpected Response Code ' . $responseCode,
                    code: $responseCode,
                    errors: $errors,
                );
        }
    }

    /**
     * @param \SimpleXMLElement $parsedResponseBody
     * @param string[] $requestedFeatures
     *
     * @return bool[]
     */
    private function extractFeatureFlagsFromResponseBody(
        \SimpleXMLElement $parsedResponseBody,
        array $requestedFeatures,
    ): array {
        $featureFlags = [];
        foreach ($parsedResponseBody->feature ?? [] as $feature) {
            try {
                $featureName = AccountFeaturesOptions::from((string)$feature->key)->name;
                $featureFlags[$featureName] = ('yes' === (string)$feature->value);
            } catch (\ValueError) {
                $this->logger?->warning('Unrecognised account feature encountered: "{feature}"', [
                    'feature' => $feature->key,
                ]);
            }
        }

        $returnedFeatures = array_map(
            static function (string $flagName): string {
                $enum = constant(
                    name: AccountFeaturesOptions::class . '::' . $flagName,
                );
                return $enum->value;
            },
            array_keys($featureFlags),
        );
        $missingFeatures = array_diff($requestedFeatures, $returnedFeatures);
        if ($missingFeatures) {
            $this->logger?->warning(
                'Some requested feature flags missing from response: {missing_features}',
                [
                    'requested_features' => $requestedFeatures,
                    'returned_features' => $returnedFeatures,
                    'missing_features' => implode(',', $missingFeatures),
                ],
            );
        }

        return $featureFlags;
    }

    /**
     * @return AccountFeaturesFactory
     */
    private function getAccountFeaturesFactory(): AccountFeaturesFactory
    {
        return $this->accountFeaturesFactory ??= new AccountFeaturesFactory();
    }
}
