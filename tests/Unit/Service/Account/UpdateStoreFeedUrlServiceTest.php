<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

/**
 * @noinspection DuplicatedCode
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Service\Account;

use Http\Client\Exception\NetworkException;
use Http\Client\Exception\RequestException;
use Klevu\PhpSDK\Api\Model\ApiResponseInterface;
use Klevu\PhpSDK\Api\Service\Account\UpdateStoreFeedUrlServiceInterface;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\AccountCredentials;
use Klevu\PhpSDK\Provider\BaseUrlsProviderInterface;
use Klevu\PhpSDK\Provider\ComposableUserAgentProviderInterface;
use Klevu\PhpSDK\Service\Account\UpdateStoreFeedUrlService;
use Klevu\PhpSDK\Validator\ValidatorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

#[CoversClass(UpdateStoreFeedUrlService::class)]
class UpdateStoreFeedUrlServiceTest extends TestCase
{
    #[Test]
    public function testImplementsInterface(): void
    {
        $updateStoreFeedUrlService = new UpdateStoreFeedUrlService();

        $this->assertInstanceOf(UpdateStoreFeedUrlServiceInterface::class, $updateStoreFeedUrlService);
    }

    #[Test]
    public function testGetEndpoint(): void
    {
        $updateStoreFeedUrlService = new UpdateStoreFeedUrlService();

        $this->assertSame(
            expected: 'https://api.ksearchnet.com/user-account/public/platform/account/details/indexingUrl',
            actual: $updateStoreFeedUrlService->getEndpoint(),
        );
    }

    #[Test]
    #[TestWith(["api.klevu.com", "https://api.klevu.com/user-account/public/platform/account/details/indexingUrl"])] // phpcs:ignore Generic.Files.LineLength.TooLong
    #[TestWith(["http://api.klevu.com", "http://api.klevu.com/user-account/public/platform/account/details/indexingUrl"])] // phpcs:ignore Generic.Files.LineLength.TooLong
    #[TestWith(["https://api.klevu.com/", "https://api.klevu.com/user-account/public/platform/account/details/indexingUrl"])] // phpcs:ignore Generic.Files.LineLength.TooLong
    #[TestWith(["https://api.klevu.com/foo", "https://api.klevu.com/foo/user-account/public/platform/account/details/indexingUrl"])] // phpcs:ignore Generic.Files.LineLength.TooLong
    #[TestWith(["localhost:8080", "https://localhost:8080/user-account/public/platform/account/details/indexingUrl"])] // phpcs:ignore Generic.Files.LineLength.TooLong
    public function testGetEndpoint_WithBaseUrlsProvider(
        string $apiUrl,
        string $expectedResult,
    ): void {
        $mockBaseUrlsProvider = $this->getMockBaseUrlsProvider();
        $mockBaseUrlsProvider->method('getApiUrl')
            ->willReturn($apiUrl);

        $updateStoreFeedUrlService = new UpdateStoreFeedUrlService(
            baseUrlsProvider: $mockBaseUrlsProvider,
        );

        $this->assertSame(
            expected: $expectedResult,
            actual: $updateStoreFeedUrlService->getEndpoint(),
        );
    }

    #[Test]
    #[TestWith([""])]
    #[TestWith(["/"])]
    #[TestWith(["https://"])]
    public function testGetEndpoint_WithBaseUrlsProvider_Invalid(
        string $apiUrl,
    ): void {
        $mockBaseUrlsProvider = $this->getMockBaseUrlsProvider();
        $mockBaseUrlsProvider->method('getApiUrl')
            ->willReturn($apiUrl);

        $updateStoreFeedUrlService = new UpdateStoreFeedUrlService(
            baseUrlsProvider: $mockBaseUrlsProvider,
        );

        $this->expectException(\LogicException::class);
        $updateStoreFeedUrlService->getEndpoint();
    }

    #[Test]
    public function testGetUserAgentProvider(): void
    {
        $updateStoreFeedUrlService = new UpdateStoreFeedUrlService();

        $this->assertInstanceOf(
            expected: ComposableUserAgentProviderInterface::class,
            actual: $updateStoreFeedUrlService->getUserAgentProvider(),
        );
    }

    #[Test]
    #[TestWith(['https://www.klevu.com/my-feed.xml', 'shopify', 'klevu.com'])]
    #[TestWith(['https://www.klevu.com/my-feed.xml', 'bigcommerce', 'BigCommerce Store'])]
    public function testExecute_Success(string $indexingUrl, string $storeType, string $storeUrl): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $mockResponseContent = sprintf(
            'Feed for store %s added for monitoring',
            $accountCredentials->jsApiKey,
        );
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $mockResponseContent,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) use ($accountCredentials, $indexingUrl, $storeType, $storeUrl): bool { // phpcs:ignore Generic.Files.LineLength.TooLong
                $this->assertSame(
                    'https://api.ksearchnet.com/user-account/public/platform/account/details/indexingUrl',
                    (string)$request->getUri(),
                );

                $this->assertSame(['api.ksearchnet.com'], $request->getHeader('Host'));
                $this->assertSame(['application/json'], $request->getHeader('Content-Type'));

                $userAgentHeaders = $request->getHeader('User-Agent');
                $this->assertIsArray($userAgentHeaders);
                $this->assertCount(1, $userAgentHeaders);
                $this->assertMatchesRegularExpression(
                    pattern: '#^klevu-php-sdk/\d(\.\d)+ \(PHP \d(\.\d)+\)#',
                    string: $userAgentHeaders[0],
                );

                $this->assertSame([$accountCredentials->jsApiKey], $request->getHeader('X-KLEVU-JSAPIKEY'));
                $this->assertSame([$accountCredentials->restAuthKey], $request->getHeader('X-KLEVU-RESTAPIKEY'));

                $requestBody = clone $request->getBody();
                $this->assertSame(
                    json_encode([
                        'indexingUrl' => $indexingUrl,
                        'storeType' => $storeType,
                        'storeUrl' => $storeUrl,
                    ]),
                    json_encode(json_decode($requestBody->getContents())), // Re-encode to ensure whitespace
                );

                return true;
            }))
            ->willReturn($mockResponse);

        $updateStoreFeedUrlService = new UpdateStoreFeedUrlService(
            httpClient: $mockHttpClient,
        );

        $apiResponse = $updateStoreFeedUrlService->execute(
            accountCredentials: $accountCredentials,
            indexingUrl: $indexingUrl,
            storeType: $storeType,
            storeUrl: $storeUrl,
        );
        $this->assertInstanceOf(ApiResponseInterface::class, $apiResponse);
        $this->assertTrue($apiResponse->isSuccess());
        $this->assertSame(200, $apiResponse->getResponseCode());
    }

    #[Test]
    #[Depends('testExecute_Success')]
    public function testExecute_Success_WithBaseUrlsProvider(): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );
        $indexingUrl = 'https://www.klevu.com/my-feed.xml';
        $storeType = 'shopify';
        $storeUrl = 'klevu.com';

        $mockBaseUrlsProvider = $this->getMockBaseUrlsProvider();
        $mockBaseUrlsProvider->method('getApiUrl')
            ->willReturn('custom-api.ksearchnet.com');

        $mockResponseContent = sprintf(
            'Feed for store %s added for monitoring',
            $accountCredentials->jsApiKey,
        );
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $mockResponseContent,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $this->assertSame(
                    'https://custom-api.ksearchnet.com/user-account/public/platform/account/details/indexingUrl',
                    (string)$request->getUri(),
                );

                $this->assertSame(['custom-api.ksearchnet.com'], $request->getHeader('Host'));

                // testExecute_Success already checks other request assertions
                return true;
            }))
            ->willReturn($mockResponse);

        $updateStoreFeedUrlService = new UpdateStoreFeedUrlService(
            baseUrlsProvider: $mockBaseUrlsProvider,
            httpClient: $mockHttpClient,
        );

        $apiResponse = $updateStoreFeedUrlService->execute(
            accountCredentials: $accountCredentials,
            indexingUrl: $indexingUrl,
            storeType: $storeType,
            storeUrl: $storeUrl,
        );
        $this->assertInstanceOf(ApiResponseInterface::class, $apiResponse);
        $this->assertTrue($apiResponse->isSuccess());
        $this->assertSame(200, $apiResponse->getResponseCode());
    }

    #[Test]
    #[Depends('testExecute_Success')]
    public function testExecute_Success_WithLogger(): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );
        $indexingUrl = 'https://www.klevu.com/my-feed.xml';
        $storeType = 'shopify';
        $storeUrl = 'klevu.com';

        $mockResponseContent = sprintf(
            'Feed for store %s added for monitoring',
            $accountCredentials->jsApiKey,
        );
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $mockResponseContent,
        );
        $mockResponse->method('getHeaders')
            ->willReturn([]);

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
                        'Request to update store feed URL',
                        'Response from update store feed URL request' => true,
                        default => false,
                    };
                }),
                $this->callback(function (array $context) use (
                    $accountCredentials,
                    $indexingUrl,
                    $storeType,
                    $storeUrl,
                    $mockResponseContent,
                    // phpcs:ignore SlevomatCodingStandard.PHP.DisallowReference.DisallowedInheritingVariableByReference
                    &$debugContextCount,
                ): bool {
                    $debugContextCount++;

                    $this->assertArrayHasKey('js_api_key', $context);
                    $this->assertSame($accountCredentials->jsApiKey, $context['js_api_key']);

                    $this->assertArrayHasKey('indexing_url', $context);
                    $this->assertSame($indexingUrl, $context['indexing_url']);

                    $this->assertArrayHasKey('store_type', $context);
                    $this->assertSame($storeType, $context['store_type']);

                    $this->assertArrayHasKey('store_url', $context);
                    $this->assertSame($storeUrl, $context['store_url']);

                    $this->assertArrayHasKey('headers', $context);
                    $this->assertIsArray($context['headers']);

                    switch ($debugContextCount) {
                        case 1: // Log Request
                            $this->assertArrayHasKey('Host', $context['headers']);
                            $this->assertSame(['api.ksearchnet.com'], $context['headers']['Host']);

                            $this->assertArrayHasKey('Content-Type', $context['headers']);
                            $this->assertSame(['application/json'], $context['headers']['Content-Type']);

                            $this->assertArrayHasKey('User-Agent', $context['headers']);
                            $this->assertIsArray($context['headers']['User-Agent']);
                            $this->assertCount(1, $context['headers']['User-Agent']);
                            $this->assertMatchesRegularExpression(
                                pattern: '#^klevu-php-sdk/\d(\.\d)+ \(PHP \d(\.\d)+\)#',
                                string: $context['headers']['User-Agent'][0],
                            );

                            $this->assertArrayHasKey('X-KLEVU-RESTAPIKEY', $context['headers']);
                            $this->assertSame(['********'], $context['headers']['X-KLEVU-RESTAPIKEY']);
                            break;

                        case 2: // Log Response
                            $this->assertArrayHasKey('status_code', $context);
                            $this->assertSame(200, $context['status_code']);

                            // No specific checks on headers for response, beyond being present

                            $this->assertArrayHasKey('body', $context);
                            $this->assertSame($mockResponseContent, $context['body']);
                            break;
                    }

                    return true;
                }),
            );

        $updateStoreFeedUrlService = new UpdateStoreFeedUrlService(
            httpClient: $mockHttpClient,
            logger: $mockLogger,
        );

        $apiResponse = $updateStoreFeedUrlService->execute(
            accountCredentials: $accountCredentials,
            indexingUrl: $indexingUrl,
            storeType: $storeType,
            storeUrl: $storeUrl,
        );
        $this->assertInstanceOf(ApiResponseInterface::class, $apiResponse);
        $this->assertTrue($apiResponse->isSuccess());
        $this->assertSame(200, $apiResponse->getResponseCode());
    }

    #[Test]
    public function testExecute_AccountCredentialsValidationFailed(): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'invalidKey',
            restAuthKey: '**invalidKey**',
        );
        $indexingUrl = 'https://www.klevu.com/my-feed.xml';
        $storeType = 'shopify';
        $storeUrl = 'klevu.com';

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())->method('sendRequest');

        $updateStoreFeedUrlService = new UpdateStoreFeedUrlService(
            httpClient: $mockHttpClient,
        );

        $this->expectException(InvalidDataValidationException::class);
        try {
            $updateStoreFeedUrlService->execute(
                accountCredentials: $accountCredentials,
                indexingUrl: $indexingUrl,
                storeType: $storeType,
                storeUrl: $storeUrl,
            );
        } catch (ValidationException $e) {
            $this->assertCount(2, $e->getErrors());
            throw $e;
        }
    }

    #[Test]
    #[Depends('testExecute_AccountCredentialsValidationFailed')]
    public function testExecute_AccountCredentialsValidationFailed_CustomValidator(): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'invalidKey',
            restAuthKey: '**invalidKey**',
        );
        $indexingUrl = 'https://www.klevu.com/my-feed.xml';
        $storeType = 'shopify';
        $storeUrl = 'klevu.com';

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())->method('sendRequest');

        $mockAccountCredentialsValidator = $this->getMockAccountCredentialsValidator();
        $mockAccountCredentialsValidator->expects($this->atLeastOnce())
            ->method('execute')
            ->willThrowException(new InvalidDataValidationException([
                'Invalid JS API Key',
                'Invalid REST AUTH Key',
            ]));

        $updateStoreFeedUrlService = new UpdateStoreFeedUrlService(
            httpClient: $mockHttpClient,
            accountCredentialsValidator: $mockAccountCredentialsValidator,
        );

        $this->expectException(ValidationException::class);
        try {
            $updateStoreFeedUrlService->execute(
                accountCredentials: $accountCredentials,
                indexingUrl: $indexingUrl,
                storeType: $storeType,
                storeUrl: $storeUrl,
            );
        } catch (ValidationException $e) {
            $this->assertCount(2, $e->getErrors());
            throw $e;
        }
    }

    #[Test]
    #[Depends('testExecute_Success')]
    #[TestWith([''])]
    #[TestWith([' '])]
    #[TestWith(['not-a-url'])]
    public function testExecute_PayloadValidationFailed_InvalidIndexingUrl(string $indexingUrl): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );
        $storeType = 'shopify';
        $storeUrl = 'klevu.com';

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())->method('sendRequest');

        $updateStoreFeedUrlService = new UpdateStoreFeedUrlService(
            httpClient: $mockHttpClient,
        );

        $this->expectException(ValidationException::class);
        try {
            $updateStoreFeedUrlService->execute(
                accountCredentials: $accountCredentials,
                indexingUrl: $indexingUrl,
                storeType: $storeType,
                storeUrl: $storeUrl,
            );
        } catch (ValidationException $e) {
            $this->assertCount(1, $e->getErrors());
            throw $e;
        }
    }

    #[Test]
    #[Depends('testExecute_Success')]
    #[TestWith([''])]
    #[TestWith([' '])]
    #[TestWith(['magento'])]
    #[TestWith(['custom'])]
    #[TestWith(['unknown'])]
    public function testExecute_PayloadValidationFailed_InvalidStoreType(string $storeType): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );
        $indexingUrl = 'https://www.klevu.com/my-feed.xml';
        $storeUrl = 'klevu.com';

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())->method('sendRequest');

        $updateStoreFeedUrlService = new UpdateStoreFeedUrlService(
            httpClient: $mockHttpClient,
        );

        $this->expectException(ValidationException::class);
        try {
            $updateStoreFeedUrlService->execute(
                accountCredentials: $accountCredentials,
                indexingUrl: $indexingUrl,
                storeType: $storeType,
                storeUrl: $storeUrl,
            );
        } catch (ValidationException $e) {
            $this->assertCount(1, $e->getErrors());
            throw $e;
        }
    }

    #[Test]
    #[Depends('testExecute_Success')]
    #[TestWith([''])]
    #[TestWith([' '])]
    public function testExecute_PayloadValidationFailed_InvalidStoreUrl(string $storeUrl): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );
        $indexingUrl = 'https://www.klevu.com/my-feed.xml';
        $storeType = 'shopify';

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())->method('sendRequest');

        $updateStoreFeedUrlService = new UpdateStoreFeedUrlService(
            httpClient: $mockHttpClient,
        );

        $this->expectException(ValidationException::class);
        try {
            $updateStoreFeedUrlService->execute(
                accountCredentials: $accountCredentials,
                indexingUrl: $indexingUrl,
                storeType: $storeType,
                storeUrl: $storeUrl,
            );
        } catch (ValidationException $e) {
            $this->assertCount(1, $e->getErrors());
            throw $e;
        }
    }

    #[Test]
    public function testExecute_PayloadValidationFailed_CustomValidator(): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );
        $indexingUrl = 'https://www.klevu.com/my-feed.xml';
        $storeType = 'shopify';
        $storeUrl = 'Klevu';

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())->method('sendRequest');

        $mockUpdateStoreFeedUrlPayloadValidator = $this->getMockUpdateStoreFeedUrlPayloadValidator();
        $mockUpdateStoreFeedUrlPayloadValidator->expects($this->atLeastOnce())
            ->method('execute')
            ->willThrowException(new InvalidDataValidationException([
                'Invalid Indexing URL',
                'Invalid Store Type',
                'Invalid Store URL',
            ]));
        $updateStoreFeedUrlService = new UpdateStoreFeedUrlService(
            httpClient: $mockHttpClient,
            updateStoreFeedUrlPayloadValidator: $mockUpdateStoreFeedUrlPayloadValidator,
        );

        $this->expectException(ValidationException::class);
        try {
            $updateStoreFeedUrlService->execute(
                accountCredentials: $accountCredentials,
                indexingUrl: $indexingUrl,
                storeType: $storeType,
                storeUrl: $storeUrl,
            );
        } catch (ValidationException $e) {
            $this->assertCount(3, $e->getErrors());
            throw $e;
        }
    }

    #[Test]
    public function testExecute_SendRequest_ThrowsRequestException(): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );
        $indexingUrl = 'https://www.klevu.com/my-feed.xml';
        $storeType = 'shopify';
        $storeUrl = 'klevu.com';

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            // No need to check assertions on request
            ->willThrowException(
                new RequestException('Test Exception', $this->getMockRequest()),
            );

        $updateStoreFeedUrlService = new UpdateStoreFeedUrlService(
            httpClient: $mockHttpClient,
        );

        $this->expectException(BadRequestException::class);
        $updateStoreFeedUrlService->execute(
            accountCredentials: $accountCredentials,
            indexingUrl: $indexingUrl,
            storeType: $storeType,
            storeUrl: $storeUrl,
        );
    }

    #[Test]
    public function testExecute_SendRequest_ThrowsNetworkException(): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );
        $indexingUrl = 'https://www.klevu.com/my-feed.xml';
        $storeType = 'shopify';
        $storeUrl = 'klevu.com';

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            // No need to check assertions on request
            ->willThrowException(
                new NetworkException('Test Exception', $this->getMockRequest()),
            );

        $updateStoreFeedUrlService = new UpdateStoreFeedUrlService(
            httpClient: $mockHttpClient,
        );

        $this->expectException(BadResponseException::class);
        $updateStoreFeedUrlService->execute(
            accountCredentials: $accountCredentials,
            indexingUrl: $indexingUrl,
            storeType: $storeType,
            storeUrl: $storeUrl,
        );
    }

    #[Test]
    #[TestWith([200, 'Invalid Feed URL:'])]
    #[TestWith([200, 'Invalid storeType :null'])]
    #[TestWith([200, 'Invalid store name:'])]
    #[TestWith([400, '<h1>Bad Request</h1>'])]
    #[TestWith([401, '<h1>Unauthorized</h1>'])]
    #[TestWith([403, '<h1>Forbidden</h1>'])]
    #[TestWith([405, '<h1>Method Not Allowed</h1>'])]
    public function testExecute_BadRequest(int $responseCode, string $responseBody): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );
        $indexingUrl = 'https://www.klevu.com/my-feed.xml';
        $storeType = 'shopify';
        $storeUrl = 'klevu.com';

        $mockResponse = $this->getMockResponse(
            statusCode: $responseCode,
            bodyContents: $responseBody,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            // No need to check assertions on request
            ->willReturn($mockResponse);

        $updateStoreFeedUrlService = new UpdateStoreFeedUrlService(
            httpClient: $mockHttpClient,
        );

        $this->expectException(BadRequestException::class);
        $updateStoreFeedUrlService->execute(
            accountCredentials: $accountCredentials,
            indexingUrl: $indexingUrl,
            storeType: $storeType,
            storeUrl: $storeUrl,
        );
    }

    #[Test]
    #[TestWith([200, ''])]
    #[TestWith([404, '<h1>Not Found</h1>'])]
    #[TestWith([500, '<h1>Internal Server Error</h1>'])]
    #[TestWith([503, '<h1>Service Unavailable</h1>'])]
    public function testExecute_BadResponse(int $responseCode, string $responseBody): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );
        $indexingUrl = 'https://www.klevu.com/my-feed.xml';
        $storeType = 'shopify';
        $storeUrl = 'klevu.com';

        $mockResponse = $this->getMockResponse(
            statusCode: $responseCode,
            bodyContents: $responseBody,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            // No need to check assertions on request
            ->willReturn($mockResponse);

        $updateStoreFeedUrlService = new UpdateStoreFeedUrlService(
            httpClient: $mockHttpClient,
        );

        $this->expectException(BadResponseException::class);
        $updateStoreFeedUrlService->execute(
            accountCredentials: $accountCredentials,
            indexingUrl: $indexingUrl,
            storeType: $storeType,
            storeUrl: $storeUrl,
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
     * @return MockObject&ValidatorInterface
     */
    private function getMockAccountCredentialsValidator(): MockObject&ValidatorInterface
    {
        return $this->getMockBuilder(ValidatorInterface::class)
            ->getMock();
    }

    /**
     * @return MockObject&ValidatorInterface
     */
    private function getMockUpdateStoreFeedUrlPayloadValidator(): MockObject&ValidatorInterface
    {
        return $this->getMockBuilder(ValidatorInterface::class)
            ->getMock();
    }
}
