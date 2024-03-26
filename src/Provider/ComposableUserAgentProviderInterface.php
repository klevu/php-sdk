<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Provider;

interface ComposableUserAgentProviderInterface extends UserAgentProviderInterface
{
    /**
     * @param UserAgentProviderInterface $userAgentProvider
     * @param string|null $identifier
     *
     * @return void
     */
    public function addUserAgentProvider(
        UserAgentProviderInterface $userAgentProvider,
        ?string $identifier = null,
    ): void;

    /**
     * @param string $identifier
     *
     * @return UserAgentProviderInterface|null
     */
    public function getUserAgentProviderByIdentifier(string $identifier): ?UserAgentProviderInterface;
}
