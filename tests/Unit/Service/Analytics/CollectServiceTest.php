<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Service\Analytics;

use Http\Client\Exception\NetworkException;
use Http\Client\Exception\RequestException;
use Klevu\PhpSDK\Api\Model\ApiResponseInterface;
use Klevu\PhpSDK\Api\Service\Analytics\CollectServiceInterface;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Model\Analytics\Collect\Event;
use Klevu\PhpSDK\Model\Analytics\Collect\EventIterator;
use Klevu\PhpSDK\Model\Analytics\Collect\EventType;
use Klevu\PhpSDK\Model\Analytics\Collect\UserProfile;
use Klevu\PhpSDK\Provider\BaseUrlsProviderInterface;
use Klevu\PhpSDK\Provider\ComposableUserAgentProviderInterface;
use Klevu\PhpSDK\Service\Analytics\CollectService;
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

#[CoversClass(CollectService::class)]
class CollectServiceTest extends TestCase
{
    #[Test]
    public function testImplementsInterface(): void
    {
        $collectService = new CollectService();

        $this->assertInstanceOf(CollectServiceInterface::class, $collectService);
    }

    #[Test]
    public function testGetEndpoint(): void
    {
        $collectService = new CollectService();

        $this->assertSame(
            expected: 'https://stats.ksearchnet.com/analytics/collect',
            actual: $collectService->getEndpoint(),
        );
    }

    #[Test]
    #[TestWith(["stats.klevu.com", "https://stats.klevu.com/analytics/collect"])]
    #[TestWith(["http://stats.klevu.com", "http://stats.klevu.com/analytics/collect"])]
    #[TestWith(["https://stats.klevu.com/", "https://stats.klevu.com/analytics/collect"])]
    #[TestWith(["https://stats.klevu.com/foo", "https://stats.klevu.com/foo/analytics/collect"])]
    #[TestWith(["localhost:8080", "https://localhost:8080/analytics/collect"])]
    public function testGetEndpoint_WithBaseUrlsProvider(
        string $analyticsUrl,
        string $expectedResult,
    ): void {
        $mockBaseUrlsProvider = $this->getMockBaseUrlsProvider();
        $mockBaseUrlsProvider->method('getAnalyticsUrl')
            ->willReturn($analyticsUrl);

        $collectService = new CollectService(
            baseUrlsProvider: $mockBaseUrlsProvider,
        );

        $this->assertSame(
            expected: $expectedResult,
            actual: $collectService->getEndpoint(),
        );
    }

    #[Test]
    #[TestWith([""])]
    #[TestWith(["/"])]
    #[TestWith(["https://"])]
    public function testGetEndpoint_WithBaseUrlsProvider_Invalid(
        string $analyticsUrl,
    ): void {
        $mockBaseUrlsProvider = $this->getMockBaseUrlsProvider();
        $mockBaseUrlsProvider->method('getAnalyticsUrl')
            ->willReturn($analyticsUrl);

        $collectService = new CollectService(
            baseUrlsProvider: $mockBaseUrlsProvider,
        );

        $this->expectException(\LogicException::class);
        $collectService->getEndpoint();
    }

