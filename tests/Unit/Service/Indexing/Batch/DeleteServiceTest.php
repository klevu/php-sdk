<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Service\Indexing\Batch;

use Http\Client\Exception\NetworkException;
use Http\Client\Exception\RequestException;
use Klevu\PhpSDK\Api\Model\Indexing\RecordInterface;
use Klevu\PhpSDK\Api\Service\Indexing\BatchDeleteServiceInterface;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\AccountCredentials;
use Klevu\PhpSDK\Model\ApiResponse;
use Klevu\PhpSDK\Model\Indexing\AuthAlgorithms;
use Klevu\PhpSDK\Model\Indexing\Record;
use Klevu\PhpSDK\Model\Indexing\RecordIterator;
use Klevu\PhpSDK\Provider\BaseUrlsProviderInterface;
use Klevu\PhpSDK\Provider\ComposableUserAgentProviderInterface;
use Klevu\PhpSDK\Provider\Indexing\IndexingVersions;
use Klevu\PhpSDK\Provider\RequestBearerTokenProvider;
use Klevu\PhpSDK\Provider\RequestBearerTokenProviderInterface;
use Klevu\PhpSDK\Service\ApiServiceInterface;
use Klevu\PhpSDK\Service\Indexing\Batch\DeleteService;
use Klevu\PhpSDK\Service\Indexing\InvalidRecordMode;
use Klevu\PhpSDK\Validator\RequestBearerToken\RequestHeaders\ApiKeyValidator;
use Klevu\PhpSDK\Validator\RequestBearerToken\RequestHeaders\AuthAlgorithmValidator;
use Klevu\PhpSDK\Validator\RequestBearerToken\RequestHeaders\ContentTypeValidator;
use Klevu\PhpSDK\Validator\RequestBearerToken\RequestHeaders\TimestampValidator;
use Klevu\PhpSDK\Validator\RequestBearerToken\RequestHeadersValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

