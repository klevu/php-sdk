<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model\Analytics\Collect;

/**
 * Immutable object representing a UserProfile within an analytics collect Event
 *
 * @link https://docs.klevu.com/apis/smart-search-analytics-events#Ai6mR
 * @see Event::$userProfile
 * @since 1.0.0
 */
class UserProfile
{
    /**
     * @param string|null $ipAddress
     * @param string|null $email
     */
    public function __construct(
        public readonly ?string $ipAddress = null,
        public readonly ?string $email = null,
    ) {
    }
}
