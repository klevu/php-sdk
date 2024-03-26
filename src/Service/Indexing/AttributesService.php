<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Service\Indexing;

// phpcs:ignore SlevomatCodingStandard.Namespaces.UseOnlyWhitelistedNamespaces.NonFullyQualified
use Http\Discovery\Exception\NotFoundException;
use Http\Discovery\Psr18ClientDiscovery;
use Klevu\PhpSDK\Api\Model\ApiResponseInterface;
use Klevu\PhpSDK\Api\Model\Indexing\AttributeInterface;
use Klevu\PhpSDK\Api\Service\Indexing\AttributesServiceInterface;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\AccountCredentials;
use Klevu\PhpSDK\Model\ApiResponse;
use Klevu\PhpSDK\Model\Indexing\Attribute;
use Klevu\PhpSDK\Model\Indexing\AttributeFactory;
use Klevu\PhpSDK\Model\Indexing\AttributeIterator;
use Klevu\PhpSDK\Model\Indexing\AuthAlgorithms;
use Klevu\PhpSDK\Provider\BaseUrlsProvider;
use Klevu\PhpSDK\Provider\BaseUrlsProviderInterface;
use Klevu\PhpSDK\Provider\Indexing\IndexingVersions;
use Klevu\PhpSDK\Provider\RequestBearerTokenProvider;
use Klevu\PhpSDK\Provider\RequestBearerTokenProviderInterface;
use Klevu\PhpSDK\Provider\UserAgentProvider;
use Klevu\PhpSDK\Provider\UserAgentProviderInterface;
use Klevu\PhpSDK\Service\CreateEndpointTrait;
use Klevu\PhpSDK\Traits\MaskSensitiveDataTrait;
use Klevu\PhpSDK\Traits\Psr17FactoryTrait;
use Klevu\PhpSDK\Validator\AccountCredentialsValidator;
use Klevu\PhpSDK\Validator\Indexing\AttributeNameValidator;
use Klevu\PhpSDK\Validator\Indexing\AttributeValidator;
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
 * Service class responsible for managing attributes registered with the Klevu indexing service
 *
 * @link https://docs.klevu.com/indexing-apis/adding-additionalcustom-attributes-to-a-product
 * @link https://docs.klevu.com/indexing-apis/api-definition
 * @since 1.0.0
 */
class AttributesService implements AttributesServiceInterface
{
    use CreateEndpointTrait;
    use MaskSensitiveDataTrait;
    use Psr17FactoryTrait;

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
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $attributeNameValidator;
    /**
     * @var RequestBearerTokenProviderInterface
     */
    private readonly RequestBearerTokenProviderInterface $requestBearerTokenProvider;
    /**
     * @var AttributeFactory
     */
    private readonly AttributeFactory $attributeFactory;
    /**
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $attributeValidator;
    /**
     * @var UserAgentProviderInterface
     */
    private readonly UserAgentProviderInterface $userAgentProvider;
    /**
     * @var AuthAlgorithms
     */
    private readonly AuthAlgorithms $authAlgorithm;

