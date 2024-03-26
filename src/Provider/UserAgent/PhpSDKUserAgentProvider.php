<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Provider\UserAgent;

// phpcs:ignore SlevomatCodingStandard.Namespaces.UseOnlyWhitelistedNamespaces.NonFullyQualified
use Composer\InstalledVersions;
use Klevu\PhpSDK\Provider\ComposableUserAgentProviderInterface;
use Klevu\PhpSDK\Provider\ComposableUserAgentProviderTrait;
use Klevu\PhpSDK\Provider\UserAgent\SystemInformation\PhpVersionProvider;
use Klevu\PhpSDK\Provider\UserAgentProviderInterface;

/**
 * User Agent Provider identifying PHP-SDK with composable system information
 *
 * @since 1.0.0
 */
class PhpSDKUserAgentProvider implements ComposableUserAgentProviderInterface
{
    use ComposableUserAgentProviderTrait;

    /**
     * Product name user agent part identifying Klevu PHP-SDK library
     *
     * @var string
     */
    final public const PRODUCT_NAME = 'klevu-php-sdk';

    /**
     * @param UserAgentProviderInterface[] $systemInformationProviders
     *      A new instance of {@see PhpVersionProvider} is always added first,
     *      using key "php"
     */
    public function __construct(
        array $systemInformationProviders = [],
    ) {
        $this->addUserAgentProvider(
            userAgentProvider: new PhpVersionProvider(),
            identifier: 'php',
        );
        array_walk(
            array: $systemInformationProviders,
            callback: function (mixed $systemInformationProvider, int|string $identifier): void {
                $this->addUserAgentProvider(
                    userAgentProvider: $systemInformationProvider,
                    identifier: is_string($identifier) ? $identifier : null,
                );
            },
        );
    }

    /**
     * Returns user agent for php-sdk library
     *
     * String is composed of product name and version, in addition to optional system
     *  information provided by child providers.
     *
     * @example klevu-php-sdk/0.0.0.1 (PHP 8.1.999; foo/1.2.3)
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/User-Agent
     * @return string
     */
    public function execute(): string
    {
        try {
            $version = InstalledVersions::getVersion('klevu/php-sdk');
        } catch (\OutOfBoundsException) {
            $version = null;
        }

        $userAgent = $version
            ? sprintf('%s/%s', self::PRODUCT_NAME, $version)
            : self::PRODUCT_NAME;

        $systemInformation = array_filter(
            array_map(
                static fn (UserAgentProviderInterface $systemInformationProvider): string => (
                    $systemInformationProvider->execute()
                ),
                $this->userAgentProviders,
            ),
        );
        if ($systemInformation) {
            $userAgent .= sprintf(
                ' (%s)',
                implode('; ', $systemInformation),
            );
        }

        return $userAgent;
    }
}
