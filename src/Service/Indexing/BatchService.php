<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Service\Indexing;

use Http\Discovery\Exception\NotFoundException;
use Http\Discovery\Psr18ClientDiscovery;
use Klevu\PhpSDK\Api\Model\ApiResponseInterface;
use Klevu\PhpSDK\Api\Model\Indexing\RecordInterface;
use Klevu\PhpSDK\Api\Service\Indexing\BatchServiceInterface;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\AccountCredentials;
use Klevu\PhpSDK\Model\ApiResponse;
use Klevu\PhpSDK\Model\Indexing\AuthAlgorithms;
use Klevu\PhpSDK\Model\Indexing\RecordIterator;
use Klevu\PhpSDK\Provider\BaseUrlsProvider;
use Klevu\PhpSDK\Provider\BaseUrlsProviderInterface;
use Klevu\PhpSDK\Provider\Indexing\Batch\RequestPayloadProvider;
use Klevu\PhpSDK\Provider\Indexing\Batch\RequestPayloadProviderInterface;
use Klevu\PhpSDK\Provider\Indexing\IndexingVersions;
use Klevu\PhpSDK\Provider\RequestBearerTokenProvider;
use Klevu\PhpSDK\Provider\RequestBearerTokenProviderInterface;
use Klevu\PhpSDK\Provider\UserAgentProvider;
use Klevu\PhpSDK\Provider\UserAgentProviderInterface;
use Klevu\PhpSDK\Service\CreateEndpointTrait;
use Klevu\PhpSDK\Traits\MaskSensitiveDataTrait;
use Klevu\PhpSDK\Traits\Psr17FactoryTrait;
use Klevu\PhpSDK\Validator\AccountCredentialsValidator;
use Klevu\PhpSDK\Validator\Indexing\RecordValidator;
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
 * Service class responsible for adding and updating records in Klevu's index
 *
 * @link https://docs.klevu.com/indexing-apis/how-to-do-with-examples
 * @link https://docs.klevu.com/indexing-apis/api-definition
 * @since 1.0.0
 * @uses RequestPayloadProvider
 */