    /**
     * @uses Psr18ClientDiscovery::find()
     *
     * @param BaseUrlsProviderInterface|null $baseUrlsProvider
     * @param ClientInterface|null $httpClient
     * @param LoggerInterface|null $logger
     * @param ValidatorInterface|null $accountCredentialsValidator
     * @param ValidatorInterface|null $attributeNameValidator
     * @param RequestBearerTokenProviderInterface|null $requestBearerTokenProvider
     * @param AttributeFactory|null $attributeFactory
     * @param RequestFactoryInterface|null $requestFactory
     * @param ResponseFactoryInterface|null $responseFactory
     * @param AuthAlgorithms $authAlgorithm
     * @param ValidatorInterface|null $attributeValidator
     * @param UserAgentProviderInterface|null $userAgentProvider
     *
     * @throws NotFoundException Where httpClient is not provided and no PSR-18 compatible ClientInterface
     *       can be automagically discovered
     */
    public function __construct(
        ?BaseUrlsProviderInterface $baseUrlsProvider = null,
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null,
        ?ValidatorInterface $accountCredentialsValidator = null,
        ?ValidatorInterface $attributeNameValidator = null,
        ?RequestBearerTokenProviderInterface $requestBearerTokenProvider = null,
        ?AttributeFactory $attributeFactory = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?ResponseFactoryInterface $responseFactory = null,
        ?ValidatorInterface $attributeValidator = null,
        ?UserAgentProviderInterface $userAgentProvider = null,
        AuthAlgorithms $authAlgorithm = AuthAlgorithms::HMAC_SHA384,
    ) {
        $this->baseUrlsProvider = $baseUrlsProvider ?: new BaseUrlsProvider();
        $this->httpClient = $httpClient ?: Psr18ClientDiscovery::find();
        $this->logger = $logger;
        $this->accountCredentialsValidator = $accountCredentialsValidator ?: new AccountCredentialsValidator();
        $this->attributeNameValidator = $attributeNameValidator ?: new AttributeNameValidator();
        $this->requestBearerTokenProvider = $requestBearerTokenProvider ?: new RequestBearerTokenProvider(
            logger: $this->logger,
            accountCredentialsValidator: $this->accountCredentialsValidator,
        );
        $this->attributeFactory = $attributeFactory ?: new AttributeFactory();
        $this->requestFactory = $requestFactory;
        $this->responseFactory = $responseFactory;
        $this->attributeValidator = $attributeValidator ?: new AttributeValidator(
            attributeNameValidator: $this->attributeNameValidator,
        );
        $this->userAgentProvider = $userAgentProvider ?: new UserAgentProvider();
        $this->authAlgorithm = $authAlgorithm;
    }

