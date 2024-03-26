<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Provider\UserAgent;

use Klevu\PhpSDK\Provider\UserAgent\PhpSDKUserAgentProvider;
use Klevu\PhpSDK\Provider\UserAgent\SystemInformation\PhpVersionProvider;
use Klevu\PhpSDK\Provider\UserAgentProvider;
use Klevu\PhpSDK\Provider\UserAgentProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpSDKUserAgentProvider::class)]
class PhpSDKUserAgentProviderTest extends TestCase
{
    #[Test]
    public function testExecute_NullConstructor(): void
    {
        $phpSDKUserAgentProvider = new PhpSDKUserAgentProvider();
        $this->assertInstanceOf(
            expected: PhpVersionProvider::class,
            actual: $phpSDKUserAgentProvider->getUserAgentProviderByIdentifier('php'),
        );

        $expectedResult = sprintf(
            'klevu-php-sdk/%s (PHP %s)',
            $this->getLibraryVersion(),
            phpversion(),
        );
        $actualResult = $phpSDKUserAgentProvider->execute();

        $this->assertSame(
            expected: $expectedResult,
            actual: $actualResult,
        );
    }

    public function testExecute_EmptyConstructor(): void
    {
        $phpSDKUserAgentProvider = new PhpSDKUserAgentProvider([]);
        $this->assertInstanceOf(
            expected: PhpVersionProvider::class,
            actual: $phpSDKUserAgentProvider->getUserAgentProviderByIdentifier('php'),
        );

        $expectedResult = sprintf(
            'klevu-php-sdk/%s (PHP %s)',
            $this->getLibraryVersion(),
            phpversion(),
        );
        $actualResult = $phpSDKUserAgentProvider->execute();

        $this->assertSame(
            expected: $expectedResult,
            actual: $actualResult,
        );
    }

    public function testExecute_InjectedProviders(): void
    {
        $phpSDKUserAgentProvider = new PhpSDKUserAgentProvider([
            $this->getMockUserAgentProvider(''),
            'test' => $this->getMockUserAgentProvider('test/1.0'),
        ]);
        $this->assertInstanceOf(
            expected: PhpVersionProvider::class,
            actual: $phpSDKUserAgentProvider->getUserAgentProviderByIdentifier('php'),
        );
        $this->assertInstanceOf(
            expected: MockObject::class,
            actual: $phpSDKUserAgentProvider->getUserAgentProviderByIdentifier('test'),
        );

        $expectedResult = sprintf(
            'klevu-php-sdk/%s (PHP %s; test/1.0)',
            $this->getLibraryVersion(),
            phpversion(),
        );
        $actualResult = $phpSDKUserAgentProvider->execute();

        $this->assertSame(
            expected: $expectedResult,
            actual: $actualResult,
        );
    }

    #[Test]
    #[TestWith(['test/1.0', 'klevu-php-sdk/%s (test/1.0)'])]
    #[TestWith(['', 'klevu-php-sdk/%s'])]
    public function testExecute_OverwrittenProviders(
        string $overrideReturnValue,
        string $expectedResult,
    ): void {
        $phpSDKUserAgentProvider = new PhpSDKUserAgentProvider([
            'php' => $this->getMockUserAgentProvider($overrideReturnValue),
        ]);
        $this->assertInstanceOf(
            expected: MockObject::class,
            actual: $phpSDKUserAgentProvider->getUserAgentProviderByIdentifier('php'),
        );

        $actualResult = $phpSDKUserAgentProvider->execute();
        $expectedResult = sprintf(
            $expectedResult,
            $this->getLibraryVersion(),
        );

        $this->assertSame(
            expected: $expectedResult,
            actual: $actualResult,
        );
    }

    #[Test]
    public function testExecute_InvalidProviders(): void
    {
        $this->expectException(\TypeError::class);

        // @phpstan-ignore-next-line This is literally the error we're testing for
        new PhpSDKUserAgentProvider([
            new \stdClass(),
        ]);
    }

    #[Test]
    public function testAddGetUserAgentProvider(): void
    {
        $userAgentProvider = new UserAgentProvider();

        $this->assertNull(
            $userAgentProvider->getUserAgentProviderByIdentifier('foo'),
        );

        $mockUserAgentProvider = $this->getMockUserAgentProvider('foo');
        $userAgentProvider->addUserAgentProvider(
            userAgentProvider: $mockUserAgentProvider,
            identifier: 'foo',
        );

        $this->assertSame(
            expected: $mockUserAgentProvider,
            actual: $userAgentProvider->getUserAgentProviderByIdentifier('foo'),
        );
    }

    /**
     * @return string
     */
    private function getLibraryVersion(): string
    {
        $composerContent = json_decode(
            json: file_get_contents(__DIR__ . '/../../../../composer.json') ?: '{}',
            associative: true,
        );
        if (!is_array($composerContent)) {
            $composerContent = [];
        }

        $version = $composerContent['version'] ?? '-';
        $versionParts = explode('.', $version) + array_fill(0, 4, '0');

        return implode('.', $versionParts);
    }

    /**
     * @param string $returnValue
     *
     * @return MockObject&UserAgentProviderInterface
     * @throws Exception
     */
    private function getMockUserAgentProvider(string $returnValue): MockObject&UserAgentProviderInterface
    {
        $mockUserAgentProvider = $this->createMock(UserAgentProviderInterface::class);
        $mockUserAgentProvider->method('execute')
            ->willReturn($returnValue);

        return $mockUserAgentProvider;
    }
}