class BatchService implements BatchServiceInterface
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
    private readonly ValidatorInterface $recordValidator;
    /**
     * @var RequestBearerTokenProviderInterface
     */
    private readonly RequestBearerTokenProviderInterface $requestBearerTokenProvider;
    /**
     * @var RequestPayloadProviderInterface
     */
    private readonly RequestPayloadProviderInterface $requestPayloadProvider;
    /**
     * @var UserAgentProviderInterface
     */
    private readonly UserAgentProviderInterface $userAgentProvider;
    /**
     * @var AuthAlgorithms
     */
    private readonly AuthAlgorithms $authAlgorithm;
    /**
     * @var InvalidRecordMode
     */
    private readonly InvalidRecordMode $invalidRecordMode;
    /**
     * @var int
     */
    private readonly int $maxBatchSize;

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
     * @param ValidatorInterface|null $recordValidator
     *      If null, a new instance of {@see RecordValidator} is used
     * @param RequestBearerTokenProviderInterface|null $requestBearerTokenProvider
     *      If null, a new instance of {@see RequestBearerTokenProvider} is created using this class' logger
     *          and accountCredentialsValidator properties
     * @param RequestPayloadProviderInterface|null $requestPayloadProvider
     *      If null, a new instance of {@see RequestPayloadProvider} is used
     * @param RequestFactoryInterface|null $requestFactory
     * @param ResponseFactoryInterface|null $responseFactory
     * @param UserAgentProviderInterface|null $userAgentProvider
     *      If null, a new instance of {@see UserAgentProvider} is used
     * @param AuthAlgorithms $authAlgorithm
     * @param InvalidRecordMode $invalidRecordMode
     * @param int $maxBatchSize
     *
     * @throws NotFoundException Where httpClient is not provided and no PSR-18 compatible ClientInterface
     *        can be automagically discovered
     */
    public function __construct(
        ?BaseUrlsProviderInterface $baseUrlsProvider = null,
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null,
        ?ValidatorInterface $accountCredentialsValidator = null,
        ?ValidatorInterface $recordValidator = null,
        ?RequestBearerTokenProviderInterface $requestBearerTokenProvider = null,
        ?RequestPayloadProviderInterface $requestPayloadProvider = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?ResponseFactoryInterface $responseFactory = null,
        ?UserAgentProviderInterface $userAgentProvider = null,
        AuthAlgorithms $authAlgorithm = AuthAlgorithms::HMAC_SHA384,
        InvalidRecordMode $invalidRecordMode = InvalidRecordMode::SKIP,
        int $maxBatchSize = 250,
    ) {
        $this->baseUrlsProvider = $baseUrlsProvider ?: new BaseUrlsProvider();
        $this->httpClient = $httpClient ?: Psr18ClientDiscovery::find();
        $this->logger = $logger;
        $this->accountCredentialsValidator = $accountCredentialsValidator ?: new AccountCredentialsValidator();
        $this->recordValidator = $recordValidator ?: new RecordValidator();
        $this->requestBearerTokenProvider = $requestBearerTokenProvider ?: new RequestBearerTokenProvider(
            logger: $this->logger,
            accountCredentialsValidator: $this->accountCredentialsValidator,
        );
        $this->requestPayloadProvider = $requestPayloadProvider ?: new RequestPayloadProvider();
        $this->requestFactory = $requestFactory;
        $this->responseFactory = $responseFactory;
        $this->userAgentProvider = $userAgentProvider ?: new UserAgentProvider();
        $this->authAlgorithm = $authAlgorithm;
        $this->invalidRecordMode = $invalidRecordMode;
        $this->maxBatchSize = $maxBatchSize;
    }

    /**
     * Returns the full endpoint for requests made by this service class
     *
     * @example https://indexing.ksearchnet.com/v2/batch
     * @uses BaseUrlsProviderInterface::getIndexingUrl
     * @return string
     * @throws \LogicException On internal errors encountered by the application, such as incorrectly
     *      configured base URLs information
     */
    public function getEndpoint(): string
    {
        return $this->createEndpoint(
            baseUrl: $this->baseUrlsProvider->getIndexingUrl(IndexingVersions::JSON),
            path: '/batch',
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
     * @note If the invalid record mode is set to SKIP, invalid records will be ignored but any valid records
     *  will be sent to Klevu, which may lead to batch sizes smaller than expected. When the invalid record
     *  mode is set to FAIL, any invalid record will cause the entire batch to fail amd trigger am
     *  InvalidDataValidationException
     *
     * @param AccountCredentials $accountCredentials
     * @param RecordIterator $records
     *
     * @return ApiResponseInterface
     * @throws ValidationException Where the account credentials or record arguments contain invalid
     *           information and fail internal validation. API request is NOT sent
     * @throws InvalidDataValidationException When the number of records to send exceeds the batch size;
     *           any records fail validation and the invalid record mode is FAIL;
     *           or zero records are marked for send after validation is performed
     * @throws BadRequestException Where the Klevu service rejects the request as invalid (4xx response code)
     * @throws BadResponseException Where the Klevu service does not return a valid response (timeouts, 5xx response)
     */
    public function send(
        AccountCredentials $accountCredentials,
        RecordIterator $records,
    ): ApiResponseInterface {
        $this->validateAccountCredentials($accountCredentials);

        $allRecordsCount = $records->count();
        $this->validateRecordCount(
            recordCount: $allRecordsCount,
            allRecordsCount: $allRecordsCount,
        );

        $validRecords = $this->filterValidRecords($records);
        $this->validateRecordCount(
            recordCount: $validRecords->count(),
            allRecordsCount: $allRecordsCount,
        );

        $request = $this->buildRequest(
            accountCredentials: $accountCredentials,
            records: $validRecords,
        );
        $action = str_replace(
            search: [
                $this->baseUrlsProvider->getIndexingUrl(), // No version so logs are more informational
                'https://',
            ],
            replace: '',
            subject: (string)$request->getUri(),
        );

        $this->logger?->debug(
            message: sprintf('Request to send indexing records [%s]', $action),
            context: [
                'class' => static::class,
                'js_api_key' => $accountCredentials->jsApiKey,
                'headers' => $this->maskHttpHeaders($request->getHeaders()),
                'record_count' => $validRecords->count(),
            ],
        );

        try {
            $startTime = microtime(true);
            $response = $this->httpClient->sendRequest($request);
            $endTime = microtime(true);

            $responseBody = $response->getBody();
            $responseBodyContents = $responseBody->getContents();
            $responseBody->rewind();

            $this->logger?->debug(
                message: sprintf('Response from indexing records request [%s]', $action),
                context: [
                    'class' => static::class,
                    'js_api_key' => $accountCredentials->jsApiKey,
                    'status_code' => $response->getStatusCode(),
                    'response_time' => $endTime - $startTime,
                    'headers' => $this->maskHttpHeaders($response->getHeaders()),
                    'body' => $responseBodyContents,
                ],
            );
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

        /** @var mixed[] $responseBodyDecoded */
        $responseBodyDecoded = json_decode(
            json: $responseBodyContents,
            associative: true,
        );

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
     * @see $maxBatchSize
     *
     * @param int $allRecordsCount
     * @param int $recordCount
     *
     * @return void
     * @throws InvalidDataValidationException
     */
    private function validateRecordCount(
        int $recordCount,
        int $allRecordsCount,
    ): void {
        switch (true) {
            case $recordCount > $this->maxBatchSize:
                throw new InvalidDataValidationException(
                    errors: [
                        sprintf(
                            'Record count of %d exceeds max batch size of %d',
                            $recordCount,
                            $this->maxBatchSize,
                        ),
                    ],
                    message: 'Max batch size exceeded',
                );

            case !$recordCount && $allRecordsCount:
                throw new InvalidDataValidationException(
                    errors: [
                        'All records failed validation',
                    ],
                    message: 'No valid records found to send',
                );

            case !$recordCount:
                throw new InvalidDataValidationException(
                    errors: [
                        'No records provided for send',
                    ],
                    message: 'No valid records found to send',
                );
        }
    }

    /**
     * @param RecordIterator $records
     *
     * @return RecordIterator
     */
    private function filterValidRecords(RecordIterator $records): RecordIterator
    {
        $validRecords = new RecordIterator();
        $invalidRecordMessages = [];

        /**
         * @var int $recordIndex
         * @var RecordInterface $record
         */
        foreach ($records as $recordIndex => $record) {
            try {
                $this->recordValidator->execute($record);
                $validRecords->addItem($record);
            } catch (ValidationException $exception) {
                $invalidRecordMessages[$recordIndex] = array_map(
                    static fn (string $error): string => sprintf('Record #%s: %s', $recordIndex, $error),
                    $exception->getErrors(),
                );
            }
        }

        if ($invalidRecordMessages) {
            $invalidRecordCount = count($invalidRecordMessages);
            switch ($this->invalidRecordMode) {
                case InvalidRecordMode::FAIL:
                    throw new InvalidDataValidationException(
                        errors: array_merge([], ...$invalidRecordMessages),
                        message: sprintf(
                            '%d records were found invalid',
                            $invalidRecordCount,
                        ),
                    );

                case InvalidRecordMode::SKIP:
                    $errors = array_merge([], ...$invalidRecordMessages);
                    sort($errors);

                    $this->logger?->warning(
                        message: sprintf(
                            '%d records were found invalid and excluded from sync',
                            $invalidRecordCount,
                        ),
                        context: [
                            'class' => static::class,
                            'errors' => $errors,
                            'valid_record_count' => $records->count() - $invalidRecordCount,
                            'invalid_record_count' => $invalidRecordCount,
                        ],
                    );
                    break;
            }
        }

        return $validRecords;
    }

    /**
     * @param AccountCredentials $accountCredentials
     * @param RecordIterator $records
     *
     * @return RequestInterface
     */
    private function buildRequest(
        AccountCredentials $accountCredentials,
        RecordIterator $records,
    ): RequestInterface {
        $psr17Factory = $this->getPsr17Factory();
        $request = $psr17Factory->createRequest(
            method: 'PUT',
            uri: $this->getEndpoint(),
        );

        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withHeader('User-Agent', $this->userAgentProvider->execute());
        $request = $request->withHeader(self::API_HEADER_KEY_APIKEY, $accountCredentials->jsApiKey);
        $request = $request->withHeader(self::API_HEADER_KEY_AUTH_ALGO, $this->authAlgorithm->value);
        $request = $request->withHeader(self::API_HEADER_KEY_TIMESTAMP, date('c'));

        $request = $request->withBody(
            body: $psr17Factory->createStream(
                content: $this->requestPayloadProvider->get($records),
            ),
        );

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
            $responseBodyDecoded = @json_decode($responseBody, true);
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

        $errors = array_filter(
            array_merge(
                (array)$responseMessage,
                (array)($responseBodyDecoded['errors'] ?? []),
            ),
        );

        if (499 <= $responseCode) {
            throw new BadResponseException(
                message: sprintf(
                    'API request rejected by Klevu API [%d] %s',
                    $responseCode,
                    is_string($responseMessage) ? $responseMessage : '',
                ),
                code: $responseCode,
                errors: array_map(
                    callback: 'strval',
                    array: $errors,
                ),
            );
        }

        if (400 <= $responseCode) {
            throw new BadRequestException(
                message: sprintf(
                    'API request rejected by Klevu API [%d] %s',
                    $responseCode,
                    is_string($responseMessage) ? $responseMessage : '',
                ),
                code: $responseCode,
                errors: array_map(
                    callback: 'strval',
                    array: $errors,
                ),
            );
        }
    }
}
