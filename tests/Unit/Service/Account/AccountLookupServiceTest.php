<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

/**
 * phpcs:disable SlevomatCodingStandard.PHP.DisallowReference.DisallowedInheritingVariableByReference
 * @noinspection DuplicatedCode
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Service\Account;

use Http\Client\Exception\NetworkException;
use Http\Client\Exception\RequestException;
use Klevu\PhpSDK\Api\Model\AccountInterface;
use Klevu\PhpSDK\Api\Service\Account\AccountLookupServiceInterface;
use Klevu\PhpSDK\Exception\AccountNotFoundException;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\AccountCredentials;
use Klevu\PhpSDK\Model\Platforms;
use Klevu\PhpSDK\Provider\BaseUrlsProviderInterface;
use Klevu\PhpSDK\Provider\ComposableUserAgentProviderInterface;
use Klevu\PhpSDK\Service\Account\AccountLookupService;
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

#[CoversClass(AccountLookupService::class)]
class AccountLookupServiceTest extends TestCase
{
    #[Test]
    public function testImplementsInterface(): void
    {
        $accountLookupService = new AccountLookupService();

        $this->assertInstanceOf(AccountLookupServiceInterface::class, $accountLookupService);
    }

    #[Test]
    public function testGetEndpoint(): void
    {
        $accountLookupService = new AccountLookupService();

        $this->assertSame(
            expected: 'https://api.ksearchnet.com/user-account/public/platform/account/details',
            actual: $accountLookupService->getEndpoint(),
        );
    }

    #[Test]
    #[TestWith(["api.klevu.com", "https://api.klevu.com/user-account/public/platform/account/details"])]
    #[TestWith(["http://api.klevu.com", "http://api.klevu.com/user-account/public/platform/account/details"])]
    #[TestWith(["https://api.klevu.com/", "https://api.klevu.com/user-account/public/platform/account/details"])]
    #[TestWith(["https://api.klevu.com/foo", "https://api.klevu.com/foo/user-account/public/platform/account/details"])]
    #[TestWith(["localhost:8080", "https://localhost:8080/user-account/public/platform/account/details"])]
    public function testGetEndpoint_WithBaseUrlsProvider(
        string $apiUrl,
        string $expectedResult,
    ): void {
        $mockBaseUrlsProvider = $this->getMockBaseUrlsProvider();
        $mockBaseUrlsProvider->method('getApiUrl')
            ->willReturn($apiUrl);

        $accountLookupService = new AccountLookupService(
            baseUrlsProvider: $mockBaseUrlsProvider,
        );

        $this->assertSame(
            expected: $expectedResult,
            actual: $accountLookupService->getEndpoint(),
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

        $accountLookupService = new AccountLookupService(
            baseUrlsProvider: $mockBaseUrlsProvider,
        );

        $this->expectException(\LogicException::class);
        $accountLookupService->getEndpoint();
    }

    #[Test]
    public function testGetUserAgentProvider(): void
    {
        $accountLookupService = new AccountLookupService();

        $this->assertInstanceOf(
            expected: ComposableUserAgentProviderInterface::class,
            actual: $accountLookupService->getUserAgentProvider(),
        );
    }

    #[Test]
    public function testExecute_Success(): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $mockResponseContent = <<<'JSON'
{
    "platform": "magento",
    "active": true,
    "companyName": "Klevu",
    "email": "contact@klevu.com",
    "indexingUrl": "indexing.ksearchnet.com",
    "searchUrl": "cs.ksearchnet.com",
    "catNavUrl": "cn.ksearchnet.com",
    "analyticsUrl": "stats.ksearchnet.com",
    "jsUrl": "js.klevu.com",
    "tiersUrl": "tiers.klevu.com",
    "indexingVersion": "3",
    "defaultCurrency": "EUR"
}
JSON;
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $mockResponseContent,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) use ($accountCredentials): bool {
                $this->assertSame(
                    'https://api.ksearchnet.com/user-account/public/platform/account/details',
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
                $this->assertEmpty($requestBody->getContents());

                return true;
            }))
            ->willReturn($mockResponse);

        $accountLookupService = new AccountLookupService(
            httpClient: $mockHttpClient,
        );

        $account = $accountLookupService->execute($accountCredentials);
        $this->assertInstanceOf(AccountInterface::class, $account);
        $this->assertSame($accountCredentials->jsApiKey, $account->getJsApiKey());
        $this->assertSame($accountCredentials->restAuthKey, $account->getRestAuthKey());
        $this->assertSame(Platforms::MAGENTO->value, $account->getPlatform());
        $this->assertTrue($account->isActive());
        $this->assertSame('Klevu', $account->getCompanyName());
        $this->assertSame('contact@klevu.com', $account->getEmail());
        $this->assertSame('indexing.ksearchnet.com', $account->getIndexingUrl());
        $this->assertSame('cs.ksearchnet.com', $account->getSearchUrl());
        $this->assertSame('cn.ksearchnet.com', $account->getSmartCategoryMerchandisingUrl());
        $this->assertSame('stats.ksearchnet.com', $account->getAnalyticsUrl());
        $this->assertSame('js.klevu.com', $account->getJsUrl());
        $this->assertSame('tiers.klevu.com', $account->getTiersUrl());
        $this->assertSame('3', $account->getIndexingVersion());
        $this->assertSame('EUR', $account->getDefaultCurrency());
        $this->assertFalse($account->getAccountFeatures()->smartCategoryMerchandising);
        $this->assertFalse($account->getAccountFeatures()->smartRecommendations);
    }

    #[Test]
    #[Depends('testExecute_Success')]
    public function testExecute_Success_WithBaseUrlsProvider(): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $mockBaseUrlsProvider = $this->getMockBaseUrlsProvider();
        $mockBaseUrlsProvider->method('getApiUrl')
            ->willReturn('custom-api.ksearchnet.com');

        $mockResponseContent = <<<'JSON'
{
    "platform": "magento",
    "active": true,
    "companyName": "Klevu",
    "email": "contact@klevu.com",
    "indexingUrl": "indexing.ksearchnet.com",
    "searchUrl": "cs.ksearchnet.com",
    "catNavUrl": "cn.ksearchnet.com",
    "analyticsUrl": "stats.ksearchnet.com",
    "jsUrl": "js.klevu.com",
    "tiersUrl": "tiers.klevu.com",
    "indexingVersion": "3",
    "defaultCurrency": "EUR"
}
JSON;
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $mockResponseContent,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $this->assertSame(
                    'https://custom-api.ksearchnet.com/user-account/public/platform/account/details',
                    (string)$request->getUri(),
                );

                $this->assertSame(['custom-api.ksearchnet.com'], $request->getHeader('Host'));

                // testExecute_Success already checks other request assertions
                return true;
            }))
            ->willReturn($mockResponse);

        $accountLookupService = new AccountLookupService(
            baseUrlsProvider: $mockBaseUrlsProvider,
            httpClient: $mockHttpClient,
        );

        $account = $accountLookupService->execute($accountCredentials);
        $this->assertInstanceOf(AccountInterface::class, $account);
        // testExecute_Success already checks return assertions
    }

    #[Test]
    #[Depends('testExecute_Success')]
    public function testExecute_Success_WithLogger(): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $mockResponseContent = <<<'JSON'
{
    "platform": "magento",
    "active": true,
    "companyName": "Klevu",
    "email": "contact@klevu.com",
    "indexingUrl": "indexing.ksearchnet.com",
    "searchUrl": "cs.ksearchnet.com",
    "catNavUrl": "cn.ksearchnet.com",
    "analyticsUrl": "stats.ksearchnet.com",
    "jsUrl": "js.klevu.com",
    "tiersUrl": "tiers.klevu.com",
    "indexingVersion": "3",
    "defaultCurrency": "EUR"
}
JSON;
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
                        'Request for Klevu account lookup',
                        'Response from Klevu account lookup' => true,
                        default => false,
                    };
                }),
                $this->callback(function (array $context) use ($accountCredentials, $mockResponseContent, &$debugContextCount): bool { // phpcs:ignore Generic.Files.LineLength.TooLong
                    $debugContextCount++;

                    $this->assertArrayHasKey('js_api_key', $context);
                    $this->assertSame($accountCredentials->jsApiKey, $context['js_api_key']);

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

        $accountLookupService = new AccountLookupService(
            httpClient: $mockHttpClient,
            logger: $mockLogger,
        );

        $account = $accountLookupService->execute($accountCredentials);
        $this->assertInstanceOf(AccountInterface::class, $account);
        // testExecute_Success already checks return assertions
    }

    #[Test]
    public function testExecute_AccountCredentialsValidationFailed(): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'invalidKey',
            restAuthKey: '**invalidKey**',
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())->method('sendRequest');

        $accountLookupService = new AccountLookupService(
            httpClient: $mockHttpClient,
        );

        $this->expectException(ValidationException::class);
        try {
            $accountLookupService->execute($accountCredentials);
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

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->never())->method('sendRequest');

        $mockAccountCredentialsValidator = $this->getMockAccountCredentialsValidator();
        $mockAccountCredentialsValidator->expects($this->atLeastOnce())
            ->method('execute')
            ->willThrowException(new InvalidDataValidationException([
                'Invalid JS API Key',
                'Invalid REST AUTH Key',
            ]));

        $accountLookupService = new AccountLookupService(
            httpClient: $mockHttpClient,
            accountCredentialsValidator: $mockAccountCredentialsValidator,
        );

        $this->expectException(ValidationException::class);
        try {
            $accountLookupService->execute($accountCredentials);
        } catch (ValidationException $e) {
            $this->assertCount(2, $e->getErrors());
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

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            // No need to check assertions on request
            ->willThrowException(
                new RequestException('Test Exception', $this->getMockRequest()),
            );

        $accountLookupService = new AccountLookupService(
            httpClient: $mockHttpClient,
        );

        $this->expectException(BadRequestException::class);
        $accountLookupService->execute($accountCredentials);
    }

    #[Test]
    public function testExecute_SendRequest_ThrowsNetworkException(): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            // No need to check assertions on request
            ->willThrowException(
                new NetworkException('Test Exception', $this->getMockRequest()),
            );

        $accountLookupService = new AccountLookupService(
            httpClient: $mockHttpClient,
        );

        $this->expectException(BadResponseException::class);
        $accountLookupService->execute($accountCredentials);
    }

    #[Test]
    #[TestWith([200, '{"error": "Invalid request"}'])]
    #[TestWith([400, '{"error": "Bad Request"}'])]
    #[TestWith([403, '{"error": "Forbidden"}'])]
    #[TestWith([405, '{"error": "Method Not Allowed"}'])]
    public function testExecute_BadRequest(int $responseCode, string $responseBody): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $mockResponse = $this->getMockResponse(
            statusCode: $responseCode,
            bodyContents: $responseBody,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            // No need to check assertions on request
            ->willReturn($mockResponse);

        $accountLookupService = new AccountLookupService(
            httpClient: $mockHttpClient,
        );

        $this->expectException(BadRequestException::class);
        $accountLookupService->execute($accountCredentials);
    }

    #[Test]
    #[TestWith([200, ''])]
    #[TestWith([200, '{"broken": "json"'])]
    #[TestWith([200, '"string return"'])]
    #[TestWith([401, ''])]
    #[TestWith([401, '<h1>Not Authorized</h1>'])]
    #[TestWith([404, '{}'])]
    #[TestWith([500, '{}'])]
    #[TestWith([503, '{}'])]
    public function testExecute_BadResponse(int $responseCode, string $responseBody): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $mockResponse = $this->getMockResponse(
            statusCode: $responseCode,
            bodyContents: $responseBody,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            // No need to check assertions on request
            ->willReturn($mockResponse);

        $accountLookupService = new AccountLookupService(
            httpClient: $mockHttpClient,
        );

        $this->expectException(BadResponseException::class);
        $accountLookupService->execute($accountCredentials);
    }

    #[Test]
    #[TestWith([401, '{"status": 401, "error": "Unauthorized", "message": "invalid-credentials"}'])]
    public function testExecute_AccountNotFound(int $responseCode, string $responseBody): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $mockResponse = $this->getMockResponse(
            statusCode: $responseCode,
            bodyContents: $responseBody,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            // No need to check assertions on request
            ->willReturn($mockResponse);

        $accountLookupService = new AccountLookupService(
            httpClient: $mockHttpClient,
        );

        $this->expectException(AccountNotFoundException::class);
        $accountLookupService->execute($accountCredentials);
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
}
