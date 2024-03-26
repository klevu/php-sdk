<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model\Analytics\Collect;

/**
 * Factory class to create new instance of UserProfile object
 *
 * @see UserProfile
 * @since 1.0.0
 */
class UserProfileFactory
{
    /**
     * @param mixed[] $data
     *
     * @return UserProfile
     * @throws \TypeError Where data provided for a UserProfile constructor argument is an invalid type
     */
    public function create(array $data): UserProfile
    {
        // phpstan errors suppressed as we allow TypeError to bubble
        return new UserProfile(
            ipAddress: $data['ipAddress'] ?? null,
            email: $data['email'] ?? null,
        );
    }
}
