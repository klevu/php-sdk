<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Provider;

use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\AccountCredentials;
use Klevu\PhpSDK\Provider\RequestBearerTokenProvider;
use Klevu\PhpSDK\Provider\RequestBearerTokenProviderInterface;
use Klevu\PhpSDK\Traits\Psr17FactoryTrait;
use Klevu\PhpSDK\Validator\ValidatorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

#[CoversClass(RequestBearerTokenProvider::class)]
class RequestBearerTokenProviderTest extends TestCase
{
    use Psr17FactoryTrait;

    private const FIXTURE_JS_API_KEY = 'klevu-1234567890';
    private const FIXTURE_REST_AUTH_KEY = 'ABCDE1234567890';

    #[Test]
    public function testImplementsInterface(): void
    {
        $requestBearerTokenProvider = new RequestBearerTokenProvider();

        $this->assertInstanceOf(
            expected: RequestBearerTokenProviderInterface::class,
            actual: $requestBearerTokenProvider,
        );
    }

    #[Test]
    public function testConstruct_InjectedDependencies(): void
    {
        $mockAccountCredentialsValidator = $this->getMockBuilder(ValidatorInterface::class)
            ->getMock();
        $mockAccountCredentialsValidator->expects($this->atLeast(1))
            ->method('execute');

        $mockRequestHeadersValidator = $this->getMockBuilder(ValidatorInterface::class)
            ->getMock();
        $mockRequestHeadersValidator->expects($this->atLeast(1))
            ->method('execute');

        $requestBearerTokenProvider = new RequestBearerTokenProvider(
            accountCredentialsValidator: $mockAccountCredentialsValidator,
            requestHeadersValidator: $mockRequestHeadersValidator,
        );

        $requestBearerTokenProvider->getForRequest(
            accountCredentials: new AccountCredentials(
                jsApiKey: self::FIXTURE_JS_API_KEY,
                restAuthKey: self::FIXTURE_REST_AUTH_KEY,
            ),
            request: $this->createRequest(),
        );
    }

    #[Test]
    #[TestWith(['foo', 'ABCDE1234567890'])]
    #[TestWith(['klevu-123456789012345678901234567890', 'ABCDE1234567890'])]
    #[TestWith(['klevu-1234567890', '123'])]
    #[TestWith(['klevu-1234567890', '+-=-()fdmkgfldmklgfdmklgd'])]
    public function testGetForRequest_InvalidAccountCredentials(
        string $jsApiKey,
        string $restAuthKey,
    ): void {
        $requestBearerTokenProvider = new RequestBearerTokenProvider();

        $this->expectException(ValidationException::class);
        $requestBearerTokenProvider->getForRequest(
            accountCredentials: new AccountCredentials(
                jsApiKey: $jsApiKey,
                restAuthKey: $restAuthKey,
            ),
            request: $this->createRequest(),
        );
    }

    /**
     * @param string[] $contentTypes
     *
     * @return void
     */
    #[Test]
    #[TestWith([[]])]
    #[TestWith([['application/xml']])]
    #[TestWith([['application/json', 'application/xml']])]
    public function testGetForRequest_InvalidHeaders_ContentType(array $contentTypes): void
    {
        $requestBearerTokenProvider = new RequestBearerTokenProvider();

        $this->expectException(ValidationException::class);
        $requestBearerTokenProvider->getForRequest(
            accountCredentials: new AccountCredentials(
                jsApiKey: self::FIXTURE_JS_API_KEY,
                restAuthKey: self::FIXTURE_REST_AUTH_KEY,
            ),
            request: $this->createRequest(
                headers: [
                    RequestBearerTokenProviderInterface::API_HEADER_KEY_CONTENT_TYPE => $contentTypes,
                ],
            ),
        );
    }

    /**
     * @param string[] $apiKeys
     * @param string $accountCredentialsApiKey
     *
     * @return void
     */
    #[Test]
    #[TestWith([['foo'], self::FIXTURE_JS_API_KEY])]
    #[TestWith([['klevu-9876543210'], 'klevu-1234567890'])]
    #[TestWith([['klevu-1234567890', 'klevu-9876543210'], 'klevu-1234567890'])]
    public function testGetForRequest_InvalidHeaders_ApiKey(
        array $apiKeys,
        string $accountCredentialsApiKey,
    ): void {
        $requestBearerTokenProvider = new RequestBearerTokenProvider();

        $this->expectException(ValidationException::class);
        $requestBearerTokenProvider->getForRequest(
            accountCredentials: new AccountCredentials(
                jsApiKey: $accountCredentialsApiKey,
                restAuthKey: self::FIXTURE_REST_AUTH_KEY,
            ),
            request: $this->createRequest(
                headers: [
                    RequestBearerTokenProviderInterface::API_HEADER_KEY_APIKEY => $apiKeys,
                ],
            ),
        );
    }

    /**
     * @return string[][][]
     */
    public static function dataProvider_testGetForRequest_InvalidHeaders_Timestamp(): array
    {
        return [
            [
                [
                    'foo',
                ],
            ],
            [
                [
                    date('Fs M'),
                ],
            ],
            [
                [
                    date('c', time() - 3600),
                ],
            ],
            [
                [
                    date('c', time() + 3600),
                ],
            ],
        ];
    }

