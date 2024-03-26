<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Provider\UserAgent\SystemInformation;

use Klevu\PhpSDK\Provider\UserAgentProviderInterface;

/**
 * System Information Provider for User Agents identifying running PHP version
 *
 * @since 1.0.0
 */
class PhpVersionProvider implements UserAgentProviderInterface
{
    /**
     * Returns PHP version system information string for use with composite user agent providers
     *
     * @example PHP 8.1.999
     * @return string
     */
    public function execute(): string
    {
        return sprintf('PHP %s', phpversion());
    }
}
