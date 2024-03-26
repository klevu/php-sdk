<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Provider;

/**
 * Contract for user agent provider implementation
 *
 * @since 1.0.0
 */
interface UserAgentProviderInterface
{
    /**
     * Returns string used as User-Agent header value in requests made by services
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/User-Agent
     * @return string
     */
    public function execute(): string;
}