#[CoversClass(DeleteService::class)]
class DeleteServiceTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $deleteService = new DeleteService();

        $this->assertInstanceOf(BatchDeleteServiceInterface::class, $deleteService);
    }

    public function testGetEndpoint(): void
    {
        $deleteService = new DeleteService();

        $this->assertSame(
            expected: 'https://indexing.ksearchnet.com/v2/batch/delete',
            actual: $deleteService->getEndpoint(),
        );
    }

    #[Test]
    #[TestWith(["indexing.klevu.com/v2", "https://indexing.klevu.com/v2/batch/delete"])]
    #[TestWith(["http://indexing.klevu.com/v2", "http://indexing.klevu.com/v2/batch/delete"])]
    #[TestWith(["https://indexing.klevu.com/v2/", "https://indexing.klevu.com/v2/batch/delete"])]
    #[TestWith(["https://indexing.klevu.com/foo/v2", "https://indexing.klevu.com/foo/v2/batch/delete"])]
    #[TestWith(["localhost:8080/v2", "https://localhost:8080/v2/batch/delete"])]
    public function testGetEndpoint_WithBaseUrlsProvider(
        string $v2IndexingUrl,
        string $expectedResult,
    ): void {
        $mockBaseUrlsProvider = $this->getMockBaseUrlsProvider();
        $mockBaseUrlsProvider->method('getIndexingUrl')
            ->will(
                $this->returnValueMap([
                    [IndexingVersions::XML, str_replace('/v2', '', $v2IndexingUrl)],
                    [IndexingVersions::JSON, $v2IndexingUrl],
                ]),
            );

        $deleteService = new DeleteService(
            baseUrlsProvider: $mockBaseUrlsProvider,
        );

        $this->assertSame(
            expected: $expectedResult,
            actual: $deleteService->getEndpoint(),
        );
    }

    #[Test]
    #[TestWith([""])]
    #[TestWith(["/"])]
    #[TestWith(["https://"])]
    public function testGetEndpoint_WithBaseUrlsProvider_Invalid(
        string $v2IndexingUrl,
    ): void {
        $mockBaseUrlsProvider = $this->getMockBaseUrlsProvider();
        $mockBaseUrlsProvider->method('getIndexingUrl')
            ->will(
                $this->returnValueMap([
                    [IndexingVersions::XML, str_replace('/v2', '', $v2IndexingUrl)],
                    [IndexingVersions::JSON, $v2IndexingUrl],
                ]),
            );

        $deleteService = new DeleteService(
            baseUrlsProvider: $mockBaseUrlsProvider,
        );

        $this->expectException(\LogicException::class);
        $deleteService->getEndpoint();
    }

    #[Test]
    public function testGetUserAgentProvider(): void
    {
        $deleteService = new DeleteService();

        $this->assertInstanceOf(
            expected: ComposableUserAgentProviderInterface::class,
            actual: $deleteService->getUserAgentProvider(),
        );
    }

    /**
     * @return RecordIterator
     */
    public static function getValidRecordFixtures(): RecordIterator
    {
        return new RecordIterator([
            new Record(
                id: '123-456',
                type: 'KLEVU_PRODUCT',
            ),
            new Record(
                id: 'abc',
                type: 'KLEVU_CATEGORY',
            ),
            new Record(
                id: '123-456',
                type: 'KLEVU_PRODUCT',
                relations: [
                    'categories' => [
                        'type' => 'KLEVU_PRODUCT',
                        'values' => [
                            'foo',
                        ],
                    ],
                    'channels' => [
                        'additionalProp1' => [
                            'type' => 'KLEVU_PRODUCT',
                            'values' => [
                                'foo',
                            ],
                        ],
                    ],
                    'grouping' => [
                        'type' => 'KLEVU_PRODUCT',
                        'values' => [
                            'foo',
                        ],
                        'channels' => [
                            'additionalProp1' => [
                                'type' => 'KLEVU_PRODUCT',
                                'values' => [
                                    'foo',
                                ],
                            ],
                        ],
                    ],
                ],
                attributes: [
                    'name' => [
                        'default' => 'foo',
                        'additionalProp1' => 'bar',
                        'additionalProp2' => 'baz',
                    ],
                    'sku' => 'TEST_PRODUCT',
                    'images' => [
                        [
                            'url' => 'https://klevu.com/foo.png',
                            'type' => 'default',
                            'height' => 0,
                            'width' => 0,
                        ],
                    ],
                    'prices' => [
                        [
                            'amount' => 0,
                            'currency' => 'GBP',
                            'type' => 'defaultPrice',
                        ],
                    ],
                    'categoryPath' => 'foo;;bar;baz',
                    'url' => 'https://www.klevu.com/test-product.html',
                    'inStock' => true,
                    'shortDescription' => [
                        'default' => '<h1>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</h1> 
                            <p>Pellentesque pellentesque dapibus erat eget efficitur.</p>',
                    ],
                    'description' => [
                        'default' => '&lt;h1&gt;Lorem ipsum dolor sit amet, consectetur adipiscing elit.&lt;/h1&gt; 
                            &lt;p&gt;Pellentesque pellentesque dapibus erat eget efficitur.&lt;/p&gt;',
                    ],
                    'boosting' => 1,
                    'rating' => 3.14,
                    'ratingCount' => 0,
                    'tags' => [
                        'foo',
                        'bar',
                        'baz',
                    ],
                    'colors' => [
                        [
                            'label' => [
                                'default' => 'foo',
                                'additionalProp1' => 'bar',
                            ],
                            'value' => 'baz',
                        ],
                    ],
                    'swatches' => [
                        [
                            'id' => 'foo',
                            'color' => 'red',
                            'swatchImage' => 'https://www.klevu.com/swatch.webp',
                            'image' => 'https://www.klevu.com/image.gif',
                            'numberOfAdditionalVariants' => 99,
                        ],
                    ],
                    'visibility' => 'catalog-search',
                    'additionalProp1' => 'string',
                ],
                display: [
                    'additionalProp1' => [],
                    'additionalProp2' => [],
                    'additionalProp3' => [],
                ],
            ),
        ]);
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testSend_Success(): array
    {
        $records = self::getValidRecordFixtures();

        return [
            [
                $records,
                json_encode([
                    'jobId' => '1234567890',
                ]),
                new ApiResponse(
                    responseCode: 200,
                    message: '',
                    status: null,
                    jobId: '1234567890',
                    errors: null,
                ),
            ],
            [
                $records,
                json_encode([
                    'message' => 'Job submitted successfully.',
                    'jobId' => '1234567890',
                ]),
                new ApiResponse(
                    responseCode: 200,
                    message: 'Job submitted successfully.',
                    status: null,
                    jobId: '1234567890',
                    errors: null,
                ),
            ],
            [
                $records,
                json_encode([
                    'errors' => [
                        'foo',
                    ],
                ]),
                new ApiResponse(
                    responseCode: 200,
                    message: '',
                    status: null,
                    jobId: null,
                    errors: ['foo'],
                ),
            ],
            [
                $records,
                json_encode([
                    'message' => 'Foo',
                    'status' => 'OK',
                ]),
                new ApiResponse(
                    responseCode: 200,
                    message: 'Foo',
                    status: 'OK',
                    jobId: null,
                    errors: null,
                ),
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testSend_Success')]
    public function testSend_Success(
        RecordIterator $records,
        string $apiResponse,
        ApiResponse $expectedResult,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $apiResponse,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) use ($records): bool {
                $this->assertSame('PUT', $request->getMethod());
                $this->assertSame(
                    expected: 'https://indexing.ksearchnet.com/v2/batch/delete',
                    actual: (string)$request->getUri(),
                );

                $this->assertSame(['indexing.ksearchnet.com'], $request->getHeader('Host'));
                $this->assertSame(['application/json'], $request->getHeader('Content-Type'));

                $userAgentHeaders = $request->getHeader('User-Agent');
                $this->assertIsArray($userAgentHeaders);
                $this->assertCount(1, $userAgentHeaders);
                $this->assertMatchesRegularExpression(
                    pattern: '#^klevu-php-sdk/\d(\.\d)+ \(PHP \d(\.\d)+\)#',
                    string: $userAgentHeaders[0],
                );

                $this->assertSame(
                    expected: ['klevu-1234567890'],
                    actual: $request->getHeader(ApiServiceInterface::API_HEADER_KEY_APIKEY),
                );
                $this->assertSame(
                    expected: ['HmacSHA384'],
                    actual: $request->getHeader(ApiServiceInterface::API_HEADER_KEY_AUTH_ALGO),
                );

                $timestampHeader = $request->getHeader(ApiServiceInterface::API_HEADER_KEY_TIMESTAMP);
                $this->assertIsArray($timestampHeader);
                $this->assertCount(1, $timestampHeader);
                $this->assertMatchesRegularExpression(
                    pattern: '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/',
                    string: $timestampHeader[0],
                );
                $timestampUnixtime = strtotime($timestampHeader[0]);
                $this->assertGreaterThan(
                    expected: time() - 3600,
                    actual: $timestampUnixtime,
                );
                $this->assertLessThan(
                    expected: time() + 60,
                    actual: $timestampUnixtime,
                );

                $authorizationHeader = $request->getHeader('Authorization');
                $this->assertIsArray($authorizationHeader);
                $this->assertCount(1, $authorizationHeader);
                $this->assertMatchesRegularExpression(
                    pattern: '#^Bearer [-A-Za-z0-9+/]*={0,3}$#',
                    string: $authorizationHeader[0],
                );

                $requestBody = $request->getBody();
                $requestBodyContents = $requestBody->getContents();
                $requestBody->rewind();

                $this->assertSame(
                    expected: json_encode([
                        'ids' => array_map(
                            static fn (RecordInterface $record): string => $record->getId(),
                            $records->toArray(),
                        ),
                    ]),
                    actual: $requestBodyContents,
                );

                return true;
            }))
            ->willReturn($mockResponse);

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $sendResponse = $deleteService->send(
            accountCredentials: $accountCredentials,
            records: $records,
        );

        $this->assertEquals(
            expected: $expectedResult,
            actual: $sendResponse,
        );
    }

    #[Test]
    #[DataProvider('dataProvider_testSend_Success')]
    public function testSend_Success_WithBaseUrlsProvider(
        RecordIterator $records,
        string $apiResponse,
        ApiResponse $expectedResult,
    ): void {
        $mockBaseUrlsProvider = $this->getMockBaseUrlsProvider();
        $mockBaseUrlsProvider->method('getIndexingUrl')
            ->will(
                $this->returnValueMap([
                    [IndexingVersions::XML, 'custom-indexing.klevu.com'],
                    [IndexingVersions::JSON, 'custom-indexing.klevu.com/v2'],
                ]),
            );

        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $apiResponse,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $this->assertSame('PUT', $request->getMethod());
                $this->assertSame(
                    expected: 'https://custom-indexing.klevu.com/v2/batch/delete',
                    actual: (string)$request->getUri(),
                );

                $this->assertSame(['custom-indexing.klevu.com'], $request->getHeader('Host'));

                // testSend_Success already checks other request assertions
                return true;
            }))
            ->willReturn($mockResponse);

        $deleteService = new DeleteService(
            baseUrlsProvider: $mockBaseUrlsProvider,
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $sendResponse = $deleteService->send(
            accountCredentials: $accountCredentials,
            records: $records,
        );

        $this->assertEquals(
            expected: $expectedResult,
            actual: $sendResponse,
        );
    }

    #[Test]
    #[DataProvider('dataProvider_testSend_Success')]
    public function testSend_Success_WithLogger(
        RecordIterator $records,
        string $apiResponse,
        ApiResponse $expectedResult,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $apiResponse,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            // testSend_Success already validates parameters assertions
            ->willReturn($mockResponse);

        $mockLogger = $this->getMockLogger();
        $mockLogger->expects($this->never())->method('critical');
        $mockLogger->expects($this->never())->method('emergency');
        $mockLogger->expects($this->never())->method('alert');
        $mockLogger->expects($this->never())->method('error');
        $mockLogger->expects($this->never())->method('warning');
        $mockLogger->expects($this->never())->method('notice');
        $mockLogger->expects($this->never())->method('info');

        $invocationRule = $this->exactly(3);
        $mockLogger->expects($invocationRule)
            ->method('debug')
            ->with(
                $this->callback(static function (string $message): bool {
                    return match ($message) {
                        'Generating bearer token for request',
                        'Request to send indexing records [/v2/batch/delete]',
                        'Response from indexing records request [/v2/batch/delete]' => true,
                        default => false,
                    };
                }),
                $this->callback(function (array $context) use ($invocationRule, $apiResponse): bool {
                    switch ($invocationRule->numberOfInvocations()) {
                        case 1: // Generate bearer token
                            $this->assertArrayHasKey('algorithm', $context);
                            $this->assertSame('sha384', $context['algorithm']);

                            $this->assertArrayHasKey('requestString', $context);
                            $this->assertNotEmpty($context['requestString']);

                            $this->assertArrayHasKey('secretKey', $context);
                            $this->assertMatchesRegularExpression(
                                pattern: '/^.{3}\*{7}$/',
                                string: $context['secretKey'],
                            );
                            break;

                        case 2: // Log request
                            $this->assertArrayHasKey('class', $context);
                            $this->assertSame(DeleteService::class, $context['class']);

                            $this->assertArrayHasKey('js_api_key', $context);
                            $this->assertSame('klevu-1234567890', $context['js_api_key']);

                            $this->assertArrayHasKey('headers', $context);
                            $this->assertIsArray($context['headers']);

                            $this->assertArrayHasKey('Host', $context['headers']);
                            $this->assertSame(['indexing.ksearchnet.com'], $context['headers']['Host']);

                            $this->assertArrayHasKey('Content-Type', $context['headers']);
                            $this->assertSame(['application/json'], $context['headers']['Content-Type']);

                            $this->assertArrayHasKey('User-Agent', $context['headers']);
                            $this->assertIsArray($context['headers']['User-Agent']);
                            $this->assertCount(1, $context['headers']['User-Agent']);
                            $this->assertMatchesRegularExpression(
                                pattern: '#^klevu-php-sdk/\d(\.\d)+ \(PHP \d(\.\d)+\)#',
                                string: $context['headers']['User-Agent'][0],
                            );

                            $this->assertSame(
                                expected: ['klevu-1234567890'],
                                actual: $context['headers'][ApiServiceInterface::API_HEADER_KEY_APIKEY],
                            );
                            $this->assertSame(
                                expected: ['HmacSHA384'],
                                actual: $context['headers'][ApiServiceInterface::API_HEADER_KEY_AUTH_ALGO],
                            );

                            $timestampHeader = $context['headers'][ApiServiceInterface::API_HEADER_KEY_TIMESTAMP]
                                ?? null;
                            $this->assertIsArray($timestampHeader);
                            $this->assertCount(1, $timestampHeader);
                            $this->assertMatchesRegularExpression(
                                pattern: '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/',
                                string: $timestampHeader[0],
                            );
                            $timestampUnixtime = strtotime($timestampHeader[0]);
                            $this->assertGreaterThan(
                                expected: time() - 3600,
                                actual: $timestampUnixtime,
                            );
                            $this->assertLessThan(
                                expected: time() + 60,
                                actual: $timestampUnixtime,
                            );

                            $authorizationHeader = $context['headers']['Authorization'];
                            $this->assertIsArray($authorizationHeader);
                            $this->assertCount(1, $authorizationHeader);
                            $this->assertMatchesRegularExpression(
                                pattern: '#^Bearer [-A-Za-z0-9+/]*={0,3}$#',
                                string: $authorizationHeader[0],
                            );
                            break;

                        case 3: // Log response
                            $this->assertArrayHasKey('class', $context);
                            $this->assertSame(DeleteService::class, $context['class']);

                            $this->assertArrayHasKey('js_api_key', $context);
                            $this->assertSame('klevu-1234567890', $context['js_api_key']);

                            $this->assertArrayHasKey('headers', $context);
                            $this->assertIsArray($context['headers']);

                            $this->assertArrayHasKey('body', $context);

                            $this->assertArrayHasKey('status_code', $context);
                            $this->assertSame(200, $context['status_code']);

                            $this->assertSame(
                                expected: $apiResponse,
                                actual: $context['body'],
                            );
                            break;
                    }
                    return true;
                }),
            );

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
            logger: $mockLogger,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $sendResponse = $deleteService->send(
            accountCredentials: $accountCredentials,
            records: $records,
        );

        $this->assertEquals(
            expected: $expectedResult,
            actual: $sendResponse,
        );
    }

    /**
     * @return RecordIterator[][]
     */
    public static function dataProvider_validRecords(): array
    {
        return [
            [
                self::getValidRecordFixtures(),
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_validRecords')]
    public function testSend_InvalidAccountCredentials(
        RecordIterator $records,
    ): void {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())
            ->method('sendRequest');

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'foo',
            restAuthKey: '',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid account credentials');
        $deleteService->send(
            accountCredentials: $accountCredentials,
            records: $records,
        );
    }

    #[Test]
    #[DataProvider('dataProvider_validRecords')]
    public function testSend_InvalidHeadersForBearerToken(
        RecordIterator $records,
    ): void {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())
            ->method('sendRequest');

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
            requestBearerTokenProvider: $this->getRequestBearerTokenProviderForFailedAuthAlgorithmValidation(),
            authAlgorithm: AuthAlgorithms::HMAC_SHA384,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Could not generate valid bearer token');
        $deleteService->send(
            accountCredentials: $accountCredentials,
            records: $records,
        );
    }

    #[Test]
    public function testSend_EmptyRequest(): void
    {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())
            ->method('sendRequest');

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('No valid records found to send');
        try {
            $deleteService->send(
                accountCredentials: $accountCredentials,
                records: new RecordIterator([]),
            );
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);
            $this->assertSame(
                expected: 'No records provided for send',
                actual: $errors[0],
            );

            throw $exception;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testSend_BatchSizeExceedsLimit(): array
    {
        $records = self::getValidRecordFixtures();
        $validRecord = $records->current();

        return [
            [
                (new RecordIterator(array_fill(
                    start_index: 0,
                    count: 251,
                    value: clone $validRecord,
                )))->walk(static function (Record &$record): void { // phpcs:ignore SlevomatCodingStandard.PHP.DisallowReference.DisallowedPassingByReference, Generic.Files.LineLength.TooLong
                    $record = new Record(
                        id: $record->getId() . rand(1, 99999),
                        type: $record->getType(),
                    );
                }),
                null,
            ],
            [
                (new RecordIterator(array_fill(
                    start_index: 0,
                    count: 2,
                    value: clone $validRecord,
                )))->walk(static function (Record &$record): void { // phpcs:ignore SlevomatCodingStandard.PHP.DisallowReference.DisallowedPassingByReference, Generic.Files.LineLength.TooLong
                    $record = new Record(
                        id: $record->getId() . rand(1, 99999),
                        type: $record->getType(),
                    );
                }),
                1,
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testSend_BatchSizeExceedsLimit')]
    public function testSend_BatchSizeExceedsLimit(
        RecordIterator $records,
        ?int $maxBatchSize,
    ): void {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())
            ->method('sendRequest');

        $deleteService = (null === $maxBatchSize)
            ? new DeleteService(
                httpClient: $mockHttpClient,
            )
            : new DeleteService(
                httpClient: $mockHttpClient,
                maxBatchSize: $maxBatchSize,
            );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Max batch size exceeded');
        $deleteService->send(
            accountCredentials: $accountCredentials,
            records: $records,
        );
    }

    #[Test]
    public function testSend_AllRecordsFailValidation_Skip(): void
    {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())
            ->method('sendRequest');

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
            invalidRecordMode: InvalidRecordMode::SKIP,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('No valid records found to send');
        try {
            $deleteService->send(
                accountCredentials: $accountCredentials,
                records: new RecordIterator([
                    new Record(
                        id: '',
                        type: 'bar',
                    ),
                ]),
            );
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);
            $this->assertSame(
                expected: 'All records failed validation',
                actual: $errors[0],
            );

            throw $exception;
        }
    }

    #[Test]
    public function testSend_AllRecordsFailValidation_Fail(): void
    {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())
            ->method('sendRequest');

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
            invalidRecordMode: InvalidRecordMode::FAIL,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('1 records were found invalid');
        try {
            $deleteService->send(
                accountCredentials: $accountCredentials,
                records: new RecordIterator([
                    new Record(
                        id: '',
                        type: '',
                    ),
                ]),
            );
        } catch (ValidationException $exception) {
            $this->assertEquals(
                expected: [
                    'Record #0: id: Record Id is required',
                ],
                actual: $exception->getErrors(),
            );

            throw $exception;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testSend_SomeRecordsFailValidation(): array
    {
        $records = self::getValidRecordFixtures();
        $records->addItem(
            item: new Record(
                id: '',
                type: '',
            ),
        );
        $records->addItem(
            item: new Record(
                id: 'foo',
                type: '',
            ),
        );

        return [
            [
                $records,
                json_encode([
                    'message' => ['Success'],
                    'jobId' => '1234567890',
                ]),
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testSend_SomeRecordsFailValidation')]
    public function testSend_SomeRecordsFailValidation_Skip(
        RecordIterator $records,
        string $apiResponse,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $apiResponse,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            // testSend_Success already validates parameters assertions
            ->willReturn($mockResponse);

        $mockLogger = $this->getMockLogger();
        $mockLogger->expects($this->once())
            ->method('warning')
            ->with(
                '1 records were found invalid and excluded from sync',
                [
                    'errors' => [
                        'Record #3: id: Record Id is required',
                    ],
                    'valid_record_count' => 4,
                    'invalid_record_count' => 1,
                    'class' => DeleteService::class,
                ],
            );

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
            logger: $mockLogger,
            invalidRecordMode: InvalidRecordMode::SKIP,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $result = $deleteService->send(
            accountCredentials: $accountCredentials,
            records: $records,
        );
        $this->assertSame(200, $result->getResponseCode());
    }

    #[Test]
    #[DataProvider('dataProvider_testSend_SomeRecordsFailValidation')]
    public function testSend_SomeRecordsFailValidation_Fail(
        RecordIterator $records,
        string $apiResponse, // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    ): void {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())
            ->method('sendRequest');

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
            invalidRecordMode: InvalidRecordMode::FAIL,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('1 records were found invalid');
        try {
            $deleteService->send(
                accountCredentials: $accountCredentials,
                records: $records,
            );
        } catch (ValidationException $exception) {
            $this->assertEquals(
                expected: [
                    'Record #3: id: Record Id is required',
                ],
                actual: $exception->getErrors(),
            );

            throw $exception;
        }
    }

    #[Test]
    #[DataProvider('dataProvider_validRecords')]
    public function testSend_SendRequest_ThrowsRequestException(
        RecordIterator $records,
    ): void {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(
                new RequestException('Test Exception', $this->getMockRequest()),
            );

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Test Exception');
        $deleteService->send(
            accountCredentials: $accountCredentials,
            records: $records,
        );
    }

    #[Test]
    #[DataProvider('dataProvider_validRecords')]
    public function testSend_SendRequest_ThrowsNetworkException(
        RecordIterator $records,
    ): void {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(
                new NetworkException('Test Exception', $this->getMockRequest()),
            );

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadResponseException::class);
        $this->expectExceptionMessage('Test Exception');
        $deleteService->send(
            accountCredentials: $accountCredentials,
            records: $records,
        );
    }

    #[Test]
    #[TestWith([400])]
    #[TestWith([401])]
    #[TestWith([403])]
    #[TestWith([404])]
    #[TestWith([405])]
    public function testSend_BadRequest(
        int $statusCode,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: $statusCode,
            bodyContents: (string)json_encode([
                'status' => 'BAD_REQUEST',
                'message' => 'Invalid Payload request',
                'errors' => [
                    'save.items[0]: Exception while validating field : id',
                    'save.items[0]: Exception while validating field : type',
                ],
            ]),
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage(
            sprintf(
                'API request rejected by Klevu API [%d] Invalid Payload request',
                $statusCode,
            ),
        );
        try {
            $deleteService->send(
                accountCredentials: $accountCredentials,
                records: self::getValidRecordFixtures(),
            );
        } catch (BadRequestException $exception) {
            $this->assertSame(
                expected: [
                    'Invalid Payload request',
                    'save.items[0]: Exception while validating field : id',
                    'save.items[0]: Exception while validating field : type',
                ],
                actual: $exception->getErrors(),
            );

            throw $exception;
        }
    }

    #[Test]
    #[TestWith([499])]
    #[TestWith([500])]
    #[TestWith([501])]
    #[TestWith([502])]
    #[TestWith([503])]
    #[TestWith([504])]
    public function testSend_BadResponse(
        int $statusCode,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: $statusCode,
            bodyContents: (string)json_encode([
                'status' => 'BAD_REQUEST',
            ]),
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadResponseException::class);
        $this->expectExceptionMessage(
            sprintf(
                'API request rejected by Klevu API [%d] ',
                $statusCode,
            ),
        );
        try {
            $deleteService->send(
                accountCredentials: $accountCredentials,
                records: self::getValidRecordFixtures(),
            );
        } catch (BadResponseException $exception) {
            $this->assertSame(
                expected: [],
                actual: $exception->getErrors(),
            );

            throw $exception;
        }
    }


    #[Test]
    #[TestWith(['500 Internal Server Error'])]
    public function testSend_InvalidResponse(
        string $apiResponse,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $apiResponse,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadResponseException::class);
        $this->expectExceptionMessage('Received invalid JSON response');
        try {
            $deleteService->send(
                accountCredentials: $accountCredentials,
                records: self::getValidRecordFixtures(),
            );
        } catch (BadResponseException $exception) {
            $this->assertSame(
                expected: [
                    'Syntax error',
                ],
                actual: $exception->getErrors(),
            );

            throw $exception;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testSendByIds_Success(): array
    {
        return [
            [
                ['1', '2', '3'],
                json_encode([
                    'jobId' => '1234567890',
                ]),
                new ApiResponse(
                    responseCode: 200,
                    message: '',
                    status: null,
                    jobId: '1234567890',
                    errors: null,
                ),
            ],
            [
                ['foo', 'bar', 'baz'],
                json_encode([
                    'message' => 'Job submitted successfully.',
                    'jobId' => '1234567890',
                ]),
                new ApiResponse(
                    responseCode: 200,
                    message: 'Job submitted successfully.',
                    status: null,
                    jobId: '1234567890',
                    errors: null,
                ),
            ],
            [
                ['ABC-DEF', 'ZZZ-123', '555-666'],
                json_encode([
                    'errors' => [
                        'foo',
                    ],
                ]),
                new ApiResponse(
                    responseCode: 200,
                    message: '',
                    status: null,
                    jobId: null,
                    errors: ['foo'],
                ),
            ],
            [
                [' recipe ', ' Recipe ', ' RECIPE '],
                json_encode([
                    'message' => 'Foo',
                    'status' => 'OK',
                ]),
                new ApiResponse(
                    responseCode: 200,
                    message: 'Foo',
                    status: 'OK',
                    jobId: null,
                    errors: null,
                ),
            ],
        ];
    }

    /**
     * @param string[] $recordIds
     * @param string $apiResponse
     * @param ApiResponse $expectedResult
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testSendByIds_Success')]
    public function testSendByIds_Success(
        array $recordIds,
        string $apiResponse,
        ApiResponse $expectedResult,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $apiResponse,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) use ($recordIds): bool {
                $this->assertSame('PUT', $request->getMethod());
                $this->assertSame(
                    expected: 'https://indexing.ksearchnet.com/v2/batch/delete',
                    actual: (string)$request->getUri(),
                );

                $this->assertSame(['indexing.ksearchnet.com'], $request->getHeader('Host'));
                $this->assertSame(['application/json'], $request->getHeader('Content-Type'));

                $userAgentHeaders = $request->getHeader('User-Agent');
                $this->assertIsArray($userAgentHeaders);
                $this->assertCount(1, $userAgentHeaders);
                $this->assertMatchesRegularExpression(
                    pattern: '#^klevu-php-sdk/\d(\.\d)+ \(PHP \d(\.\d)+\)#',
                    string: $userAgentHeaders[0],
                );

                $this->assertSame(
                    expected: ['klevu-1234567890'],
                    actual: $request->getHeader(ApiServiceInterface::API_HEADER_KEY_APIKEY),
                );
                $this->assertSame(
                    expected: ['HmacSHA384'],
                    actual: $request->getHeader(ApiServiceInterface::API_HEADER_KEY_AUTH_ALGO),
                );

                $timestampHeader = $request->getHeader(ApiServiceInterface::API_HEADER_KEY_TIMESTAMP);
                $this->assertIsArray($timestampHeader);
                $this->assertCount(1, $timestampHeader);
                $this->assertMatchesRegularExpression(
                    pattern: '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/',
                    string: $timestampHeader[0],
                );
                $timestampUnixtime = strtotime($timestampHeader[0]);
                $this->assertGreaterThan(
                    expected: time() - 3600,
                    actual: $timestampUnixtime,
                );
                $this->assertLessThan(
                    expected: time() + 60,
                    actual: $timestampUnixtime,
                );

                $authorizationHeader = $request->getHeader('Authorization');
                $this->assertIsArray($authorizationHeader);
                $this->assertCount(1, $authorizationHeader);
                $this->assertMatchesRegularExpression(
                    pattern: '#^Bearer [-A-Za-z0-9+/]*={0,3}$#',
                    string: $authorizationHeader[0],
                );

                $requestBody = $request->getBody();
                $requestBodyContents = $requestBody->getContents();
                $requestBody->rewind();

                $this->assertSame(
                    expected: json_encode([
                        'ids' => $recordIds,
                    ]),
                    actual: $requestBodyContents,
                );

                return true;
            }))
            ->willReturn($mockResponse);

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $sendResponse = $deleteService->sendByIds(
            accountCredentials: $accountCredentials,
            recordIds: $recordIds,
        );

        $this->assertEquals(
            expected: $expectedResult,
            actual: $sendResponse,
        );
    }

    /**
     * @param string[] $recordIds
     * @param string $apiResponse
     * @param ApiResponse $expectedResult
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testSendByIds_Success')]
    public function testSendByIds_Success_WithBaseUrlsProvider(
        array $recordIds,
        string $apiResponse,
        ApiResponse $expectedResult,
    ): void {
        $mockBaseUrlsProvider = $this->getMockBaseUrlsProvider();
        $mockBaseUrlsProvider->method('getIndexingUrl')
            ->will(
                $this->returnValueMap([
                    [IndexingVersions::XML, 'custom-indexing.klevu.com'],
                    [IndexingVersions::JSON, 'custom-indexing.klevu.com/v2'],
                ]),
            );

        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $apiResponse,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $this->assertSame('PUT', $request->getMethod());
                $this->assertSame(
                    expected: 'https://custom-indexing.klevu.com/v2/batch/delete',
                    actual: (string)$request->getUri(),
                );

                $this->assertSame(['custom-indexing.klevu.com'], $request->getHeader('Host'));

                // testSend_Success already checks other request assertions
                return true;
            }))
            ->willReturn($mockResponse);

        $deleteService = new DeleteService(
            baseUrlsProvider: $mockBaseUrlsProvider,
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $sendResponse = $deleteService->sendByIds(
            accountCredentials: $accountCredentials,
            recordIds: $recordIds,
        );

        $this->assertEquals(
            expected: $expectedResult,
            actual: $sendResponse,
        );
    }

    /**
     * @param string[] $recordIds
     * @param string $apiResponse
     * @param ApiResponse $expectedResult
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testSendByIds_Success')]
    public function testSendByIds_Success_WithLogger(
        array $recordIds,
        string $apiResponse,
        ApiResponse $expectedResult,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $apiResponse,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            // testSend_Success already validates parameters assertions
            ->willReturn($mockResponse);

        $mockLogger = $this->getMockLogger();
        $mockLogger->expects($this->never())->method('critical');
        $mockLogger->expects($this->never())->method('emergency');
        $mockLogger->expects($this->never())->method('alert');
        $mockLogger->expects($this->never())->method('error');
        $mockLogger->expects($this->never())->method('warning');
        $mockLogger->expects($this->never())->method('notice');
        $mockLogger->expects($this->never())->method('info');

        $invocationRule = $this->exactly(3);
        $mockLogger->expects($invocationRule)
            ->method('debug')
            ->with(
                $this->callback(static function (string $message): bool {
                    return match ($message) {
                        'Generating bearer token for request',
                        'Request to send indexing records [/v2/batch/delete]',
                        'Response from indexing records request [/v2/batch/delete]' => true,
                        default => false,
                    };
                }),
                $this->callback(function (array $context) use ($invocationRule, $apiResponse): bool {
                    switch ($invocationRule->numberOfInvocations()) {
                        case 1: // Generate bearer token
                            $this->assertArrayHasKey('algorithm', $context);
                            $this->assertSame('sha384', $context['algorithm']);

                            $this->assertArrayHasKey('requestString', $context);
                            $this->assertNotEmpty($context['requestString']);

                            $this->assertArrayHasKey('secretKey', $context);
                            $this->assertMatchesRegularExpression(
                                pattern: '/^.{3}\*{7}$/',
                                string: $context['secretKey'],
                            );
                            break;

                        case 2: // Log request
                            $this->assertArrayHasKey('class', $context);
                            $this->assertSame(DeleteService::class, $context['class']);

                            $this->assertArrayHasKey('js_api_key', $context);
                            $this->assertSame('klevu-1234567890', $context['js_api_key']);

                            $this->assertArrayHasKey('headers', $context);
                            $this->assertIsArray($context['headers']);

                            $this->assertArrayHasKey('Host', $context['headers']);
                            $this->assertSame(['indexing.ksearchnet.com'], $context['headers']['Host']);

                            $this->assertArrayHasKey('Content-Type', $context['headers']);
                            $this->assertSame(['application/json'], $context['headers']['Content-Type']);

                            $this->assertArrayHasKey('User-Agent', $context['headers']);
                            $this->assertIsArray($context['headers']['User-Agent']);
                            $this->assertCount(1, $context['headers']['User-Agent']);
                            $this->assertMatchesRegularExpression(
                                pattern: '#^klevu-php-sdk/\d(\.\d)+ \(PHP \d(\.\d)+\)#',
                                string: $context['headers']['User-Agent'][0],
                            );

                            $this->assertSame(
                                expected: ['klevu-1234567890'],
                                actual: $context['headers'][ApiServiceInterface::API_HEADER_KEY_APIKEY],
                            );
                            $this->assertSame(
                                expected: ['HmacSHA384'],
                                actual: $context['headers'][ApiServiceInterface::API_HEADER_KEY_AUTH_ALGO],
                            );

                            $timestampHeader = $context['headers'][ApiServiceInterface::API_HEADER_KEY_TIMESTAMP]
                                ?? null;
                            $this->assertIsArray($timestampHeader);
                            $this->assertCount(1, $timestampHeader);
                            $this->assertMatchesRegularExpression(
                                pattern: '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/',
                                string: $timestampHeader[0],
                            );
                            $timestampUnixtime = strtotime($timestampHeader[0]);
                            $this->assertGreaterThan(
                                expected: time() - 3600,
                                actual: $timestampUnixtime,
                            );
                            $this->assertLessThan(
                                expected: time() + 60,
                                actual: $timestampUnixtime,
                            );

                            $authorizationHeader = $context['headers']['Authorization'];
                            $this->assertIsArray($authorizationHeader);
                            $this->assertCount(1, $authorizationHeader);
                            $this->assertMatchesRegularExpression(
                                pattern: '#^Bearer [-A-Za-z0-9+/]*={0,3}$#',
                                string: $authorizationHeader[0],
                            );
                            break;

                        case 3: // Log response
                            $this->assertArrayHasKey('class', $context);
                            $this->assertSame(DeleteService::class, $context['class']);

                            $this->assertArrayHasKey('js_api_key', $context);
                            $this->assertSame('klevu-1234567890', $context['js_api_key']);

                            $this->assertArrayHasKey('headers', $context);
                            $this->assertIsArray($context['headers']);

                            $this->assertArrayHasKey('body', $context);

                            $this->assertArrayHasKey('status_code', $context);
                            $this->assertSame(200, $context['status_code']);

                            $this->assertSame(
                                expected: $apiResponse,
                                actual: $context['body'],
                            );
                            break;
                    }
                    return true;
                }),
            );

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
            logger: $mockLogger,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );
        $sendResponse = $deleteService->sendByIds(
            accountCredentials: $accountCredentials,
            recordIds: $recordIds,
        );

        $this->assertEquals(
            expected: $expectedResult,
            actual: $sendResponse,
        );
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testSendByIds_Success_RemoveDuplicates(): array
    {
        return [
            [
                ['1', '2', '1', '2 ', '1'],
                ['1', '2'],
                json_encode([
                    'jobId' => '1234567890',
                ]),
                new ApiResponse(
                    responseCode: 200,
                    message: '',
                    status: null,
                    jobId: '1234567890',
                    errors: null,
                ),
            ],
            [
                ['foo', 'foo', 'FOO'],
                ['foo', 'FOO'],
                json_encode([
                    'message' => 'Job submitted successfully.',
                    'jobId' => '1234567890',
                ]),
                new ApiResponse(
                    responseCode: 200,
                    message: 'Job submitted successfully.',
                    status: null,
                    jobId: '1234567890',
                    errors: null,
                ),
            ],
        ];
    }

    /**
     * @param string[] $recordIds
     * @param string[] $expectedRecordIds
     * @param string $apiResponse
     * @param ApiResponse $expectedResult
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testSendByIds_Success_RemoveDuplicates')]
    public function testSendByIds_Success_RemoveDuplicates(
        array $recordIds,
        array $expectedRecordIds,
        string $apiResponse,
        ApiResponse $expectedResult,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $apiResponse,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) use ($expectedRecordIds): bool {
                $requestBody = $request->getBody();
                $requestBodyContents = $requestBody->getContents();
                $requestBody->rewind();

                $this->assertSame(
                    expected: json_encode([
                        'ids' => $expectedRecordIds,
                    ]),
                    actual: $requestBodyContents,
                );

                return true;
            }))
            ->willReturn($mockResponse);

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $sendResponse = $deleteService->sendByIds(
            accountCredentials: $accountCredentials,
            recordIds: $recordIds,
        );

        $this->assertEquals(
            expected: $expectedResult,
            actual: $sendResponse,
        );
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_validRecordIds(): array
    {
        return [
            [
                ['1', '2', '3'],
            ],
            [
                ['foo', 'bar', 'baz'],
            ],
            [
                ['ABC-DEF', '', 'baz'],
            ],
            [
                [' recipe ', 'recipe'],
            ],
        ];
    }

    /**
     * @param string[] $recordIds
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_validRecordIds')]
    public function testSendByIds_InvalidAccountCredentials(
        array $recordIds,
    ): void {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())
            ->method('sendRequest');

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'foo',
            restAuthKey: '',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid account credentials');
        $deleteService->sendByIds(
            accountCredentials: $accountCredentials,
            recordIds: $recordIds,
        );
    }

    /**
     * @param string[] $recordIds
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_validRecordIds')]
    public function testSendByIds_InvalidHeadersForBearerToken(
        array $recordIds,
    ): void {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())
            ->method('sendRequest');

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
            requestBearerTokenProvider: $this->getRequestBearerTokenProviderForFailedAuthAlgorithmValidation(),
            authAlgorithm: AuthAlgorithms::HMAC_SHA384,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Could not generate valid bearer token');
        $deleteService->sendByIds(
            accountCredentials: $accountCredentials,
            recordIds: $recordIds,
        );
    }

    #[Test]
    public function testSendByIds_EmptyRequest(): void
    {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())
            ->method('sendRequest');

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('No valid records found to send');
        try {
            $deleteService->sendByIds(
                accountCredentials: $accountCredentials,
                recordIds: [],
            );
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);
            $this->assertSame(
                expected: 'No records provided for send',
                actual: $errors[0],
            );

            throw $exception;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testSendByIds_BatchSizeExceedsLimit(): array
    {
        $records = [];
        for ($i = 1; $i <= 251; $i++) {
            $records[] = (string)$i;
        }

        return [
            [
                $records,
                null,
            ],
            [
                ['1', '2'],
                1,
            ],
        ];
    }

    /**
     * @param string[] $recordIds
     * @param int|null $maxBatchSize
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testSendByIds_BatchSizeExceedsLimit')]
    public function testSendByIds_BatchSizeExceedsLimit(
        array $recordIds,
        ?int $maxBatchSize,
    ): void {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())
            ->method('sendRequest');

        $deleteService = (null === $maxBatchSize)
            ? new DeleteService(
                httpClient: $mockHttpClient,
            )
            : new DeleteService(
                httpClient: $mockHttpClient,
                maxBatchSize: $maxBatchSize,
            );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Max batch size exceeded');
        $deleteService->sendByIds(
            accountCredentials: $accountCredentials,
            recordIds: $recordIds,
        );
    }

    #[Test]
    public function testSendByIds_BatchSizeExceedsLimit_BeforeFilterUnique(): void
    {
        $recordIds = [];
        for ($i = 1; $i <= 251; $i++) {
            $recordIds[] = (string)($i % 2);
        }
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: (string)json_encode([
                'message' => 'Job submitted successfully.',
                'jobId' => '1234567890',
            ]),
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            // testSend_Success already validates parameters assertions
            ->willReturn($mockResponse);

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
            maxBatchSize: 250,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $deleteService->sendByIds(
            accountCredentials: $accountCredentials,
            recordIds: $recordIds,
        );
    }

    /**
     * @param string[] $recordIds
     *
     * @return void
     */
    #[Test]
    #[TestWith([['', '']])]
    public function testSendByIds_AllRecordsFailValidation_Skip(
        array $recordIds,
    ): void {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())
            ->method('sendRequest');

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
            invalidRecordMode: InvalidRecordMode::SKIP,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('No valid records found to send');
        try {
            $deleteService->sendByIds(
                accountCredentials: $accountCredentials,
                recordIds: $recordIds,
            );
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);
            $this->assertSame(
                expected: 'All records failed validation',
                actual: $errors[0],
            );

            throw $exception;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testSendByIds_AllRecordsFailValidation_Fail(): array
    {
        return [
            [
                ['', ''],
                [
                    'Record #0: id: Record Id is required',
                ],
            ],
            [
                [''],
                [
                    'Record #0: id: Record Id is required',
                ],
            ],
        ];
    }

    /**
     * @param string[] $recordIds
     * @param string[] $expectedErrors
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testSendByIds_AllRecordsFailValidation_Fail')]
    public function testSendByIds_AllRecordsFailValidation_Fail(
        array $recordIds,
        array $expectedErrors,
    ): void {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())
            ->method('sendRequest');

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
            invalidRecordMode: InvalidRecordMode::FAIL,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(
            sprintf(
                '%d records were found invalid',
                count(array_unique($recordIds)),
            ),
        );
        try {
            $deleteService->sendByIds(
                accountCredentials: $accountCredentials,
                recordIds: $recordIds,
            );
        } catch (ValidationException $exception) {
            $this->assertEquals(
                expected: $expectedErrors,
                actual: $exception->getErrors(),
            );

            throw $exception;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testSendByIds_SomeRecordsFailValidation(): array
    {
        return [
            [
                ['', '1', '2', ''],
                json_encode([
                    'jobId' => '1234567890',
                ]),
                1,
                [
                    'Record #0: id: Record Id is required',
                ],
            ],
            [
                ['1', '1', '2', ''],
                json_encode([
                    'message' => ['Success'],
                    'jobId' => '1234567890',
                ]),
                1,
                [
                    'Record #2: id: Record Id is required',
                ],
            ],
        ];
    }

    /**
     * @param string[] $recordIds
     * @param string $apiResponse
     * @param int $expectedFailedCount
     * @param string[] $expectedMessages
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testSendByIds_SomeRecordsFailValidation')]
    public function testSendByIds_SomeRecordsFailValidation_Skip(
        array $recordIds,
        string $apiResponse,
        int $expectedFailedCount,
        array $expectedMessages,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $apiResponse,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            // testSend_Success already validates parameters assertions
            ->willReturn($mockResponse);

        $mockLogger = $this->getMockLogger();
        $uniqueRecordIds = array_unique($recordIds);
        $mockLogger->expects($this->once())
            ->method('warning')
            ->with(
                sprintf('%d records were found invalid and excluded from sync', $expectedFailedCount),
                [
                    'errors' => $expectedMessages,
                    'valid_record_count' => count($uniqueRecordIds) - $expectedFailedCount,
                    'invalid_record_count' => $expectedFailedCount,
                    'class' => DeleteService::class,
                ],
            );

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
            logger: $mockLogger,
            invalidRecordMode: InvalidRecordMode::SKIP,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $result = $deleteService->sendByIds(
            accountCredentials: $accountCredentials,
            recordIds: $recordIds,
        );
        $this->assertSame(200, $result->getResponseCode());
    }

    /**
     * @param string[] $recordIds
     * @param string $apiResponse
     * @param int $expectedFailedCount
     * @param string[] $expectedMessages
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testSendByIds_SomeRecordsFailValidation')]
    public function testSendByIds_SomeRecordsFailValidation_Fail(
        array $recordIds,
        string $apiResponse, // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        int $expectedFailedCount,
        array $expectedMessages,
    ): void {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())
            ->method('sendRequest');

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
            invalidRecordMode: InvalidRecordMode::FAIL,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(
            sprintf('%d records were found invalid', $expectedFailedCount),
        );
        try {
            $deleteService->sendByIds(
                accountCredentials: $accountCredentials,
                recordIds: $recordIds,
            );
        } catch (ValidationException $exception) {
            $this->assertEquals(
                expected: $expectedMessages,
                actual: $exception->getErrors(),
            );

            throw $exception;
        }
    }

    /**
     * @param string[] $recordIds
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_validRecordIds')]
    public function testSendByIds_SendRequest_ThrowsRequestException(
        array $recordIds,
    ): void {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(
                new RequestException('Test Exception', $this->getMockRequest()),
            );

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Test Exception');
        $deleteService->sendByIds(
            accountCredentials: $accountCredentials,
            recordIds: $recordIds,
        );
    }

    /**
     * @param string[] $recordIds
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_validRecordIds')]
    public function testSendByIds_SendRequest_ThrowsNetworkException(
        array $recordIds,
    ): void {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(
                new NetworkException('Test Exception', $this->getMockRequest()),
            );

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadResponseException::class);
        $this->expectExceptionMessage('Test Exception');
        $deleteService->sendByIds(
            accountCredentials: $accountCredentials,
            recordIds: $recordIds,
        );
    }

    #[Test]
    #[TestWith([400])]
    #[TestWith([401])]
    #[TestWith([403])]
    #[TestWith([404])]
    #[TestWith([405])]
    public function testSendByIds_BadRequest(
        int $statusCode,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: $statusCode,
            bodyContents: (string)json_encode([
                'status' => 'BAD_REQUEST',
                'message' => 'Invalid Payload request',
                'errors' => [
                    'save.items[0]: Exception while validating field : id',
                    'save.items[0]: Exception while validating field : type',
                ],
            ]),
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage(
            sprintf(
                'API request rejected by Klevu API [%d] Invalid Payload request',
                $statusCode,
            ),
        );
        try {
            $deleteService->sendByIds(
                accountCredentials: $accountCredentials,
                recordIds: ['1', '2', '3'],
            );
        } catch (BadRequestException $exception) {
            $this->assertSame(
                expected: [
                    'Invalid Payload request',
                    'save.items[0]: Exception while validating field : id',
                    'save.items[0]: Exception while validating field : type',
                ],
                actual: $exception->getErrors(),
            );

            throw $exception;
        }
    }

    #[Test]
    #[TestWith([499])]
    #[TestWith([500])]
    #[TestWith([501])]
    #[TestWith([502])]
    #[TestWith([503])]
    #[TestWith([504])]
    public function testSendByIds_BadResponse(
        int $statusCode,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: $statusCode,
            bodyContents: (string)json_encode([
                'status' => 'BAD_REQUEST',
            ]),
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadResponseException::class);
        $this->expectExceptionMessage(
            sprintf(
                'API request rejected by Klevu API [%d] ',
                $statusCode,
            ),
        );
        try {
            $deleteService->sendByIds(
                accountCredentials: $accountCredentials,
                recordIds: ['1', '2', '3'],
            );
        } catch (BadResponseException $exception) {
            $this->assertSame(
                expected: [],
                actual: $exception->getErrors(),
            );

            throw $exception;
        }
    }

    #[Test]
    #[TestWith(['500 Internal Server Error'])]
    public function testSendByIds_InvalidResponse(
        string $apiResponse,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $apiResponse,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $deleteService = new DeleteService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadResponseException::class);
        $this->expectExceptionMessage('Received invalid JSON response');
        try {
            $deleteService->send(
                accountCredentials: $accountCredentials,
                records: self::getValidRecordFixtures(),
            );
        } catch (BadResponseException $exception) {
            $this->assertSame(
                expected: [
                    'Syntax error',
                ],
                actual: $exception->getErrors(),
            );

            throw $exception;
        }
    }

    /**
     * @return MockObject&BaseUrlsProviderInterface
     */
    private function getMockBaseUrlsProvider(): MockObject&BaseUrlsProviderInterface
    {
        return $this->getMockBuilder(BaseUrlsProviderInterface::class)
            ->getMock();
    }

    /**
     * @return MockObject&ClientInterface
     */
    private function getMockHttpClient(): MockObject&ClientInterface
    {
        return $this->getMockBuilder(ClientInterface::class)
            ->getMock();
    }

    /**
     * @return MockObject&RequestInterface
     */
    private function getMockRequest(): MockObject&RequestInterface
    {
        return $this->getMockBuilder(RequestInterface::class)
            ->getMock();
    }

    /**
     * @param int $statusCode
     * @param string $bodyContents
     *
     * @return MockObject&ResponseInterface
     */
    private function getMockResponse(int $statusCode, string $bodyContents): MockObject&ResponseInterface
    {
        $mockStream = $this->getMockBuilder(StreamInterface::class)
            ->getMock();
        $mockStream->method('getContents')
            ->willReturn($bodyContents);

        $mockResponse = $this->getMockBuilder(ResponseInterface::class)
            ->getMock();

        $mockResponse->method('getStatusCode')
            ->willReturn($statusCode);
        $mockResponse->method('getBody')
            ->willReturn($mockStream);

        return $mockResponse;
    }

    /**
     * @return MockObject&LoggerInterface
     */
    private function getMockLogger(): MockObject&LoggerInterface
    {
        return $this->getMockBuilder(LoggerInterface::class)
            ->getMock();
    }

    /**
     * @return RequestBearerTokenProviderInterface
     */
    private function getRequestBearerTokenProviderForFailedAuthAlgorithmValidation(): RequestBearerTokenProviderInterface // phpcs:ignore Generic.Files.LineLength.TooLong
    {
        $authAlgorithmValidator = new AuthAlgorithmValidator(
            supportedAlgorithms: [],
        );
        $requestHeadersValidator = new RequestHeadersValidator(
            headerValidators: [
                RequestBearerTokenProviderInterface::API_HEADER_KEY_TIMESTAMP => new TimestampValidator(),
                RequestBearerTokenProviderInterface::API_HEADER_KEY_APIKEY => new ApiKeyValidator(),
                RequestBearerTokenProviderInterface::API_HEADER_KEY_AUTH_ALGO => $authAlgorithmValidator,
                RequestBearerTokenProviderInterface::API_HEADER_KEY_CONTENT_TYPE => new ContentTypeValidator(),
            ],
        );

        return new RequestBearerTokenProvider(
            requestHeadersValidator: $requestHeadersValidator,
        );
    }
}