    #[Test]
    public function testGetUserAgentProvider(): void
    {
        $collectService = new CollectService();

        $this->assertInstanceOf(
            expected: ComposableUserAgentProviderInterface::class,
            actual: $collectService->getUserAgentProvider(),
        );
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_validEvents(): array
    {
        return [
            [
                new EventIterator([
                    new Event(
                        event: EventType::ORDER_PURCHASE,
                        apikey: 'klevu-1234567890',
                        version: '1.0.0',
                        data: [
                            'items' => [
                                [
                                    'order_id' => '12345',
                                    'order_line_id' => '67890',
                                    'item_name' => 'Test Product',
                                    'item_id' => '123-456',
                                    'item_group_id' => '123',
                                    'item_variant_id' => '456',
                                    'unit_price' => '3.14',
                                    'currency' => 'GBP',
                                    'units' => 42,
                                ],
                            ],
                        ],
                    ),
                    new Event(
                        event: EventType::ORDER_PURCHASE,
                        apikey: 'klevu-9876543210',
                        version: '1.0.0',
                        data: [
                            'items' => [
                                [
                                    'order_id' => '999',
                                    'order_line_id' => '888',
                                    'item_name' => 'Foo',
                                    'item_id' => '777',
                                    'item_group_id' => '666',
                                    'item_variant_id' => '555',
                                    'unit_price' => '0',
                                    'currency' => 'USD',
                                    'units' => 0,
                                ],
                                [
                                    'item_name' => 'Bar',
                                    'item_id' => '987-654',
                                    'item_group_id' => '987',
                                    'item_variant_id' => '654',
                                    'unit_price' => '1.2345',
                                    'currency' => 'EUR',
                                ],
                            ],
                        ],
                    ),
                    new Event(
                        event: EventType::ORDER_PURCHASE,
                        apikey: 'klevu-9876543210',
                        version: '1.0.0',
                        data: [
                            'items' => [
                                [
                                    'order_id' => '999',
                                    'order_line_id' => '888',
                                    'item_name' => 'Foo',
                                    'item_id' => '777',
                                    'item_group_id' => '666',
                                    'item_variant_id' => '555',
                                    'unit_price' => '0',
                                    'currency' => 'USD',
                                    'units' => 0,
                                ],
                                [
                                    'item_name' => 'Bar',
                                    'item_id' => '987-654',
                                    'item_group_id' => '987',
                                    'item_variant_id' => '654',
                                    'unit_price' => '1.2345',
                                    'currency' => 'EUR',
                                ],
                            ],
                        ],
                        userProfile: new UserProfile(
                            ipAddress: null,
                            email: null,
                        ),
                    ),
                    new Event(
                        event: EventType::ORDER_PURCHASE,
                        apikey: 'klevu-9876543210',
                        version: '1.0.0',
                        data: [
                            'items' => [
                                [
                                    'order_id' => '999',
                                    'order_line_id' => '888',
                                    'item_name' => 'Foo',
                                    'item_id' => '777',
                                    'item_group_id' => '666',
                                    'item_variant_id' => '555',
                                    'unit_price' => '0',
                                    'currency' => 'USD',
                                    'units' => 0,
                                ],
                                [
                                    'item_name' => 'Bar',
                                    'item_id' => '987-654',
                                    'item_group_id' => '987',
                                    'item_variant_id' => '654',
                                    'unit_price' => '1.2345',
                                    'currency' => 'EUR',
                                ],
                            ],
                        ],
                        userProfile: new UserProfile(
                            ipAddress: '127.0.0.1',
                            email: 'contact@klevu.com',
                        ),
                    ),
                ]),
                json_encode([
                    [
                        'event' => 'order_purchase',
                        'event_apikey' => 'klevu-1234567890',
                        'event_version' => '1.0.0',
                        'event_data' => [
                            'items' => [
                                [
                                    'order_id' => '12345',
                                    'order_line_id' => '67890',
                                    'item_name' => 'Test Product',
                                    'item_id' => '123-456',
                                    'item_group_id' => '123',
                                    'item_variant_id' => '456',
                                    'unit_price' => '3.14',
                                    'currency' => 'GBP',
                                    'units' => 42,
                                ],
                            ],
                        ],
                    ],
                    [
                        'event' => 'order_purchase',
                        'event_apikey' => 'klevu-9876543210',
                        'event_version' => '1.0.0',
                        'event_data' => [
                            'items' => [
                                [
                                    'order_id' => '999',
                                    'order_line_id' => '888',
                                    'item_name' => 'Foo',
                                    'item_id' => '777',
                                    'item_group_id' => '666',
                                    'item_variant_id' => '555',
                                    'unit_price' => '0',
                                    'currency' => 'USD',
                                    'units' => 0,
                                ],
                                [
                                    'item_name' => 'Bar',
                                    'item_id' => '987-654',
                                    'item_group_id' => '987',
                                    'item_variant_id' => '654',
                                    'unit_price' => '1.2345',
                                    'currency' => 'EUR',
                                ],
                            ],
                        ],
                    ],
                    [
                        'event' => 'order_purchase',
                        'event_apikey' => 'klevu-9876543210',
                        'event_version' => '1.0.0',
                        'event_data' => [
                            'items' => [
                                [
                                    'order_id' => '999',
                                    'order_line_id' => '888',
                                    'item_name' => 'Foo',
                                    'item_id' => '777',
                                    'item_group_id' => '666',
                                    'item_variant_id' => '555',
                                    'unit_price' => '0',
                                    'currency' => 'USD',
                                    'units' => 0,
                                ],
                                [
                                    'item_name' => 'Bar',
                                    'item_id' => '987-654',
                                    'item_group_id' => '987',
                                    'item_variant_id' => '654',
                                    'unit_price' => '1.2345',
                                    'currency' => 'EUR',
                                ],
                            ],
                        ],
                        'user_profile' => [],
                    ],
                    [
                        'event' => 'order_purchase',
                        'event_apikey' => 'klevu-9876543210',
                        'event_version' => '1.0.0',
                        'event_data' => [
                            'items' => [
                                [
                                    'order_id' => '999',
                                    'order_line_id' => '888',
                                    'item_name' => 'Foo',
                                    'item_id' => '777',
                                    'item_group_id' => '666',
                                    'item_variant_id' => '555',
                                    'unit_price' => '0',
                                    'currency' => 'USD',
                                    'units' => 0,
                                ],
                                [
                                    'item_name' => 'Bar',
                                    'item_id' => '987-654',
                                    'item_group_id' => '987',
                                    'item_variant_id' => '654',
                                    'unit_price' => '1.2345',
                                    'currency' => 'EUR',
                                ],
                            ],
                        ],
                        'user_profile' => [
                            'ip_address' => '127.0.0.1',
                            'email' => 'contact@klevu.com',
                        ],
                    ],
                ]),
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_validEvents')]
    public function testSend_Success(
        EventIterator $events,
        string $expectedPayload,
    ): void {
        $mockResponseContent = '';
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $mockResponseContent,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) use ($expectedPayload): bool {
                $this->assertSame(
                    'https://stats.ksearchnet.com/analytics/collect',
                    (string)$request->getUri(),
                );

                $this->assertSame(['stats.ksearchnet.com'], $request->getHeader('Host'));
                $this->assertSame(['application/json'], $request->getHeader('Content-Type'));

                $userAgentHeaders = $request->getHeader('User-Agent');
                $this->assertIsArray($userAgentHeaders);
                $this->assertCount(1, $userAgentHeaders);
                $this->assertMatchesRegularExpression(
                    pattern: '#^klevu-php-sdk/\d(\.\d)+ \(PHP \d(\.\d)+\)#',
                    string: $userAgentHeaders[0],
                );

                $requestBody = clone $request->getBody();
                $this->assertSame(
                    $expectedPayload,
                    $requestBody->getContents(),
                );

                return true;
            }))
            ->willReturn($mockResponse);

        $collectService = new CollectService(
            httpClient: $mockHttpClient,
        );

        $response = $collectService->send($events);
        $this->assertInstanceOf(ApiResponseInterface::class, $response);
        $this->assertTrue($response->isSuccess());
        $this->assertSame(200, $response->getResponseCode());
    }

    #[Test]
    #[DataProvider('dataProvider_validEvents')]
    public function testSend_Success_WithBaseUrlsProvider(
        EventIterator $events,
        string $expectedPayload, // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    ): void {
        $mockBaseUrlsProvider = $this->getMockBaseUrlsProvider();
        $mockBaseUrlsProvider->method('getAnalyticsUrl')
            ->willReturn('custom-stats.klevu.com');

        $mockResponseContent = '';
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $mockResponseContent,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $this->assertSame(
                    'https://custom-stats.klevu.com/analytics/collect',
                    (string)$request->getUri(),
                );

                $this->assertSame(['custom-stats.klevu.com'], $request->getHeader('Host'));

                // testSend_Success already checks other request assertions
                return true;
            }))
            ->willReturn($mockResponse);

        $collectService = new CollectService(
            baseUrlsProvider: $mockBaseUrlsProvider,
            httpClient: $mockHttpClient,
        );

        $response = $collectService->send($events);
        $this->assertInstanceOf(ApiResponseInterface::class, $response);
        $this->assertTrue($response->isSuccess());
        $this->assertSame(200, $response->getResponseCode());
    }

    #[Test]
    #[DataProvider('dataProvider_validEvents')]
    public function testSend_Success_WithLogger(
        EventIterator $events,
        string $expectedPayload, // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    ): void {
        $mockResponseContent = '';
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $mockResponseContent,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            // testExecute_Success already validates parameters assertions
            ->willReturn($mockResponse);

        $mockLogger = $this->getMockLogger();
        $mockLogger->expects($this->never())->method('critical');
        $mockLogger->expects($this->never())->method('emergency');
        $mockLogger->expects($this->never())->method('alert');
        $mockLogger->expects($this->never())->method('error');
        $mockLogger->expects($this->never())->method('warning');
        $mockLogger->expects($this->never())->method('notice');
        $mockLogger->expects($this->never())->method('info');

        $debugContextCount = 0;
        $mockLogger->expects($this->exactly(2))
            ->method('debug')
            ->with(
                $this->callback(static function (string $message) {
                    return match ($message) {
                        'Request for Klevu analytics collect',
                        'Response from Klevu analytics collect' => true,
                        default => false,
                    };
                }),
                // phpcs:ignore SlevomatCodingStandard.PHP.DisallowReference.DisallowedInheritingVariableByReference
                $this->callback(function (array $context) use ($mockResponseContent, &$debugContextCount): bool {
                    $debugContextCount++;
                    $this->assertArrayHasKey('headers', $context);
                    $this->assertIsArray($context['headers']);

                    $this->assertArrayHasKey('body', $context);

                    switch ($debugContextCount) {
                        case 1: // Log Request
                            $this->assertArrayHasKey('Host', $context['headers']);
                            $this->assertSame(['stats.ksearchnet.com'], $context['headers']['Host']);

                            $this->assertArrayHasKey('Content-Type', $context['headers']);
                            $this->assertSame(['application/json'], $context['headers']['Content-Type']);

                            $this->assertArrayHasKey('User-Agent', $context['headers']);
                            $this->assertIsArray($context['headers']['User-Agent']);
                            $this->assertCount(1, $context['headers']['User-Agent']);
                            $this->assertMatchesRegularExpression(
                                pattern: '#^klevu-php-sdk/\d(\.\d)+ \(PHP \d(\.\d)+\)#',
                                string: $context['headers']['User-Agent'][0],
                            );
                            break;

                        case 2: // Log Response
                            $this->assertArrayHasKey('status_code', $context);
                            $this->assertSame(200, $context['status_code']);

                            // No specific checks on headers for response, beyond being present

                            $this->assertSame($mockResponseContent, $context['body']);
                            break;
                    }

                    return true;
                }),
            );

        $collectService = new CollectService(
            httpClient: $mockHttpClient,
            logger: $mockLogger,
        );

        $response = $collectService->send($events);
        $this->assertInstanceOf(ApiResponseInterface::class, $response);
        $this->assertTrue($response->isSuccess());
        $this->assertSame(200, $response->getResponseCode());
    }

    #[Test]
    #[DataProvider('dataProvider_validEvents')]
    public function testExecute_SendRequest_ThrowsRequestException(
        EventIterator $events,
        string $expectedPayload, // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    ): void {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            // No need to check assertions on request
            ->willThrowException(
                new RequestException('Test Exception', $this->getMockRequest()),
            );

        $collectService = new CollectService(
            httpClient: $mockHttpClient,
        );

        $this->expectException(BadRequestException::class);
        $collectService->send($events);
    }

    #[Test]
    #[DataProvider('dataProvider_validEvents')]
    public function testExecute_SendRequest_ThrowsNetworkException(
        EventIterator $events,
        string $expectedPayload, // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    ): void {
        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            // No need to check assertions on request
            ->willThrowException(
                new NetworkException('Test Exception', $this->getMockRequest()),
            );

        $collectService = new CollectService(
            httpClient: $mockHttpClient,
        );

        $this->expectException(BadResponseException::class);
        $collectService->send($events);
    }

    #[Test]
    #[TestWith([400, ''])]
    #[TestWith([400, '<h1>Bad Request</h1>'])]
    #[TestWith([401, ''])]
    #[TestWith([401, '<h1>Unauthorized</h1>'])]
    #[TestWith([403, ''])]
    #[TestWith([403, '<h1>Forbidden</h1>'])]
    #[TestWith([405, ''])]
    #[TestWith([405, '<h1>Method Not Allowed</h1>'])]
    public function testExecute_BadRequest(
        int $responseCode,
        string $responseBody,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: $responseCode,
            bodyContents: $responseBody,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $collectService = new CollectService(
            httpClient: $mockHttpClient,
        );

        $fixtures = self::dataProvider_validEvents();
        if (!$fixtures) {
            throw new \LogicException('dataProvider_validEvents returned no data');
        }
        [$events] = current($fixtures);
        /** @var EventIterator $events */

        $this->expectException(BadRequestException::class);
        $collectService->send($events);
    }

    #[Test]
    #[TestWith([404, ''])]
    #[TestWith([404, '<h1>Not Found</h1>'])]
    #[TestWith([499, ''])]
    #[TestWith([499, '<h1>Client Closed Request</h1>'])]
    #[TestWith([499, ''])]
    #[TestWith([500, '<h1>Client Closed Request</h1>'])]
    #[TestWith([501, ''])]
    #[TestWith([501, '<h1>Not Implemented</h1>'])]
    #[TestWith([502, ''])]
    #[TestWith([502, '<h1>Bad Gateway</h1>'])]
    #[TestWith([503, ''])]
    #[TestWith([503, '<h1>Service Unavailable</h1>'])]
    #[TestWith([504, ''])]
    #[TestWith([504, '<h1>Gateway Timeout</h1>'])]
    public function testExecute_BadResponse(
        int $responseCode,
        string $responseBody,
    ): void {
        $mockResponse = $this->getMockResponse(
            statusCode: $responseCode,
            bodyContents: $responseBody,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $collectService = new CollectService(
            httpClient: $mockHttpClient,
        );

        $fixtures = self::dataProvider_validEvents();
        if (!$fixtures) {
            throw new \LogicException('dataProvider_validEvents returned no data');
        }
        [$events] = current($fixtures);
        /** @var EventIterator $events */

        $this->expectException(BadResponseException::class);
        $collectService->send($events);
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
}