    /**
     * @param string[] $timestamps
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testGetForRequest_InvalidHeaders_Timestamp')]
    public function testGetForRequest_InvalidHeaders_Timestamp(
        array $timestamps,
    ): void {
        $requestBearerTokenProvider = new RequestBearerTokenProvider();

        $this->expectException(ValidationException::class);
        $requestBearerTokenProvider->getForRequest(
            accountCredentials: new AccountCredentials(
                jsApiKey: self::FIXTURE_JS_API_KEY,
                restAuthKey: self::FIXTURE_REST_AUTH_KEY,
            ),
            request: $this->createRequest(
                headers: [
                    RequestBearerTokenProviderInterface::API_HEADER_KEY_TIMESTAMP => $timestamps,
                ],
            ),
        );
    }

    /**
     * @param string[] $authAlgorithms
     *
     * @return void
     */
    #[Test]
    #[TestWith([['foo']])]
    #[TestWith([['HmacSHA384', 'HmacSHA256']])]
    public function testGetForRequest_InvalidHeaders_AuthAlgorithm(array $authAlgorithms): void
    {
        $requestBearerTokenProvider = new RequestBearerTokenProvider();

        $this->expectException(ValidationException::class);
        $requestBearerTokenProvider->getForRequest(
            accountCredentials: new AccountCredentials(
                jsApiKey: self::FIXTURE_JS_API_KEY,
                restAuthKey: self::FIXTURE_REST_AUTH_KEY,
            ),
            request: $this->createRequest(
                headers: [
                    RequestBearerTokenProviderInterface::API_HEADER_KEY_AUTH_ALGO => $authAlgorithms,
                ],
            ),
        );
    }

    #[Test]
    #[TestWith(['PUT', 'https://indexing.ksearchnet.com/v2/batch?test=1', '{}'])]
    #[TestWith(['GET', 'https://indexing.ksearchnet.com/v2/attributes', null])]
    public function testGetForRequest_Valid(
        string $method,
        string $url,
        ?string $content,
    ): void {
        $timestamp = date('c');
        $jsApiKey = self::FIXTURE_JS_API_KEY;

        $expectedPlainTextString = <<<'TEXT'
%method%
%path%
%querystring%
X-KLEVU-TIMESTAMP=%timestamp%
X-KLEVU-APIKEY=%jsApiKey%
X-KLEVU-AUTH-ALGO=HmacSHA384
Content-Type=application/json
%content%
TEXT;
        $parsedUrl = parse_url($url);
        $expectedPlainTextString = str_replace(
            search: [
                '%method%',
                '%path%',
                '%querystring%',
                '%timestamp%',
                '%jsApiKey%',
                '%content%',
            ],
            replace: [
                $method,
                $parsedUrl['path'] ?? '',
                ($parsedUrl['query'] ?? null)
                    ? '?' . $parsedUrl['query']
                    : '',
                $timestamp,
                $jsApiKey,
                $content,
            ],
            subject: $expectedPlainTextString,
        );

        $expectedBearerToken = base64_encode(
            hash_hmac(
                algo: 'sha384',
                data: $expectedPlainTextString,
                key: self::FIXTURE_REST_AUTH_KEY,
                binary: true,
            ),
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: $jsApiKey,
            restAuthKey: self::FIXTURE_REST_AUTH_KEY,
        );
        $request = $this->createRequest(
            method: $method,
            uri: $url,
            headers: [
                RequestBearerTokenProviderInterface::API_HEADER_KEY_CONTENT_TYPE => [
                    'application/json',
                ],
                RequestBearerTokenProviderInterface::API_HEADER_KEY_APIKEY => [
                    'klevu-1234567890',
                ],
                RequestBearerTokenProviderInterface::API_HEADER_KEY_AUTH_ALGO => [
                    'HmacSHA384',
                ],
                RequestBearerTokenProviderInterface::API_HEADER_KEY_TIMESTAMP => [
                    $timestamp,
                ],
            ],
            content: $content,
        );

        $requestBearerTokenProvider = new RequestBearerTokenProvider();
        $actualBearerToken = $requestBearerTokenProvider->getForRequest(
            accountCredentials: $accountCredentials,
            request: $request,
        );

        $this->assertSame(
            expected: $expectedBearerToken,
            actual: $actualBearerToken,
        );
    }

    /**
     * @param string $method
     * @param string $uri
     * @param string[][] $headers
     * @param string $content
     *
     * @return RequestInterface
     */
    private function createRequest(
        string $method = 'GET',
        string $uri = 'https://www.klevu.com/',
        array $headers = [],
        ?string $content = null,
    ): RequestInterface {
        $headers = array_merge(
            [
                RequestBearerTokenProviderInterface::API_HEADER_KEY_CONTENT_TYPE => [
                    'application/json',
                ],
                RequestBearerTokenProviderInterface::API_HEADER_KEY_APIKEY => [
                    self::FIXTURE_JS_API_KEY,
                ],
                RequestBearerTokenProviderInterface::API_HEADER_KEY_TIMESTAMP => [
                    date('c'),
                ],
                RequestBearerTokenProviderInterface::API_HEADER_KEY_AUTH_ALGO => [
                    'HmacSHA384',
                ],
            ],
            $headers,
        );

        $psr17Factory = $this->getPsr17Factory();
        $request = $psr17Factory->createRequest(
            method: $method,
            uri: $uri,
        );
        foreach ($headers as $header => $values) {
            foreach ($values as $value) {
                $request = $request->withHeader($header, $value);
            }
        }
        if (null !== $content) {
            $request = $request->withBody(
                $psr17Factory->createStream($content),
            );
        }

        return $request;
    }
}