    /**
     * Returns the full endpoint for requests made by this service class
     *
     * @example https://indexing.ksearchnet.com/v2/analytics
     * @uses BaseUrlsProviderInterface::getIndexingUrl
     * @return string
     * @throws \LogicException On internal errors encountered by the application, such as incorrectly
     *        configured base URLs information
     */
    public function getEndpoint(): string
    {
        return $this->createEndpoint(
            baseUrl: $this->baseUrlsProvider->getIndexingUrl(IndexingVersions::JSON),
            path: '/attributes',
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
     * @note Attributes are identified by the attribute name property. Note that this is case-insensitive,
     * so if attribute FOO exists and the passed attribute name is foo, you will receive a response containing
     * the FOO object
     *
     * @param AccountCredentials $accountCredentials
     * @param string $attributeName
     *
     * @return AttributeInterface|null
     * @throws ValidationException Where the account credentials or attribute name arguments contain invalid
     *       information and fail internal validation. API request is NOT sent
     * @throws BadRequestException Where the Klevu service rejects the request as invalid (4xx response code)
     * @throws BadResponseException Where the Klevu service does not return a valid response (timeouts, 5xx response)
     */
    public function getByName(
        AccountCredentials $accountCredentials,
        string $attributeName,
    ): ?AttributeInterface {
        $attributes = $this->get(
            accountCredentials: $accountCredentials,
        );
        $filteredAttributes = $attributes->filter(
            static fn (AttributeInterface $attribute): bool => $attribute->getAttributeName() === $attributeName,
        );

        return $filteredAttributes->count()
            ? $filteredAttributes->current()
            : null;
    }

    /**
     * @param AccountCredentials $accountCredentials
     *
     * @return AttributeIterator
     * @throws ValidationException Where the account credentials contain invalid information and fail internal
     *       validation. API request is NOT sent
     * @throws BadRequestException Where the Klevu service rejects the request as invalid (4xx response code)
     * @throws BadResponseException Where the Klevu service does not return a valid response (timeouts, 5xx response)
     */
    public function get(
        AccountCredentials $accountCredentials,
    ): AttributeIterator {
        $this->validateAccountCredentials($accountCredentials);

        $request = $this->buildRequest(
            accountCredentials: $accountCredentials,
            method: 'GET',
            endpoint: $this->getEndpoint(),
        );
        $this->logger?->debug('Request to get indexing attributes list', [
            'js_api_key' => $accountCredentials->jsApiKey,
            'headers' => $this->maskHttpHeaders($request->getHeaders()),
        ]);

        try {
            $startTime = microtime(true);
            $response = $this->httpClient->sendRequest($request);
            $endTime = microtime(true);

            $responseBody = $response->getBody();
            $responseBodyContents = $responseBody->getContents();
            $responseBody->rewind();

            $this->logger?->debug('Response from indexing attributes list', [
                'js_api_key' => $accountCredentials->jsApiKey,
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

        $this->checkResponse(
            responseCode: $response->getStatusCode(),
            responseBody: $responseBodyContents,
        );

        /** @var mixed[][] $responseBodyDecoded */
        $responseBodyDecoded = @json_decode( // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
            json: $responseBodyContents,
            associative: true,
        ) ?? [json_last_error_msg()];
        // The fallback array should never occur unless checkResponse is changed
        //  or removed, as it validates the responseBodyContents

        $attributes = new AttributeIterator();
        try {
            foreach ($responseBodyDecoded as $attributeData) {
                $attributes->addItem(
                    item: $this->attributeFactory->create($attributeData),
                );
            }
        } catch (\TypeError | \ValueError $exception) {
            $this->logger?->error('Attribute API response format is invalid', [
                'js_api_key' => $accountCredentials->jsApiKey,
                'status_code' => $response->getStatusCode(),
                'headers' => $this->maskHttpHeaders($response->getHeaders()),
                'body' => $responseBody,
                'response_body_decoded' => $responseBodyDecoded,
                'error' => $exception->getMessage(),
            ]);

            throw new BadResponseException(
                message: 'Attribute API response format is invalid',
                code: $response->getStatusCode(),
                errors: array_map(
                    callback: '\strval', // Leading \ to keep phpstan happy
                    array: (array)($responseBodyDecoded['errors'] ?? []),
                ),
            );
        }

        return $attributes;
    }

    /**
     * @see AttributeValidator
     *
     * @param AttributeInterface $attribute
     * @param AccountCredentials $accountCredentials
     *
     * @return ApiResponseInterface
     * @throws BadRequestException Where the Klevu service rejects the request as invalid (4xx response code)
     * @throws BadResponseException Where the Klevu service does not return a valid response (timeouts, 5xx response)
     * @throws ValidationException Where the account credentials or attribute arguments contain invalid
     *        information and fail internal validation. API request is NOT sent
     */
    public function put(
        AccountCredentials $accountCredentials,
        AttributeInterface $attribute,
    ): ApiResponseInterface {
        $attributeName = $attribute->getAttributeName();
        $attributeData = $attribute->toArray();
        unset($attributeData[Attribute::FIELD_IMMUTABLE]);

        $this->validateAccountCredentials($accountCredentials);
        $this->attributeValidator->execute($attribute);

        $endpoint = $this->getEndpoint() . '/' . $attributeName;

        $request = $this->buildRequest(
            accountCredentials: $accountCredentials,
            method: 'PUT',
            endpoint: $endpoint,
            requestBody: (string)json_encode($attributeData),
        );
        $this->logger?->debug('Request to add or update indexing attribute', [
            'js_api_key' => $accountCredentials->jsApiKey,
            'headers' => $this->maskHttpHeaders($request->getHeaders()),
            'attribute_name' => $attributeName,
            'attribute_data' => $attributeData,
            'endpoint' => $endpoint,
        ]);

        try {
            $startTime = microtime(true);
            $response = $this->httpClient->sendRequest($request);
            $endTime = microtime(true);

            $responseBody = $response->getBody();
            $responseBodyContents = $responseBody->getContents();
            $responseBody->rewind();

            $this->logger?->debug('Response from put indexing attribute request', [
                'js_api_key' => $accountCredentials->jsApiKey,
                'attribute_name' => $attributeName,
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

        $this->checkResponse(
            responseCode: $response->getStatusCode(),
            responseBody: $responseBodyContents,
        );

        /** @var string[] $responseBodyDecoded */
        $responseBodyDecoded = @json_decode( // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
            json: $responseBodyContents,
            associative: true,
        ) ?? [json_last_error_msg()];
        // The fallback array should never occur unless checkResponse is changed
        //  or removed, as it validates the responseBodyContents
        /** @var string|string[] $responseMessage */
        $responseMessage = $responseBodyDecoded['message'] ?? '';
        if (is_array($responseMessage)) {
            $responseMessage = implode(', ', $responseMessage);
        }

        return new ApiResponse(
            responseCode: $response->getStatusCode(),
            message: $responseMessage,
            status: $responseBodyDecoded['status'] ?? null,
            jobId: $responseBodyDecoded['jobId'] ?? null,
            errors: array_key_exists('errors', $responseBodyDecoded)
                ? (array)$responseBodyDecoded['errors']
                : null,
        );
    }

    /**
     * @note Attributes are identified by the attribute name property. Note that this is case-insensitive,
     * so if attribute FOO exists and the passed attribute's name is foo, FOO will be deleted
     *
     * @param AccountCredentials $accountCredentials
     * @param AttributeInterface $attribute
     *
     * @return ApiResponseInterface
     * @throws ValidationException Where the account credentials or attribute arguments contain invalid
     *         information and fail internal validation. API request is NOT sent
     * @throws BadRequestException Where the Klevu service rejects the request as invalid (4xx response code)
     * @throws BadResponseException Where the Klevu service does not return a valid response (timeouts, 5xx response)
     */
    public function delete( // phpcs:ignore Security.BadFunctions.FilesystemFunctions.WarnFilesystem
        AccountCredentials $accountCredentials,
        AttributeInterface $attribute,
    ): ApiResponseInterface {
        return $this->deleteByName(
            accountCredentials: $accountCredentials,
            attributeName: $attribute->getAttributeName(),
        );
    }

    /**
     * @note Attributes are identified by the attribute name property. Note that this is case-insensitive,
     * so if attribute FOO exists and the passed attribute name is foo, FOO will be deleted
     *
     * @param AccountCredentials $accountCredentials
     * @param string $attributeName Alphanumeric string, optionally containing underscore
     *
     * @return ApiResponseInterface
     * @throws ValidationException Where the account credentials or attribute name arguments contain invalid
     *        information and fail internal validation. API request is NOT sent
     * @throws BadRequestException Where the Klevu service rejects the request as invalid (4xx response code)
     * @throws BadResponseException Where the Klevu service does not return a valid response (timeouts, 5xx response)
     */
    public function deleteByName(
        AccountCredentials $accountCredentials,
        string $attributeName,
    ): ApiResponseInterface {
        $this->validateAccountCredentials($accountCredentials);
        $this->attributeNameValidator->execute($attributeName);

        $endpoint = $this->getEndpoint() . '/' . $attributeName;

        $request = $this->buildRequest(
            accountCredentials: $accountCredentials,
            method: 'DELETE',
            endpoint: $endpoint,
        );
        $this->logger?->debug('Request to get delete indexing attribute', [
            'js_api_key' => $accountCredentials->jsApiKey,
            'headers' => $this->maskHttpHeaders($request->getHeaders()),
            'attribute_name' => $attributeName,
            'endpoint' => $endpoint,
        ]);

        try {
            $startTime = microtime(true);
            $response = $this->httpClient->sendRequest($request);
            $endTime = microtime(true);

            $responseBody = $response->getBody();
            $responseBodyContents = $responseBody->getContents();
            $responseBody->rewind();

            $this->logger?->debug('Response from delete indexing attributes request', [
                'js_api_key' => $accountCredentials->jsApiKey,
                'attribute_name' => $attributeName,
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

        $this->checkResponse(
            responseCode: $response->getStatusCode(),
            responseBody: $responseBodyContents,
        );

        /** @var string[] $responseBodyDecoded */
        $responseBodyDecoded = @json_decode( // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
            json: $responseBodyContents,
            associative: true,
        ) ?? [json_last_error_msg()];
        // The fallback array should never occur unless checkResponse is changed
        //  or removed, as it validates the responseBodyContents
        /** @var string|string[] $responseMessage */
        $responseMessage = $responseBodyDecoded['message'] ?? '';
        if (is_array($responseMessage)) {
            $responseMessage = implode(', ', $responseMessage);
        }

        return new ApiResponse(
            responseCode: $response->getStatusCode(),
            message: $responseMessage,
            jobId: $responseBodyDecoded['jobId'] ?? null,
        );
    }

    /**
     * @param AccountCredentials $accountCredentials
     *
     * @return void
     * @throws ValidationException
     */
    private function validateAccountCredentials(
        AccountCredentials $accountCredentials,
    ): void {
        try {
            $this->accountCredentialsValidator->execute($accountCredentials);
        } catch (ValidationException $exception) {
            throw new ValidationException(
                errors: $exception->getErrors(),
                message: 'Invalid account credentials',
                code: $exception->getCode(),
                previous: $exception,
            );
        }
    }

    /**
     * @param AccountCredentials $accountCredentials
     * @param string $method
     * @param string $endpoint
     * @param string|null $requestBody
     *
     * @return RequestInterface
     */
    private function buildRequest(
        AccountCredentials $accountCredentials,
        string $method,
        string $endpoint,
        ?string $requestBody = null,
    ): RequestInterface {
        $psr17Factory = $this->getPsr17Factory();
        $request = $psr17Factory->createRequest(
            method: $method,
            uri: $endpoint,
        );

        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withHeader('User-Agent', $this->userAgentProvider->execute());
        $request = $request->withHeader(self::API_HEADER_KEY_APIKEY, $accountCredentials->jsApiKey);
        $request = $request->withHeader(self::API_HEADER_KEY_AUTH_ALGO, $this->authAlgorithm->value);
        $request = $request->withHeader(self::API_HEADER_KEY_TIMESTAMP, date('c'));

        if (null !== $requestBody) {
            $request = $request->withBody(
                body: $psr17Factory->createStream($requestBody),
            );
        }

        try {
            $bearerToken = $this->requestBearerTokenProvider->getForRequest(
                accountCredentials: $accountCredentials,
                request: $request,
            );
        } catch (ValidationException $exception) {
            throw new BadRequestException(
                message: 'Could not generate valid bearer token',
                code: 400,
                previous: $exception,
            );
        }

        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $request = $request->withHeader('Authorization', 'Bearer ' . $bearerToken);

        return $request;
    }

    /**
     * @param int $responseCode
     * @param string|null $responseBody
     *
     * @return void
     * @throws BadResponseException
     * @throws BadRequestException
     */
    private function checkResponse(
        int $responseCode,
        ?string $responseBody = null,
    ): void {
        $responseMessage = null;
        if (null !== $responseBody) {
            /** @var array<string|string[]> $responseBodyDecoded */
            $responseBodyDecoded = @json_decode( // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
                $responseBody,
                true,
            );
            if (json_last_error()) {
                throw new BadResponseException(
                    message: 'Received invalid JSON response',
                    code: $responseCode,
                    errors: [
                        json_last_error_msg(),
                    ],
                );
            }

            $responseMessage = $responseBodyDecoded['message'] ?? null;
        }

        if (499 <= $responseCode) {
            throw new BadResponseException(
                message: sprintf(
                    'Unexpected Response Code [%d] %s',
                    $responseCode,
                    is_array($responseMessage)
                        ? implode(',', $responseMessage)
                        : $responseCode,
                ),
                code: $responseCode,
            );
        }

        if (400 <= $responseCode) {
            throw new BadRequestException(
                message: sprintf(
                    'API request rejected by Klevu API [%d] %s',
                    $responseCode,
                    is_array($responseMessage)
                        ? implode(',', $responseMessage)
                        : $responseCode,
                ),
                code: $responseCode,
            );
        }
    }
}
