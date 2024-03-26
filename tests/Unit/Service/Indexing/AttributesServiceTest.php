<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Service\Indexing;

use Http\Client\Exception\NetworkException;
use Http\Client\Exception\RequestException;
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
use Klevu\PhpSDK\Model\Indexing\DataType;
use Klevu\PhpSDK\Provider\BaseUrlsProviderInterface;
use Klevu\PhpSDK\Provider\ComposableUserAgentProviderInterface;
use Klevu\PhpSDK\Provider\Indexing\IndexingVersions;
use Klevu\PhpSDK\Provider\RequestBearerTokenProvider;
use Klevu\PhpSDK\Provider\RequestBearerTokenProviderInterface;
use Klevu\PhpSDK\Service\ApiServiceInterface;
use Klevu\PhpSDK\Service\Indexing\AttributesService;
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

#[CoversClass(AttributesService::class)]
class AttributesServiceTest extends TestCase
{
    #[Test]
    public function testImplementsInterface(): void
    {
        $attributesService = new AttributesService();

        $this->assertInstanceOf(AttributesServiceInterface::class, $attributesService);
    }

    #[Test]
    public function testGetEndpoint(): void
    {
        $attributesService = new AttributesService();

        $this->assertSame(
            expected: 'https://indexing.ksearchnet.com/v2/attributes',
            actual: $attributesService->getEndpoint(),
        );
    }

