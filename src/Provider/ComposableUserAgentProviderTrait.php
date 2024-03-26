<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Provider;

trait ComposableUserAgentProviderTrait
{
    /**
     * @var UserAgentProviderInterface[]
     */
    private array $userAgentProviders = [];

    /**
     * Adds a child provider to be used when generating the User-Agent string
     *
     * Providers registered with an identifier (ie a non-empty string) will override earlier
     *  providers registered with the same identifier.
     * To remove an identifier, inject a provider which returns an empty string with the
     *  identifier of the provider to be removed.
     *
     * @param UserAgentProviderInterface $userAgentProvider
     * @param string|null $identifier
     *
     * @return void
     */
    public function addUserAgentProvider(
        UserAgentProviderInterface $userAgentProvider,
        ?string $identifier = null,
    ): void {
        if ($identifier) {
            $this->userAgentProviders[$identifier] = $userAgentProvider;
        } else {
            $this->userAgentProviders[] = $userAgentProvider;
        }
    }

    /**
     * Returns a registered child provider based upon identifier
     *
     * @note This method can never return providers registered without an identifier
     *
     * @param string $identifier
     *
     * @return UserAgentProviderInterface|null
     */
    public function getUserAgentProviderByIdentifier(string $identifier): ?UserAgentProviderInterface
    {
        return $this->userAgentProviders[$identifier] ?? null;
    }
}
