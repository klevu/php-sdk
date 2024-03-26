<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Service\Indexing\Batch;

use Http\Discovery\Exception\NotFoundException;
use Klevu\PhpSDK\Api\Model\ApiResponseInterface;
use Klevu\PhpSDK\Api\Model\Indexing\RecordInterface;
use Klevu\PhpSDK\Api\Service\Indexing\BatchDeleteServiceInterface;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\AccountCredentials;
use Klevu\PhpSDK\Model\Indexing\AuthAlgorithms;
use Klevu\PhpSDK\Model\Indexing\RecordFactory;
use Klevu\PhpSDK\Model\Indexing\RecordIterator;
use Klevu\PhpSDK\Provider\BaseUrlsProvider;
use Klevu\PhpSDK\Provider\BaseUrlsProviderInterface;
use Klevu\PhpSDK\Provider\Indexing\Batch\Delete\RequestPayloadProvider;
use Klevu\PhpSDK\Provider\Indexing\Batch\RequestPayloadProviderInterface;
use Klevu\PhpSDK\Provider\Indexing\IndexingVersions;
use Klevu\PhpSDK\Provider\RequestBearerTokenProviderInterface;
use Klevu\PhpSDK\Provider\UserAgentProviderInterface;
use Klevu\PhpSDK\Service\CreateEndpointTrait;
use Klevu\PhpSDK\Service\Indexing\BatchService;
use Klevu\PhpSDK\Service\Indexing\InvalidRecordMode;
use Klevu\PhpSDK\Validator\Indexing\Record\IdValidator as RecordIdValidator;
use Klevu\PhpSDK\Validator\Indexing\RecordValidator;
use Klevu\PhpSDK\Validator\ValidatorInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Service class responsible for deleting records in Klevu's index
 *
 * @note This class extends the base BatchService class, but uses a different payload provider and
 *      data validators where none are explicitly provided during object instantiation
 *
 * @link https://docs.klevu.com/indexing-apis/deleting-an-item-from-the-catalog
 * @link https://docs.klevu.com/indexing-apis/api-definition
 * @since 1.0.0
 * @uses RequestPayloadProvider
 */
class DeleteService extends BatchService implements BatchDeleteServiceInterface
{
    use CreateEndpointTrait;

    /**
     * @var BaseUrlsProviderInterface
     */
    private readonly BaseUrlsProviderInterface $baseUrlsProvider;
    /**
     * @var RecordFactory
     */
    private readonly RecordFactory $recordFactory;

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
     *      (via parent __construct's null handling)
     * @param ValidatorInterface|null $recordValidator
     *      If null, a new instance of {@see RecordValidator} with validation on only id
     *      (via a new {@see RecordIdValidator}) will be created
     * @param RecordFactory|null $recordFactory
     *      If null, a new instanceof {@see RecordFactory} is used
     * @param RequestBearerTokenProviderInterface|null $requestBearerTokenProvider
     * @param RequestPayloadProviderInterface|null $requestPayloadProvider
     *      If null, a new instance of {@see RequestPayloadProvider} is used
     * @param RequestFactoryInterface|null $requestFactory
     * @param ResponseFactoryInterface|null $responseFactory
     * @param UserAgentProviderInterface|null $userAgentProvider
     *      If null, a new instance of {@see UserAgentProvider} is used (via parent __construct's null handling)
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
        ?RecordFactory $recordFactory = null,
        ?RequestBearerTokenProviderInterface $requestBearerTokenProvider = null,
        ?RequestPayloadProviderInterface $requestPayloadProvider = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?ResponseFactoryInterface $responseFactory = null,
        ?UserAgentProviderInterface $userAgentProvider = null,
        AuthAlgorithms $authAlgorithm = AuthAlgorithms::HMAC_SHA384,
        InvalidRecordMode $invalidRecordMode = InvalidRecordMode::SKIP,
        int $maxBatchSize = 250,
    ) {
        $this->baseUrlsProvider = $baseUrlsProvider ?? new BaseUrlsProvider();
        $this->recordFactory = $recordFactory ?? new RecordFactory();

        $recordValidator ??= new RecordValidator(
            dataValidators: [
                'id' => new RecordIdValidator(),
                'type' => null,
                'relations' => null,
                'attributes' => null,
                'display' => null,
            ],
        );
        $requestPayloadProvider ??= new RequestPayloadProvider();

        parent::__construct(
            baseUrlsProvider: $baseUrlsProvider,
            httpClient: $httpClient,
            logger: $logger,
            accountCredentialsValidator: $accountCredentialsValidator,
            recordValidator: $recordValidator,
            requestBearerTokenProvider: $requestBearerTokenProvider,
            requestPayloadProvider: $requestPayloadProvider,
            requestFactory: $requestFactory,
            responseFactory: $responseFactory,
            userAgentProvider: $userAgentProvider,
            authAlgorithm: $authAlgorithm,
            invalidRecordMode: $invalidRecordMode,
            maxBatchSize: $maxBatchSize,
        );
    }

    /**
     * Returns the full endpoint for requests made by this service class
     *
     * @example https://indexing.ksearchnet.com/v2/batch/delete
     * @uses BaseUrlsProviderInterface::getIndexingUrl
     * @return string
     * @throws \LogicException
     * @throws \LogicException On internal errors encountered by the application, such as incorrectly
     *      configured base URLs information
     */
    public function getEndpoint(): string
    {
        return $this->createEndpoint(
            baseUrl: $this->baseUrlsProvider->getIndexingUrl(IndexingVersions::JSON),
            path: '/batch/delete',
        );
    }

    /**
     * Sends a request to Klevu to delete the indexed records corresponding to the passed ids
     *      for the specified account
     *
     * @uses BatchService::send()
     *
     * @param string[] $recordIds
     * @param AccountCredentials $accountCredentials
     *
     * @return ApiResponseInterface
     * @throws BadRequestException Where the Klevu service rejects the request as invalid (4xx response code)
     * @throws BadResponseException Where the Klevu service does not return a valid response (timeouts, 5xx response)
     * @throws ValidationException Where the account credentials or record ids contain invalid
     *           information and fail internal validation. API request is NOT sent
     */
    public function sendByIds(
        AccountCredentials $accountCredentials,
        array $recordIds,
    ): ApiResponseInterface {
        return $this->send(
            accountCredentials: $accountCredentials,
            records: new RecordIterator(
                data: array_map(
                    fn (mixed $recordId): RecordInterface => $this->recordFactory->create([
                        'id' => $recordId,
                    ]),
                    array_unique($recordIds, SORT_REGULAR),
                ),
            ),
        );
    }
}