    #[Test]
    #[TestWith(["indexing.klevu.com/v2", "https://indexing.klevu.com/v2/attributes"])]
    #[TestWith(["http://indexing.klevu.com/v2", "http://indexing.klevu.com/v2/attributes"])]
    #[TestWith(["https://indexing.klevu.com/v2/", "https://indexing.klevu.com/v2/attributes"])]
    #[TestWith(["https://indexing.klevu.com/foo/v2", "https://indexing.klevu.com/foo/v2/attributes"])]
    #[TestWith(["localhost:8080/v2", "https://localhost:8080/v2/attributes"])]
    public function testGetEndpoint_WithBaseUrlsProvider(
        string $v2IndexingUrl,
        string $expectedResult,
    ): void {
        $mockBaseUrlsProvider = $this->getMockBaseUrlsProvider();
        $mockBaseUrlsProvider->method('getIndexingUrl')
            ->with(IndexingVersions::JSON)
            ->willReturn($v2IndexingUrl);

        $attributesService = new AttributesService(
            baseUrlsProvider: $mockBaseUrlsProvider,
        );

        $this->assertSame(
            expected: $expectedResult,
            actual: $attributesService->getEndpoint(),
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
            ->with(IndexingVersions::JSON)
            ->willReturn($v2IndexingUrl);

        $attributesService = new AttributesService(
            baseUrlsProvider: $mockBaseUrlsProvider,
        );

        $this->expectException(\LogicException::class);
        $attributesService->getEndpoint();
    }

    #[Test]
    public function testGetUserAgentProvider(): void
    {
        $attributesService = new AttributesService();

        $this->assertInstanceOf(
            expected: ComposableUserAgentProviderInterface::class,
            actual: $attributesService->getUserAgentProvider(),
        );
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testGetByName_Success(): array
    {
        $attributeFactory = new AttributeFactory();
        $allAttributesApiResponse = json_encode([
            [
                'attributeName' => 'test_attribute_1',
                'label' => [
                    'default' => 'Test Attribute #1',
                ],
                'searchable' => true,
                'filterable' => true,
                'returnable' => true,
                'datatype' => 'STRING',
            ],
            [
                'attributeName' => 'test_attribute_2',
                'label' => [
                    'default' => 'Test Attribute #2',
                    'en_US' => 'TEST 2',
                ],
                'searchable' => false,
                'filterable' => false,
                'returnable' => false,
                'datatype' => 'NUMBER',
            ],
        ]);

        return [
            [
                'test_attribute',
                $allAttributesApiResponse,
                null,
            ],
            [
                'test_attribute_1',
                $allAttributesApiResponse,
                $attributeFactory->create([
                    'attributeName' => 'test_attribute_1',
                    'datatype' => DataType::STRING->value,
                    'label' => [
                        'default' => 'Test Attribute #1',
                    ],
                    'searchable' => true,
                    'filterable' => true,
                    'returnable' => true,
                ]),
            ],
            [
                'test_attribute_2',
                $allAttributesApiResponse,
                $attributeFactory->create([
                    'attributeName' => 'test_attribute_2',
                    'datatype' => DataType::NUMBER->value,
                    'label' => [
                        'default' => 'Test Attribute #2',
                        'en_US' => 'TEST 2',
                    ],
                    'searchable' => false,
                    'filterable' => false,
                    'returnable' => false,
                ]),
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testGetByName_Success')]
    public function testGetByName_Success(
        string $attributeName,
        string $apiResponse,
        ?AttributeInterface $expectedResult,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $apiResponse,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $this->assertSame('GET', $request->getMethod());
                $this->assertSame(
                    expected: 'https://indexing.ksearchnet.com/v2/attributes',
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

                $this->assertEmpty($requestBodyContents);

                return true;
            }))
            ->willReturn($mockResponse);

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $actualResult = $attributesService->getByName(
            accountCredentials: $accountCredentials,
            attributeName: $attributeName,
        );
        if ($expectedResult) {
            $this->assertEquals(
                expected: $expectedResult,
                actual: $actualResult,
            );
        } else {
            $this->assertNull($actualResult);
        }
    }

    #[Test]
    #[DataProvider('dataProvider_testGetByName_Success')]
    public function testGetByName_Success_WithBaseUrlsProvider(
        string $attributeName,
        string $apiResponse,
        ?AttributeInterface $expectedResult,
    ): void {
        $mockBaseUrlsProvider = $this->getMockBaseUrlsProvider();
        $mockBaseUrlsProvider->method('getIndexingUrl')
            ->with(IndexingVersions::JSON)
            ->willReturn('custom-indexing.klevu.com/v2');

        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $apiResponse,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $this->assertSame(
                    'https://custom-indexing.klevu.com/v2/attributes',
                    (string)$request->getUri(),
                );

                $this->assertSame(['custom-indexing.klevu.com'], $request->getHeader('Host'));

                // testGetByName_Success already checks other request assertions
                return true;
            }))
            ->willReturn($mockResponse);

        $attributesService = new AttributesService(
            baseUrlsProvider: $mockBaseUrlsProvider,
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $actualResult = $attributesService->getByName(
            accountCredentials: $accountCredentials,
            attributeName: $attributeName,
        );
        if ($expectedResult) {
            $this->assertEquals(
                expected: $expectedResult,
                actual: $actualResult,
            );
        } else {
            $this->assertNull($actualResult);
        }
    }

    #[Test]
    #[DataProvider('dataProvider_testGetByName_Success')]
    public function testGetByName_Success_WithLogger(
        string $attributeName,
        string $apiResponse,
        ?AttributeInterface $expectedResult,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $apiResponse,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            // testGetByName_Success already validates parameters assertions
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
                        'Request to get indexing attributes list',
                        'Response from indexing attributes list' => true,
                        default => false,
                    };
                }),
                $this->callback(function (array $context) use ($apiResponse, $invocationRule): bool {
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
                            $this->assertArrayHasKey('js_api_key', $context);
                            $this->assertSame('klevu-1234567890', $context['js_api_key']);

                            $this->assertArrayHasKey('headers', $context);
                            $this->assertIsArray($context['headers']);

                            $this->assertArrayHasKey('body', $context);

                            $this->assertArrayHasKey('status_code', $context);
                            $this->assertSame(200, $context['status_code']);

                            $this->assertSame($apiResponse, $context['body']);
                            break;
                    }
                    return true;
                }),
            );

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
            logger: $mockLogger,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $actualResult = $attributesService->getByName(
            accountCredentials: $accountCredentials,
            attributeName: $attributeName,
        );
        if ($expectedResult) {
            $this->assertEquals(
                expected: $expectedResult,
                actual: $actualResult,
            );
        } else {
            $this->assertNull($actualResult);
        }
    }

    #[Test]
    public function testGetByName_InvalidAccountCredentials(): void
    {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())
            ->method('sendRequest');

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'foo',
            restAuthKey: '',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid account credentials');
        $attributesService->getByName(
            accountCredentials: $accountCredentials,
            attributeName: 'test_attribute',
        );
    }

    #[Test]
    public function testGetByName_InvalidHeadersForBearerToken_AuthAlgorithm(): void
    {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())
            ->method('sendRequest');

        $attributesService = new AttributesService(
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
        $attributesService->getByName(
            accountCredentials: $accountCredentials,
            attributeName: 'test_attribute',
        );
    }

    #[Test]
    public function testGetByName_SendRequest_ThrowsRequestException(): void
    {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(
                new RequestException('Test Exception', $this->getMockRequest()),
            );

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadRequestException::class);
        $attributesService->getByName(
            accountCredentials: $accountCredentials,
            attributeName: 'test_attribute',
        );
    }

    #[Test]
    public function testGetByName_SendRequest_ThrowsNetworkException(): void
    {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(
                new NetworkException('Test Exception', $this->getMockRequest()),
            );

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadResponseException::class);
        $attributesService->getByName(
            accountCredentials: $accountCredentials,
            attributeName: 'test_attribute',
        );
    }

    #[Test]
    #[TestWith([400])]
    #[TestWith([401])]
    #[TestWith([403])]
    #[TestWith([404])]
    #[TestWith([405])]
    public function testGetByName_BadRequest(
        int $statusCode,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: $statusCode,
            bodyContents: (string)json_encode([
                'message' => 'Error',
            ]),
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadRequestException::class);
        $attributesService->getByName(
            accountCredentials: $accountCredentials,
            attributeName: 'test_attribute',
        );
    }

    #[Test]
    #[TestWith([499])]
    #[TestWith([500])]
    #[TestWith([501])]
    #[TestWith([502])]
    #[TestWith([503])]
    #[TestWith([504])]
    public function testGetByName_BadResponse(
        int $statusCode,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: $statusCode,
            bodyContents: (string)json_encode([
                'message' => 'Error',
            ]),
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadResponseException::class);
        $attributesService->getByName(
            accountCredentials: $accountCredentials,
            attributeName: 'test_attribute',
        );
    }

    #[Test]
    #[TestWith(['{"message":"Error"}'])]
    #[TestWith(['500 Internal Server Error'])]
    public function testGetByName_InvalidResponse(
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

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadResponseException::class);
        $attributesService->getByName(
            accountCredentials: $accountCredentials,
            attributeName: 'test_attribute',
        );
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testGet_Success(): array
    {
        $attributeFactory = new AttributeFactory();

        return [
            [
                json_encode([
                    [
                        'attributeName' => 'test_attribute_1',
                        'label' => [
                            'default' => 'Test Attribute #1',
                        ],
                        'searchable' => true,
                        'filterable' => true,
                        'returnable' => true,
                        'datatype' => 'STRING',
                    ],
                    [
                        'attributeName' => 'test_attribute_2',
                        'label' => [
                            'default' => 'Test Attribute #2',
                            'en_US' => 'TEST 2',
                        ],
                        'searchable' => false,
                        'filterable' => false,
                        'returnable' => false,
                        'datatype' => 'NUMBER',
                    ],
                ]),
                new AttributeIterator([
                    $attributeFactory->create([
                        'attributeName' => 'test_attribute_1',
                        'label' => [
                            'default' => 'Test Attribute #1',
                        ],
                        'searchable' => true,
                        'filterable' => true,
                        'returnable' => true,
                        'datatype' => 'STRING',
                    ]),
                    $attributeFactory->create([
                        'attributeName' => 'test_attribute_2',
                        'label' => [
                            'default' => 'Test Attribute #2',
                            'en_US' => 'TEST 2',
                        ],
                        'searchable' => false,
                        'filterable' => false,
                        'returnable' => false,
                        'datatype' => 'NUMBER',
                    ]),
                ]),
            ],
            [
                '[]',
                new AttributeIterator([]),
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testGet_Success')]
    public function testGet_Success(
        string $apiResponse,
        AttributeIterator $expectedResult,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $apiResponse,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $this->assertSame('GET', $request->getMethod());
                $this->assertSame(
                    expected: 'https://indexing.ksearchnet.com/v2/attributes',
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

                $this->assertEmpty($requestBodyContents);

                return true;
            }))
            ->willReturn($mockResponse);

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $actualResult = $attributesService->get(
            accountCredentials: $accountCredentials,
        );

        $this->assertEquals(
            expected: $expectedResult,
            actual: $actualResult,
        );
    }

    #[Test]
    #[DataProvider('dataProvider_testGet_Success')]
    public function testGet_Success_WithBaseUrlsProvider(
        string $apiResponse,
        AttributeIterator $expectedResult,
    ): void {
        $mockBaseUrlsProvider = $this->getMockBaseUrlsProvider();
        $mockBaseUrlsProvider->method('getIndexingUrl')
            ->with(IndexingVersions::JSON)
            ->willReturn('custom-indexing.klevu.com/v2');

        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $apiResponse,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $this->assertSame(
                    'https://custom-indexing.klevu.com/v2/attributes',
                    (string)$request->getUri(),
                );

                $this->assertSame(['custom-indexing.klevu.com'], $request->getHeader('Host'));

                // testGet_Success already checks other request assertions
                return true;
            }))
            ->willReturn($mockResponse);

        $attributesService = new AttributesService(
            baseUrlsProvider: $mockBaseUrlsProvider,
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $actualResult = $attributesService->get(
            accountCredentials: $accountCredentials,
        );

        $this->assertEquals(
            expected: $expectedResult,
            actual: $actualResult,
        );
    }

    #[Test]
    #[DataProvider('dataProvider_testGet_Success')]
    public function testGet_Success_WithLogger(
        string $apiResponse,
        AttributeIterator $expectedResult,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $apiResponse,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            // testGetByName_Success already validates parameters assertions
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
                        'Request to get indexing attributes list',
                        'Response from indexing attributes list' => true,
                        default => false,
                    };
                }),
                $this->callback(function (array $context) use ($apiResponse, $invocationRule): bool {
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
                            $this->assertArrayHasKey('js_api_key', $context);
                            $this->assertSame('klevu-1234567890', $context['js_api_key']);

                            $this->assertArrayHasKey('headers', $context);
                            $this->assertIsArray($context['headers']);

                            $this->assertArrayHasKey('body', $context);

                            $this->assertArrayHasKey('status_code', $context);
                            $this->assertSame(200, $context['status_code']);

                            $this->assertSame($apiResponse, $context['body']);
                            break;
                    }
                    return true;
                }),
            );

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
            logger: $mockLogger,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $actualResult = $attributesService->get(
            accountCredentials: $accountCredentials,
        );

        $this->assertEquals(
            expected: $expectedResult,
            actual: $actualResult,
        );
    }

    #[Test]
    public function testGet_InvalidAccountCredentials(): void
    {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())
            ->method('sendRequest');

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'foo',
            restAuthKey: '',
        );

        $this->expectException(ValidationException::class);
        $attributesService->get(
            accountCredentials: $accountCredentials,
        );
    }

    #[Test]
    public function testGet_InvalidHeadersForBearerToken(): void
    {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())
            ->method('sendRequest');

        $attributesService = new AttributesService(
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
        $attributesService->get(
            accountCredentials: $accountCredentials,
        );
    }

    #[Test]
    public function testGet_SendRequest_ThrowsRequestException(): void
    {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(
                new RequestException('Test Exception', $this->getMockRequest()),
            );

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadRequestException::class);
        $attributesService->get(
            accountCredentials: $accountCredentials,
        );
    }

    #[Test]
    public function testGet_SendRequest_ThrowsNetworkException(): void
    {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(
                new NetworkException('Test Exception', $this->getMockRequest()),
            );

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadResponseException::class);
        $attributesService->get(
            accountCredentials: $accountCredentials,
        );
    }

    #[Test]
    #[TestWith([400])]
    #[TestWith([401])]
    #[TestWith([403])]
    #[TestWith([404])]
    #[TestWith([405])]
    public function testGet_BadRequest(
        int $statusCode,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: $statusCode,
            bodyContents: (string)json_encode([
                'message' => 'Error',
            ]),
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadRequestException::class);
        $attributesService->get(
            accountCredentials: $accountCredentials,
        );
    }

    #[Test]
    #[TestWith([499])]
    #[TestWith([500])]
    #[TestWith([501])]
    #[TestWith([502])]
    #[TestWith([503])]
    #[TestWith([504])]
    public function testGet_BadResponse(
        int $statusCode,
    ): void {

        $mockResponse = $this->getMockResponse(
            statusCode: $statusCode,
            bodyContents: (string)json_encode([
                'message' => 'Error',
            ]),
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadResponseException::class);
        $attributesService->get(
            accountCredentials: $accountCredentials,
        );
    }

    #[Test]
    #[TestWith(['{"message":"Error"}'])]
    #[TestWith(['500 Internal Server Error'])]
    public function testGet_InvalidResponse(
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

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadResponseException::class);
        $attributesService->get(
            accountCredentials: $accountCredentials,
        );
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testPut_Success(): array
    {
        $attributeFactory = new AttributeFactory();

        return [
            [
                $attributeFactory->create([
                    'attributeName' => 'test_attribute',
                    'datatype' => DataType::STRING,
                ]),
                json_encode([
                    'message' => 'Attribute saved successfully',
                ]),
                new ApiResponse(
                    responseCode: 200,
                    message: 'Attribute saved successfully',
                    status: null,
                    jobId: null,
                    errors: null,
                ),
            ],
            [
                $attributeFactory->create([
                    'attributeName' => 'test_attribute',
                    'datatype' => DataType::STRING,
                ]),
                json_encode([
                    'message' => [
                        'Attribute saved successfully',
                    ],
                ]),
                new ApiResponse(
                    responseCode: 200,
                    message: 'Attribute saved successfully',
                    status: null,
                    jobId: null,
                    errors: null,
                ),
            ],
            [
                $attributeFactory->create([
                    'attributeName' => 'test_attribute',
                    'datatype' => DataType::MULTIVALUE,
                    'label' => [
                        'default' => 'Foo',
                        'wom' => 'Bat',
                    ],
                    'returnable' => false,
                    'filterable' => false,
                    'searchable' => false,
                ]),
                json_encode([
                    'message' => 'Attribute saved successfully',
                ]),
                new ApiResponse(
                    responseCode: 200,
                    message: 'Attribute saved successfully',
                    status: null,
                    jobId: null,
                    errors: null,
                ),
            ],
            [
                $attributeFactory->create([
                    'attributeName' => 'test_attribute',
                    'datatype' => DataType::STRING,
                ]),
                json_encode([
                    'status' => 'Internal Error',
                    'errors' => [
                        'Test Error',
                    ],
                ]),
                new ApiResponse(
                    responseCode: 200,
                    message: '',
                    status: 'Internal Error',
                    jobId: null,
                    errors: [
                        'Test Error',
                    ],
                ),
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testPut_Success')]
    public function testPut_Success(
        AttributeInterface $attribute,
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
            ->with($this->callback(function (RequestInterface $request) use ($attribute): bool {
                $this->assertSame('PUT', $request->getMethod());
                $this->assertSame(
                    expected: 'https://indexing.ksearchnet.com/v2/attributes/' . $attribute->getAttributeName(),
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

                $expectedPayload = $attribute->toArray();
                unset($expectedPayload[Attribute::FIELD_IMMUTABLE]);

                $this->assertSame(
                    expected: json_encode($expectedPayload),
                    actual: $requestBodyContents,
                );

                return true;
            }))
            ->willReturn($mockResponse);

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $putResponse = $attributesService->put(
            accountCredentials: $accountCredentials,
            attribute: $attribute,
        );

        $this->assertEquals(
            expected: $expectedResult,
            actual: $putResponse,
        );
    }

    #[Test]
    #[DataProvider('dataProvider_testPut_Success')]
    public function testPut_Success_WithBaseUrlsProvider(
        AttributeInterface $attribute,
    ): void {
        $mockBaseUrlsProvider = $this->getMockBaseUrlsProvider();
        $mockBaseUrlsProvider->method('getIndexingUrl')
            ->with(IndexingVersions::JSON)
            ->willReturn('custom-indexing.klevu.com/v2');

        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: (string)json_encode([
                'message' => 'Attribute saved successfully',
            ]),
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) use ($attribute): bool {
                $this->assertSame(
                    expected: 'https://custom-indexing.klevu.com/v2/attributes/' . $attribute->getAttributeName(),
                    actual: (string)$request->getUri(),
                );

                $this->assertSame(['custom-indexing.klevu.com'], $request->getHeader('Host'));

                // testPut_Success already checks other request assertions
                return true;
            }))
            ->willReturn($mockResponse);

        $attributesService = new AttributesService(
            baseUrlsProvider: $mockBaseUrlsProvider,
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $putResponse = $attributesService->put(
            accountCredentials: $accountCredentials,
            attribute: $attribute,
        );

        $this->assertInstanceOf(ApiResponse::class, $putResponse);
        $this->assertSame(200, $putResponse->responseCode);
        $this->assertSame('Attribute saved successfully', $putResponse->message);
        $this->assertNull($putResponse->jobId);
    }

    #[Test]
    #[DataProvider('dataProvider_testPut_Success')]
    public function testPut_Success_WithLogger(
        AttributeInterface $attribute,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: (string)json_encode([
                'message' => 'Attribute saved successfully',
            ]),
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            // testPut_Success already validates parameters assertions
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
                        'Request to add or update indexing attribute',
                        'Response from put indexing attribute request' => true,
                        default => false,
                    };
                }),
                $this->callback(function (array $context) use ($invocationRule): bool {
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
                            $this->assertArrayHasKey('js_api_key', $context);
                            $this->assertSame('klevu-1234567890', $context['js_api_key']);

                            $this->assertArrayHasKey('headers', $context);
                            $this->assertIsArray($context['headers']);

                            $this->assertArrayHasKey('body', $context);

                            $this->assertArrayHasKey('status_code', $context);
                            $this->assertSame(200, $context['status_code']);

                            $this->assertSame(
                                expected: json_encode([
                                    'message' => 'Attribute saved successfully',
                                ]),
                                actual: $context['body'],
                            );
                            break;
                    }
                    return true;
                }),
            );

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
            logger: $mockLogger,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $putResponse = $attributesService->put(
            accountCredentials: $accountCredentials,
            attribute: $attribute,
        );

        $this->assertInstanceOf(ApiResponse::class, $putResponse);
        $this->assertSame(200, $putResponse->responseCode);
        $this->assertSame('Attribute saved successfully', $putResponse->message);
        $this->assertNull($putResponse->jobId);
    }

    #[Test]
    public function testPut_InvalidAccountCredentials(): void
    {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())
            ->method('sendRequest');

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'foo',
            restAuthKey: '',
        );

        $this->expectException(ValidationException::class);
        $attributesService->put(
            accountCredentials: $accountCredentials,
            attribute: (new AttributeFactory())->create([
                'attributeName' => 'test_attribute',
                'datatype' => 'STRING',
            ]),
        );
    }

    #[Test]
    public function testPut_InvalidHeadersForBearerToken(): void
    {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())
            ->method('sendRequest');

        $attributesService = new AttributesService(
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
        $attributesService->put(
            accountCredentials: $accountCredentials,
            attribute: (new AttributeFactory())->create([
                'attributeName' => 'test_attribute',
                'datatype' => 'STRING',
            ]),
        );
    }

    #[Test]
    public function testPut_SendRequest_ThrowsRequestException(): void
    {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(
                new RequestException('Test Exception', $this->getMockRequest()),
            );

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Test Exception');
        $attributesService->put(
            accountCredentials: $accountCredentials,
            attribute: (new AttributeFactory())->create([
                'attributeName' => 'test_attribute',
                'datatype' => 'STRING',
            ]),
        );
    }

    #[Test]
    public function testPut_SendRequest_ThrowsNetworkException(): void
    {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(
                new NetworkException('Test Exception', $this->getMockRequest()),
            );

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadResponseException::class);
        $this->expectExceptionMessage('Test Exception');
        $attributesService->put(
            accountCredentials: $accountCredentials,
            attribute: (new AttributeFactory())->create([
                'attributeName' => 'test_attribute',
                'datatype' => 'STRING',
            ]),
        );
    }

    /**
     * @param int $statusCode
     * @param string|string[] $apiMessage
     *
     * @return void
     */
    #[Test]
    #[TestWith([400, 'Error'])]
    #[TestWith([401, 'Error'])]
    #[TestWith([403, 'Error'])]
    #[TestWith([404, 'Error'])]
    #[TestWith([405, 'Error'])]
    #[TestWith([400, ['Error']])]
    #[TestWith([401, ['Error']])]
    #[TestWith([403, ['Error']])]
    #[TestWith([404, ['Error']])]
    #[TestWith([405, ['Error']])]
    public function testPut_BadRequest(
        int $statusCode,
        string|array $apiMessage,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: $statusCode,
            bodyContents: (string)json_encode([
                'message' => $apiMessage,
            ]),
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadRequestException::class);
        $attributesService->put(
            accountCredentials: $accountCredentials,
            attribute: (new AttributeFactory())->create([
                'attributeName' => 'test_attribute',
                'datatype' => 'STRING',
            ]),
        );
    }

    /**
     * @param int $statusCode
     * @param string|string[] $apiMessage
     *
     * @return void
     */
    #[Test]
    #[TestWith([499, 'Error'])]
    #[TestWith([500, 'Error'])]
    #[TestWith([501, 'Error'])]
    #[TestWith([502, 'Error'])]
    #[TestWith([503, 'Error'])]
    #[TestWith([504, 'Error'])]
    #[TestWith([499, ['Error']])]
    #[TestWith([500, ['Error']])]
    #[TestWith([501, ['Error']])]
    #[TestWith([502, ['Error']])]
    #[TestWith([503, ['Error']])]
    #[TestWith([504, ['Error']])]
    public function testPut_BadResponse(
        int $statusCode,
        string|array $apiMessage,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: $statusCode,
            bodyContents: (string)json_encode([
                'message' => $apiMessage,
            ]),
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadResponseException::class);
        $attributesService->put(
            accountCredentials: $accountCredentials,
            attribute: (new AttributeFactory())->create([
                'attributeName' => 'test_attribute',
                'datatype' => 'STRING',
            ]),
        );
    }

    #[Test]
    #[TestWith(['500 Internal Server Error'])]
    public function testPut_InvalidResponse(
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

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadResponseException::class);
        $attributesService->put(
            accountCredentials: $accountCredentials,
            attribute: (new AttributeFactory())->create([
                'attributeName' => 'test_attribute',
                'datatype' => 'STRING',
            ]),
        );
    }

    /**
     * @return array<AttributeInterface|string>[]
     */
    public static function dataProvider_testDelete_Success(): array
    {
        $attributeFactory = new AttributeFactory();

        return [
            [
                $attributeFactory->create([
                    'attributeName' => 'test_attribute',
                    'datatype' => DataType::STRING,
                ]),
                (string)json_encode([
                    'message' => 'Attribute deleted successfully',
                ]),
            ],
            [
                $attributeFactory->create([
                    'attributeName' => 'test_attribute',
                    'datatype' => DataType::NUMBER,
                    'label' => [
                        'default' => 'Foo',
                        'wom' => 'Bat',
                    ],
                    'returnable' => false,
                    'filterable' => false,
                    'searchable' => false,
                ]),
                (string)json_encode([
                    'message' => [
                        'Attribute deleted successfully',
                    ],
                ]),
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testDelete_Success')]
    public function testDelete_Success(
        AttributeInterface $attribute,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: (string)json_encode([
                'message' => 'Attribute deleted successfully',
            ]),
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) use ($attribute): bool {
                $this->assertSame('DELETE', $request->getMethod());
                $this->assertSame(
                    expected: 'https://indexing.ksearchnet.com/v2/attributes/' . $attribute->getAttributeName(),
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

                $this->assertEmpty($requestBodyContents);

                return true;
            }))
            ->willReturn($mockResponse);

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $deleteResponse = $attributesService->delete(
            accountCredentials: $accountCredentials,
            attribute: $attribute,
        );

        $this->assertInstanceOf(ApiResponse::class, $deleteResponse);
        $this->assertSame(200, $deleteResponse->responseCode);
        $this->assertSame('Attribute deleted successfully', $deleteResponse->message);
        $this->assertNull($deleteResponse->jobId);
    }

    #[Test]
    #[DataProvider('dataProvider_testDelete_Success')]
    public function testDelete_WithBaseUrlsProvider(
        AttributeInterface $attribute,
    ): void {
        $mockBaseUrlsProvider = $this->getMockBaseUrlsProvider();
        $mockBaseUrlsProvider->method('getIndexingUrl')
            ->with(IndexingVersions::JSON)
            ->willReturn('custom-indexing.klevu.com/v2');

        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: (string)json_encode([
                'message' => 'Attribute deleted successfully',
            ]),
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) use ($attribute): bool {
                $this->assertSame(
                    expected: 'https://custom-indexing.klevu.com/v2/attributes/' . $attribute->getAttributeName(),
                    actual: (string)$request->getUri(),
                );

                $this->assertSame(['custom-indexing.klevu.com'], $request->getHeader('Host'));

                // testDelete_Success already checks other request assertions
                return true;
            }))
            ->willReturn($mockResponse);

        $attributesService = new AttributesService(
            baseUrlsProvider: $mockBaseUrlsProvider,
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $deleteResponse = $attributesService->delete(
            accountCredentials: $accountCredentials,
            attribute: $attribute,
        );

        $this->assertInstanceOf(ApiResponse::class, $deleteResponse);
        $this->assertSame(200, $deleteResponse->responseCode);
        $this->assertSame('Attribute deleted successfully', $deleteResponse->message);
        $this->assertNull($deleteResponse->jobId);
    }

    #[Test]
    #[DataProvider('dataProvider_testDelete_Success')]
    public function testDelete_Success_WithLogger(
        AttributeInterface $attribute,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: (string)json_encode([
                'message' => 'Attribute deleted successfully',
            ]),
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            // testDelete_Success already validates parameters assertions
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
                        'Request to get delete indexing attribute',
                        'Response from delete indexing attributes request' => true,
                        default => false,
                    };
                }),
                $this->callback(function (array $context) use ($invocationRule): bool {
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
                            $this->assertArrayHasKey('js_api_key', $context);
                            $this->assertSame('klevu-1234567890', $context['js_api_key']);

                            $this->assertArrayHasKey('headers', $context);
                            $this->assertIsArray($context['headers']);

                            $this->assertArrayHasKey('body', $context);

                            $this->assertArrayHasKey('status_code', $context);
                            $this->assertSame(200, $context['status_code']);

                            $this->assertSame(
                                expected: json_encode([
                                    'message' => 'Attribute deleted successfully',
                                ]),
                                actual: $context['body'],
                            );
                            break;
                    }
                    return true;
                }),
            );

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
            logger: $mockLogger,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $deleteResponse = $attributesService->delete(
            accountCredentials: $accountCredentials,
            attribute: $attribute,
        );

        $this->assertInstanceOf(ApiResponse::class, $deleteResponse);
        $this->assertSame(200, $deleteResponse->responseCode);
        $this->assertSame('Attribute deleted successfully', $deleteResponse->message);
        $this->assertNull($deleteResponse->jobId);
    }

    #[Test]
    public function testDelete_InvalidAccountCredentials(): void
    {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())
            ->method('sendRequest');

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'foo',
            restAuthKey: '',
        );

        $this->expectException(ValidationException::class);
        $attributesService->delete(
            accountCredentials: $accountCredentials,
            attribute: (new AttributeFactory())->create([
                'attributeName' => 'test_attribute',
                'datatype' => 'STRING',
            ]),
        );
    }

    #[Test]
    public function testDelete_InvalidHeadersForBearerToken(): void
    {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())
            ->method('sendRequest');

        $attributesService = new AttributesService(
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
        $attributesService->delete(
            accountCredentials: $accountCredentials,
            attribute: (new AttributeFactory())->create([
                'attributeName' => 'test_attribute',
                'datatype' => 'STRING',
            ]),
        );
    }

    #[Test]
    public function testDelete_SendRequest_ThrowsRequestException(): void
    {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(
                new RequestException('Test Exception', $this->getMockRequest()),
            );

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadRequestException::class);
        $attributesService->delete(
            accountCredentials: $accountCredentials,
            attribute: (new AttributeFactory())->create([
                'attributeName' => 'test_attribute',
                'datatype' => 'STRING',
            ]),
        );
    }

    #[Test]
    public function testDelete_SendRequest_ThrowsNetworkException(): void
    {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(
                new NetworkException('Test Exception', $this->getMockRequest()),
            );

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadResponseException::class);
        $attributesService->delete(
            accountCredentials: $accountCredentials,
            attribute: (new AttributeFactory())->create([
                'attributeName' => 'test_attribute',
                'datatype' => 'STRING',
            ]),
        );
    }

    #[Test]
    #[TestWith([400])]
    #[TestWith([401])]
    #[TestWith([403])]
    #[TestWith([404])]
    #[TestWith([405])]
    public function testDelete_BadRequest(
        int $statusCode,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: $statusCode,
            bodyContents: (string)json_encode([
                'message' => 'Error',
            ]),
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadRequestException::class);
        $attributesService->delete(
            accountCredentials: $accountCredentials,
            attribute: (new AttributeFactory())->create([
                'attributeName' => 'test_attribute',
                'datatype' => 'STRING',
            ]),
        );
    }

    #[Test]
    #[TestWith([499])]
    #[TestWith([500])]
    #[TestWith([501])]
    #[TestWith([502])]
    #[TestWith([503])]
    #[TestWith([504])]
    public function testDelete_BadResponse(
        int $statusCode,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: $statusCode,
            bodyContents: (string)json_encode([
                'message' => 'Error',
            ]),
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadResponseException::class);
        $attributesService->delete(
            accountCredentials: $accountCredentials,
            attribute: (new AttributeFactory())->create([
                'attributeName' => 'test_attribute',
                'datatype' => 'STRING',
            ]),
        );
    }

    #[Test]
    #[TestWith(['500 Internal Server Error'])]
    public function testDelete_InvalidResponse(
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

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadResponseException::class);
        $attributesService->delete(
            accountCredentials: $accountCredentials,
            attribute: (new AttributeFactory())->create([
                'attributeName' => 'test_attribute',
                'datatype' => 'STRING',
            ]),
        );
    }

    #[Test]
    #[DataProvider('dataProvider_testDelete_Success')]
    public function testDeleteByName_Success(
        AttributeInterface $attribute,
        string $apiResponse,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $apiResponse,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) use ($attribute): bool {
                $this->assertSame('DELETE', $request->getMethod());
                $this->assertSame(
                    expected: 'https://indexing.ksearchnet.com/v2/attributes/' . $attribute->getAttributeName(),
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

                $this->assertEmpty($requestBodyContents);

                return true;
            }))
            ->willReturn($mockResponse);

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $deleteResponse = $attributesService->deleteByName(
            accountCredentials: $accountCredentials,
            attributeName: $attribute->getAttributeName(),
        );

        $this->assertInstanceOf(ApiResponse::class, $deleteResponse);
        $this->assertSame(200, $deleteResponse->responseCode);
        $this->assertSame('Attribute deleted successfully', $deleteResponse->message);
        $this->assertNull($deleteResponse->jobId);
    }

    #[Test]
    #[DataProvider('dataProvider_testDelete_Success')]
    public function testDeleteByName_Success_WithBaseUrlsProvider(
        AttributeInterface $attribute,
    ): void {
        $mockBaseUrlsProvider = $this->getMockBaseUrlsProvider();
        $mockBaseUrlsProvider->method('getIndexingUrl')
            ->with(IndexingVersions::JSON)
            ->willReturn('custom-indexing.klevu.com/v2');

        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: (string)json_encode([
                'message' => 'Attribute deleted successfully',
            ]),
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) use ($attribute): bool {
                $this->assertSame(
                    expected: 'https://custom-indexing.klevu.com/v2/attributes/' . $attribute->getAttributeName(),
                    actual: (string)$request->getUri(),
                );

                $this->assertSame(['custom-indexing.klevu.com'], $request->getHeader('Host'));

                // testDeleteByName_Success already checks other request assertions
                return true;
            }))
            ->willReturn($mockResponse);

        $attributesService = new AttributesService(
            baseUrlsProvider: $mockBaseUrlsProvider,
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $deleteResponse = $attributesService->deleteByName(
            accountCredentials: $accountCredentials,
            attributeName: $attribute->getAttributeName(),
        );

        $this->assertInstanceOf(ApiResponse::class, $deleteResponse);
        $this->assertSame(200, $deleteResponse->responseCode);
        $this->assertSame('Attribute deleted successfully', $deleteResponse->message);
        $this->assertNull($deleteResponse->jobId);
    }

    #[Test]
    #[DataProvider('dataProvider_testDelete_Success')]
    public function testDeleteByName_Success_WithLogger(
        AttributeInterface $attribute,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: (string)json_encode([
                'message' => 'Attribute deleted successfully',
            ]),
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            // testDelete_Success already validates parameters assertions
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
                        'Request to get delete indexing attribute',
                        'Response from delete indexing attributes request' => true,
                        default => false,
                    };
                }),
                $this->callback(function (array $context) use ($invocationRule): bool {
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
                            $this->assertArrayHasKey('js_api_key', $context);
                            $this->assertSame('klevu-1234567890', $context['js_api_key']);

                            $this->assertArrayHasKey('headers', $context);
                            $this->assertIsArray($context['headers']);

                            $this->assertArrayHasKey('body', $context);

                            $this->assertArrayHasKey('status_code', $context);
                            $this->assertSame(200, $context['status_code']);

                            $this->assertSame(
                                expected: json_encode([
                                    'message' => 'Attribute deleted successfully',
                                ]),
                                actual: $context['body'],
                            );
                            break;
                    }
                    return true;
                }),
            );

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
            logger: $mockLogger,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $deleteResponse = $attributesService->deleteByName(
            accountCredentials: $accountCredentials,
            attributeName: $attribute->getAttributeName(),
        );

        $this->assertInstanceOf(ApiResponse::class, $deleteResponse);
        $this->assertSame(200, $deleteResponse->responseCode);
        $this->assertSame('Attribute deleted successfully', $deleteResponse->message);
        $this->assertNull($deleteResponse->jobId);
    }

    #[Test]
    public function testDeleteByName_InvalidAccountCredentials(): void
    {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())
            ->method('sendRequest');

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'foo',
            restAuthKey: '',
        );

        $this->expectException(ValidationException::class);
        $attributesService->deleteByName(
            accountCredentials: $accountCredentials,
            attributeName: 'test_attribute',
        );
    }

    #[Test]
    public function testDeleteByName_InvalidHeadersForBearerToken(): void
    {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())
            ->method('sendRequest');

        $attributesService = new AttributesService(
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
        $attributesService->deleteByName(
            accountCredentials: $accountCredentials,
            attributeName: 'test_attribute',
        );
    }

    #[Test]
    public function testDeleteByName_SendRequest_ThrowsRequestException(): void
    {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(
                new RequestException('Test Exception', $this->getMockRequest()),
            );

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadRequestException::class);
        $attributesService->deleteByName(
            accountCredentials: $accountCredentials,
            attributeName: 'test_attribute',
        );
    }

    #[Test]
    public function testDeleteByName_SendRequest_ThrowsNetworkException(): void
    {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(
                new NetworkException('Test Exception', $this->getMockRequest()),
            );

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadResponseException::class);
        $attributesService->deleteByName(
            accountCredentials: $accountCredentials,
            attributeName: 'test_attribute',
        );
    }

    #[Test]
    #[TestWith([400])]
    #[TestWith([401])]
    #[TestWith([403])]
    #[TestWith([404])]
    #[TestWith([405])]
    public function testDeleteByName_BadRequest(
        int $statusCode,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: $statusCode,
            bodyContents: (string)json_encode([
                'message' => 'Error',
            ]),
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadRequestException::class);
        $attributesService->deleteByName(
            accountCredentials: $accountCredentials,
            attributeName: 'test_attribute',
        );
    }

    #[Test]
    #[TestWith([499])]
    #[TestWith([500])]
    #[TestWith([501])]
    #[TestWith([502])]
    #[TestWith([503])]
    #[TestWith([504])]
    public function testDeleteByName_BadResponse(
        int $statusCode,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: $statusCode,
            bodyContents: (string)json_encode([
                'message' => 'Error',
            ]),
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadResponseException::class);
        $attributesService->deleteByName(
            accountCredentials: $accountCredentials,
            attributeName: 'test_attribute',
        );
    }

    #[Test]
    #[TestWith(['500 Internal Server Error'])]
    public function testDeleteByName_InvalidResponse(
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

        $attributesService = new AttributesService(
            httpClient: $mockHttpClient,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(BadResponseException::class);
        $attributesService->deleteByName(
            accountCredentials: $accountCredentials,
            attributeName: 'test_attribute',
        );
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
