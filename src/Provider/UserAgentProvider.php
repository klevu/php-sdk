<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Provider;

use Klevu\PhpSDK\Provider\UserAgent\PhpSDKUserAgentProvider;

/**
 * Composable implementation of user agent provider
 *
 * @since 1.0.0
 */
class UserAgentProvider implements ComposableUserAgentProviderInterface
{
    use ComposableUserAgentProviderTrait;

    /**
     * @param UserAgentProviderInterface[] $userAgentProviders
     *      A new instance of {@see PhpSDKUserAgentProvider} is always added first,
     *      using key {@see PhpSDKUserAgentProvider::PRODUCT_NAME}
     */
    public function __construct(
        array $userAgentProviders = [],
    ) {
        $this->addUserAgentProvider(
            userAgentProvider: new PhpSDKUserAgentProvider(),
            identifier: PhpSDKUserAgentProvider::PRODUCT_NAME,
        );
        array_walk(
            array: $userAgentProviders,
            callback: function (mixed $userAgentProvider, int|string $identifier): void {
                $this->addUserAgentProvider(
                    userAgentProvider: $userAgentProvider,
                    identifier: is_string($identifier) ? $identifier : null,
                );
            },
        );
    }

    /**
     * Returns the complete user agent string to be used in API requests
     *
     * String is composed of all registered child providers' execute() return values, separated
     *  by a space character
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/User-Agent
     * @uses PhpSDKUserAgentProvider::execute()
     * @return string
     */
    public function execute(): string
    {
        $userAgentStrings = array_map(
            static fn (UserAgentProviderInterface $userAgentProvider): string => $userAgentProvider->execute(),
            $this->userAgentProviders,
        );

        return implode(
            separator: ' ',
            array: array_filter($userAgentStrings),
        );
    }
}
