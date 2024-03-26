<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

/**
 * phpcs:disable SlevomatCodingStandard.PHP.DisallowReference.DisallowedInheritingVariableByReference
 *
 * @noinspection DuplicatedCode
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Service\Account;

use Http\Client\Exception\NetworkException;
use Http\Client\Exception\RequestException;
use Klevu\PhpSDK\Api\Service\Account\AccountFeaturesServiceInterface;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\Account\AccountFeatures;
use Klevu\PhpSDK\Model\Account\AccountFeaturesOptions;
use Klevu\PhpSDK\Model\AccountCredentials;
use Klevu\PhpSDK\Provider\BaseUrlsProviderInterface;
use Klevu\PhpSDK\Provider\ComposableUserAgentProviderInterface;
use Klevu\PhpSDK\Service\Account\AccountFeaturesService;
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

#[CoversClass(AccountFeaturesService::class)]
class AccountFeaturesServiceTest extends TestCase
{
    #[Test]
    public function testImplementsInterface(): void
    {
        $accountFeaturesService = new AccountFeaturesService();

        $this->assertInstanceOf(AccountFeaturesServiceInterface::class, $accountFeaturesService);
    }

    #[Test]
    public function testGetEndpoint(): void
    {
        $accountFeaturesService = new AccountFeaturesService();

        $this->assertSame(
            expected: 'https://tiers.klevu.com/uti/getFeatureValues',
            actual: $accountFeaturesService->getEndpoint(),
        );
    }

    #[Test]
    #[TestWith(["custom-tiers.ksearchnet.com", "https://custom-tiers.ksearchnet.com/uti/getFeatureValues"])]
    #[TestWith(["http://custom-tiers.ksearchnet.com", "http://custom-tiers.ksearchnet.com/uti/getFeatureValues"])]
    #[TestWith(["https://custom-tiers.ksearchnet.com/", "https://custom-tiers.ksearchnet.com/uti/getFeatureValues"])]
    #[TestWith(["https://custom-tiers.ksearchnet.com/foo", "https://custom-tiers.ksearchnet.com/foo/uti/getFeatureValues"])] // phpcs:ignore Generic.Files.LineLength.TooLong
    #[TestWith(["localhost:8080", "https://localhost:8080/uti/getFeatureValues"])]
    public function testGetEndpoint_WithBaseUrlsProvider(
        string $tiersUrl,
        string $expectedResult,
    ): void {
        $mockBaseUrlsProvider = $this->getMockBaseUrlsProvider();
        $mockBaseUrlsProvider->method('getTiersUrl')
            ->willReturn($tiersUrl);

        $accountFeaturesService = new AccountFeaturesService(
            baseUrlsProvider: $mockBaseUrlsProvider,
        );

        $this->assertSame(
            expected: $expectedResult,
            actual: $accountFeaturesService->getEndpoint(),
        );
    }

    #[Test]
    #[TestWith([""])]
    #[TestWith(["/"])]
    #[TestWith(["https://"])]
    public function testGetEndpoint_WithBaseUrlsProvider_Invalid(
        string $tiersUrl,
    ): void {
        $mockBaseUrlsProvider = $this->getMockBaseUrlsProvider();
        $mockBaseUrlsProvider->method('getTiersUrl')
            ->willReturn($tiersUrl);

        $accountFeaturesService = new AccountFeaturesService(
            baseUrlsProvider: $mockBaseUrlsProvider,
        );

        $this->expectException(\LogicException::class);
        $accountFeaturesService->getEndpoint();
    }

    #[Test]
    public function testGetUserAgentProvider(): void
    {
        $accountFeaturesService = new AccountFeaturesService();

        $this->assertInstanceOf(
            expected: ComposableUserAgentProviderInterface::class,
            actual: $accountFeaturesService->getUserAgentProvider(),
        );
    }

    #[Test]
    public function testExecute_Success(): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $mockResponseContent = <<<'XML'
<data>
    <feature>
        <key>s.enablecategorynavigation</key>
        <value>yes</value>
    </feature>
    <feature>
        <key>allow.personalizedrecommendations</key>
        <value>yes</value>
    </feature>
    <feature>
        <key>s.preservedlayout</key>
        <value>yes</value>
    </feature>
</data>
XML;
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $mockResponseContent,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) use ($accountCredentials): bool {
                $this->assertSame(
                    'https://tiers.klevu.com/uti/getFeatureValues',
                    (string)$request->getUri(),
                );

                $this->assertSame(['tiers.klevu.com'], $request->getHeader('Host'));
                $this->assertSame(['application/x-www-form-urlencoded'], $request->getHeader('Content-Type'));

                $userAgentHeaders = $request->getHeader('User-Agent');
                $this->assertIsArray($userAgentHeaders);
                $this->assertCount(1, $userAgentHeaders);
                $this->assertMatchesRegularExpression(
                    pattern: '#^klevu-php-sdk/\d(\.\d)+ \(PHP \d(\.\d)+\)#',
                    string: $userAgentHeaders[0],
                );

                $this->assertSame([$accountCredentials->restAuthKey], $request->getHeader('restApiKey'));

                $requestBody = clone $request->getBody();
                $this->assertSame(
                    http_build_query([
                        'restApiKey' => $accountCredentials->restAuthKey,
                        'features' => implode(',', [
                            AccountFeaturesOptions::smartCategoryMerchandising->value,
                            AccountFeaturesOptions::smartRecommendations->value,
                            AccountFeaturesOptions::preserveLayout->value,
                        ]),
                    ]),
                    $requestBody->getContents(),
                );

                return true;
            }))
            ->willReturn($mockResponse);

        $accountFeaturesService = new AccountFeaturesService(
            httpClient: $mockHttpClient,
        );

        $accountFeatures = $accountFeaturesService->execute($accountCredentials);
        $this->assertInstanceOf(AccountFeatures::class, $accountFeatures);
        $this->assertTrue($accountFeatures->smartCategoryMerchandising, 'Smart Category Merchandising');
        $this->assertTrue($accountFeatures->smartRecommendations, 'Smart Recommendations');
        $this->assertTrue($accountFeatures->preserveLayout, 'Preserve Layout');
    }

    #[Test]
    public function testExecute_Success_WithBaseUrlsProvider(): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $mockBaseUrlsProvider = $this->getMockBaseUrlsProvider();
        $mockBaseUrlsProvider->method('getTiersUrl')
            ->willReturn('custom-tiers.klevu.com');

        $mockResponseContent = <<<'XML'
<data>
    <feature>
        <key>s.enablecategorynavigation</key>
        <value>yes</value>
    </feature>
    <feature>
        <key>allow.personalizedrecommendations</key>
        <value>yes</value>
    </feature>
    <feature>
        <key>s.preservedlayout</key>
        <value>yes</value>
    </feature>
</data>
XML;
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $mockResponseContent,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $this->assertSame(
                    'https://custom-tiers.klevu.com/uti/getFeatureValues',
                    (string)$request->getUri(),
                );

                $this->assertSame(['custom-tiers.klevu.com'], $request->getHeader('Host'));

                // testExecute_Success already checks other request assertions
                return true;
            }))
            ->willReturn($mockResponse);

        $accountFeaturesService = new AccountFeaturesService(
            baseUrlsProvider: $mockBaseUrlsProvider,
            httpClient: $mockHttpClient,
        );

        $accountFeatures = $accountFeaturesService->execute($accountCredentials);
        $this->assertInstanceOf(AccountFeatures::class, $accountFeatures);
        // testExecute_Success already checks return assertions
    }

    #[Test]
    public function testExecute_Success_WithLogger(): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $mockResponseContent = <<<'XML'
<data>
    <feature>
        <key>s.enablecategorynavigation</key>
        <value>yes</value>
    </feature>
    <feature>
        <key>allow.personalizedrecommendations</key>
        <value>yes</value>
    </feature>
    <feature>
        <key>s.preservedlayout</key>
        <value>yes</value>
    </feature>
</data>
XML;
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
                        'Request for Klevu account features check',
                        'Response from Klevu account features check' => true,
                        default => false,
                    };
                }),
                $this->callback(function (array $context) use ($accountCredentials, $mockResponseContent, &$debugContextCount): bool { // phpcs:ignore Generic.Files.LineLength.TooLong
                    $debugContextCount++;

                    $this->assertArrayHasKey('js_api_key', $context);
                    $this->assertSame($accountCredentials->jsApiKey, $context['js_api_key']);

                    $this->assertArrayHasKey('features', $context);
                    $this->assertSame(
                        [
                            AccountFeaturesOptions::smartCategoryMerchandising->value,
                            AccountFeaturesOptions::smartRecommendations->value,
                            AccountFeaturesOptions::preserveLayout->value,
                        ],
                        $context['features'],
                    );

                    $this->assertArrayHasKey('headers', $context);
                    $this->assertIsArray($context['headers']);

                    switch ($debugContextCount) {
                        case 1: // Log Request
                            $this->assertArrayHasKey('Host', $context['headers']);
                            $this->assertSame(['tiers.klevu.com'], $context['headers']['Host']);

                            $this->assertArrayHasKey('Content-Type', $context['headers']);
                            $this->assertSame(
                                ['application/x-www-form-urlencoded'],
                                $context['headers']['Content-Type'],
                            );

                            $this->assertArrayHasKey('User-Agent', $context['headers']);
                            $this->assertIsArray($context['headers']['User-Agent']);
                            $this->assertCount(1, $context['headers']['User-Agent']);
                            $this->assertMatchesRegularExpression(
                                pattern: '#^klevu-php-sdk/\d(\.\d)+ \(PHP \d(\.\d)+\)#',
                                string: $context['headers']['User-Agent'][0],
                            );

                            $this->assertArrayHasKey('restApiKey', $context['headers']);
                            $this->assertSame(['********'], $context['headers']['restApiKey']);
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

        $accountFeaturesService = new AccountFeaturesService(
            httpClient: $mockHttpClient,
            logger: $mockLogger,
        );

        $accountFeatures = $accountFeaturesService->execute($accountCredentials);
        $this->assertInstanceOf(AccountFeatures::class, $accountFeatures);
        // testExecute_Success already checks return assertions
    }

    #[Test]
    #[Depends('testExecute_Success')]
    public function testExecute_Success_WithSpecifiedFeatures(): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $mockResponseContent = <<<'XML'
<data>
    <feature>
        <key>allow.personalizedrecommendations</key>
        <value>yes</value>
    </feature>
</data>
XML;
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $mockResponseContent,
        );

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) use ($accountCredentials): bool {
                $this->assertSame(['tiers.klevu.com'], $request->getHeader('Host'));
                $this->assertSame(['application/x-www-form-urlencoded'], $request->getHeader('Content-Type'));

                $userAgentHeaders = $request->getHeader('User-Agent');
                $this->assertIsArray($userAgentHeaders);
                $this->assertCount(1, $userAgentHeaders);
                $this->assertMatchesRegularExpression(
                    pattern: '#^klevu-php-sdk/\d(\.\d)+ \(PHP \d(\.\d)+\)#',
                    string: $userAgentHeaders[0],
                );

                $this->assertSame([$accountCredentials->restAuthKey], $request->getHeader('restApiKey'));

                $requestBody = clone $request->getBody();
                $this->assertSame(
                    http_build_query([
                        'restApiKey' => $accountCredentials->restAuthKey,
                        'features' => implode(',', [
                            AccountFeaturesOptions::smartRecommendations->value,
                        ]),
                    ]),
                    $requestBody->getContents(),
                );

                return true;
            }))
            ->willReturn($mockResponse);

        $accountFeaturesService = new AccountFeaturesService(
            httpClient: $mockHttpClient,
        );

        $accountFeatures = $accountFeaturesService->execute(
            accountCredentials: $accountCredentials,
            features: [AccountFeaturesOptions::smartRecommendations->value],
        );
        $this->assertInstanceOf(AccountFeatures::class, $accountFeatures);
        $this->assertFalse($accountFeatures->smartCategoryMerchandising, 'Smart Category Merchandising');
        $this->assertTrue($accountFeatures->smartRecommendations, 'Smart Recommendations');
        $this->assertFalse($accountFeatures->preserveLayout, 'Preserve Layout');
    }

    #[Test]
    public function testExecute_Success_UnexpectedFeatureFlag(): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $mockResponseContent = <<<'XML'
<data>
    <feature>
        <key>foo</key>
        <value>yes</value>
    </feature>
    <feature>
        <key>s.enablecategorynavigation</key>
        <value>yes</value>
    </feature>
    <feature>
        <key>allow.personalizedrecommendations</key>
        <value>yes</value>
    </feature>
    <feature>
        <key>s.preservedlayout</key>
        <value>yes</value>
    </feature>
</data>
XML;
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
        $mockLogger->expects($this->once())
            ->method('warning')
            ->with('Unrecognised account feature encountered: "{feature}"'); // Parsing occurs later
        $mockLogger->expects($this->never())->method('notice');
        $mockLogger->expects($this->never())->method('info');

        $debugContextCount = 0;
        $mockLogger->expects($this->exactly(2))
            ->method('debug')
            ->with(
                $this->callback(static function (string $message) {
                    return match ($message) {
                        'Request for Klevu account features check',
                        'Response from Klevu account features check' => true,
                        default => false,
                    };
                }),
                $this->callback(function (array $context) use ($accountCredentials, $mockResponseContent, &$debugContextCount): bool { // phpcs:ignore Generic.Files.LineLength.TooLong
                    $debugContextCount++;

                    $this->assertArrayHasKey('js_api_key', $context);
                    $this->assertSame($accountCredentials->jsApiKey, $context['js_api_key']);

                    $this->assertArrayHasKey('features', $context);
                    $this->assertSame(
                        [
                            AccountFeaturesOptions::smartCategoryMerchandising->value,
                            AccountFeaturesOptions::smartRecommendations->value,
                            AccountFeaturesOptions::preserveLayout->value,
                        ],
                        $context['features'],
                    );

                    $this->assertArrayHasKey('headers', $context);
                    $this->assertIsArray($context['headers']);

                    switch ($debugContextCount) {
                        case 1: // Log Request
                            $this->assertArrayHasKey('Host', $context['headers']);
                            $this->assertSame(['tiers.klevu.com'], $context['headers']['Host']);

                            $this->assertArrayHasKey('Content-Type', $context['headers']);
                            $this->assertSame(
                                ['application/x-www-form-urlencoded'],
                                $context['headers']['Content-Type'],
                            );

                            $this->assertArrayHasKey('User-Agent', $context['headers']);
                            $this->assertIsArray($context['headers']['User-Agent']);
                            $this->assertCount(1, $context['headers']['User-Agent']);
                            $this->assertMatchesRegularExpression(
                                pattern: '#^klevu-php-sdk/\d(\.\d)+ \(PHP \d(\.\d)+\)#',
                                string: $context['headers']['User-Agent'][0],
                            );

                            $this->assertArrayHasKey('restApiKey', $context['headers']);
                            $this->assertSame(['********'], $context['headers']['restApiKey']);
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

        $accountFeaturesService = new AccountFeaturesService(
            httpClient: $mockHttpClient,
            logger: $mockLogger,
        );

        $accountFeatures = $accountFeaturesService->execute($accountCredentials);
        $this->assertInstanceOf(AccountFeatures::class, $accountFeatures);
        // testExecute_Success already checks return assertions
    }

    #[Test]
    public function testExecute_Success_MissingFeatureFlag(): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $mockResponseContent = <<<'XML'
<data>
    <feature>
        <key>allow.personalizedrecommendations</key>
        <value>yes</value>
    </feature>
    <feature>
        <key>s.preservedlayout</key>
        <value>yes</value>
    </feature>
</data>
XML;
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
        $mockLogger->expects($this->once())
            ->method('warning')
            ->with('Some requested feature flags missing from response: {missing_features}'); // Parsing occurs later
        $mockLogger->expects($this->never())->method('notice');
        $mockLogger->expects($this->never())->method('info');

        $debugContextCount = 0;
        $mockLogger->expects($this->exactly(2))
            ->method('debug')
            ->with(
                $this->callback(static function (string $message) {
                    return match ($message) {
                        'Request for Klevu account features check',
                        'Response from Klevu account features check' => true,
                        default => false,
                    };
                }),
                $this->callback(function (array $context) use ($accountCredentials, $mockResponseContent, &$debugContextCount): bool { // phpcs:ignore Generic.Files.LineLength.TooLong
                    $debugContextCount++;

                    $this->assertArrayHasKey('js_api_key', $context);
                    $this->assertSame($accountCredentials->jsApiKey, $context['js_api_key']);

                    $this->assertArrayHasKey('features', $context);
                    $this->assertSame(
                        [
                            AccountFeaturesOptions::smartCategoryMerchandising->value,
                            AccountFeaturesOptions::smartRecommendations->value,
                            AccountFeaturesOptions::preserveLayout->value,
                        ],
                        $context['features'],
                    );

                    $this->assertArrayHasKey('headers', $context);
                    $this->assertIsArray($context['headers']);

                    switch ($debugContextCount) {
                        case 1: // Log Request
                            $this->assertArrayHasKey('Host', $context['headers']);
                            $this->assertSame(['tiers.klevu.com'], $context['headers']['Host']);

                            $this->assertArrayHasKey('Content-Type', $context['headers']);
                            $this->assertSame(
                                ['application/x-www-form-urlencoded'],
                                $context['headers']['Content-Type'],
                            );

                            $this->assertArrayHasKey('User-Agent', $context['headers']);
                            $this->assertIsArray($context['headers']['User-Agent']);
                            $this->assertCount(1, $context['headers']['User-Agent']);
                            $this->assertMatchesRegularExpression(
                                pattern: '#^klevu-php-sdk/\d(\.\d)+ \(PHP \d(\.\d)+\)#',
                                string: $context['headers']['User-Agent'][0],
                            );

                            $this->assertArrayHasKey('restApiKey', $context['headers']);
                            $this->assertSame(['********'], $context['headers']['restApiKey']);
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

        $accountFeaturesService = new AccountFeaturesService(
            httpClient: $mockHttpClient,
            logger: $mockLogger,
        );

        $accountFeatures = $accountFeaturesService->execute(
            accountCredentials: $accountCredentials,
            features: [
                's.enablecategorynavigation',
                'allow.personalizedrecommendations',
                's.preservedlayout',
            ],
        );
        $this->assertInstanceOf(AccountFeatures::class, $accountFeatures);
        $this->assertFalse($accountFeatures->smartCategoryMerchandising, 'Smart Category Merchandising');
        $this->assertTrue($accountFeatures->smartRecommendations, 'Smart Recommendations');
        $this->assertTrue($accountFeatures->preserveLayout, 'Preserve Layout');
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

        $accountFeaturesService = new AccountFeaturesService(
            httpClient: $mockHttpClient,
        );

        $this->expectException(InvalidDataValidationException::class);
        try {
            $accountFeaturesService->execute($accountCredentials);
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

        $accountFeaturesService = new AccountFeaturesService(
            httpClient: $mockHttpClient,
            accountCredentialsValidator: $mockAccountCredentialsValidator,
        );

        $this->expectException(InvalidDataValidationException::class);
        try {
            $accountFeaturesService->execute($accountCredentials);
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

        $accountFeaturesService = new AccountFeaturesService(
            httpClient: $mockHttpClient,
        );

        $this->expectException(BadRequestException::class);
        $accountFeaturesService->execute(
            accountCredentials: $accountCredentials,
            features: [AccountFeaturesOptions::smartRecommendations->value],
        );
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

        $accountFeaturesService = new AccountFeaturesService(
            httpClient: $mockHttpClient,
        );

        $this->expectException(BadResponseException::class);
        $accountFeaturesService->execute(
            accountCredentials: $accountCredentials,
            features: [AccountFeaturesOptions::smartRecommendations->value],
        );
    }

    #[Test]
    #[TestWith([200, '<data><error>Invalid Parameters</error></data>'])]
    #[TestWith([400, '<h1>Bad Request</h1>'])]
    #[TestWith([400, '<data><error>Bad Request</error></data>'])]
    #[TestWith([401, '<h1>Unauthorized</h1>'])]
    #[TestWith([401, '<data><error>Unauthorized</error></data>'])]
    #[TestWith([403, '<h1>Forbidden</h1>'])]
    #[TestWith([403, '<data><error>Forbidden</error></data>'])]
    #[TestWith([405, '<h1>Method Not Allowed</h1>'])]
    #[TestWith([405, '<data><error>Method Not Allowed</error></data>'])]
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

        $accountFeaturesService = new AccountFeaturesService(
            httpClient: $mockHttpClient,
        );

        $this->expectException(BadRequestException::class);
        $accountFeaturesService->execute($accountCredentials);
    }

    #[Test]
    #[TestWith([200, ''])]
    #[TestWith([200, '{"feature": ["s.enablecategorynavigation": "yes"]}'])]
    #[TestWith([200, '<data><feature>s.enablecategorynavigation</feature>'])]
    #[TestWith([200, '<html><head><title>404: Page Not Found</title></head><body><h1>Page Not Found</h1></body></html>'])] // phpcs:ignore Generic.Files.LineLength.TooLong
    #[TestWith([404, '<h1>Not Found</h1>'])]
    #[TestWith([404, '<data><error>Not Found</error></data>'])]
    #[TestWith([404, '<data><feature>s.enablecategorynavigation</feature></data>'])]
    #[TestWith([499, '<h1>Client Closed Request</h1>'])]
    #[TestWith([499, '<data><error>Client Closed Request</error></data>'])]
    #[TestWith([499, '<data><feature>s.enablecategorynavigation</feature></data>'])]
    #[TestWith([500, '<h1>Internal Server Error</h1>'])]
    #[TestWith([500, '<data><error>Internal Server Error</error></data>'])]
    #[TestWith([500, '<data><feature>s.enablecategorynavigation</feature></data>'])]
    #[TestWith([501, '<h1>Not Implemented</h1>'])]
    #[TestWith([501, '<data><error>Not Implemented</error></data>'])]
    #[TestWith([501, '<data><feature>s.enablecategorynavigation</feature></data>'])]
    #[TestWith([503, '<h1>Service Unavailable</h1>'])]
    #[TestWith([503, '<data><error>Service Unavailable</error></data>'])]
    #[TestWith([503, '<data><feature>s.enablecategorynavigation</feature></data>'])]
    #[TestWith([504, '<h1>Gateway Timeout</h1>'])]
    #[TestWith([504, '<data><error>Gateway Timeout</error></data>'])]
    #[TestWith([504, '<data><feature>s.enablecategorynavigation</feature></data>'])]
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

        $accountFeaturesService = new AccountFeaturesService(
            httpClient: $mockHttpClient,
        );

        $this->expectException(BadResponseException::class);
        $accountFeaturesService->execute($accountCredentials);
    }

    #[Test]
    public function testMaskHttpHeaders(): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $mockResponseContent = <<<'XML'
<data>
    <feature>
        <key>s.enablecategorynavigation</key>
        <value>yes</value>
    </feature>
    <feature>
        <key>allow.personalizedrecommendations</key>
        <value>yes</value>
    </feature>
    <feature>
        <key>s.preservedlayout</key>
        <value>yes</value>
    </feature>
</data>
XML;
        $mockResponse = $this->getMockResponse(
            statusCode: 200,
            bodyContents: $mockResponseContent,
        );
        $mockResponse->method('getHeaders')
            ->willReturn([
                'X-KLEVU-RESTAPIKEY' => ['1234567890'],
                'restApiKey' => '9876543210',
            ]);

        $mockHttpClient = $this->getMockHttpClient();
        $mockHttpClient->expects($this->once())
            ->method('sendRequest')
            // testExecute_Success already validates parameters assertions
            ->willReturn($mockResponse);

        $mockLogger = $this->getMockLogger();
        $mockLogger->expects($this->exactly(2))
            ->method('debug')
            ->with(
                $this->callback(static function (string $message): bool {
                    return match ($message) {
                        'Request for Klevu account features check',
                        'Response from Klevu account features check' => true,
                        default => false,
                    };
                }),
                $this->callback(function (array $context) use (&$debugContextCount): bool {
                    $debugContextCount++;

                    $this->assertArrayHasKey('headers', $context);
                    $this->assertIsArray($context['headers']);

                    switch ($debugContextCount) {
                        case 1: // Log Request
                            // Checks on log request performed in earlier test
                            break;

                        case 2: // Log Response
                            $this->assertSame(
                                [
                                    'X-KLEVU-RESTAPIKEY' => ['********'],
                                    'restApiKey' => '********',
                                ],
                                $context['headers'],
                            );
                            break;
                    }

                    return true;
                }),
            );

        $accountFeaturesService = new AccountFeaturesService(
            httpClient: $mockHttpClient,
            logger: $mockLogger,
        );

        $accountFeatures = $accountFeaturesService->execute($accountCredentials);
        $this->assertInstanceOf(AccountFeatures::class, $accountFeatures);
        // testExecute_Success already checks return assertions
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
}
