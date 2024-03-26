<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Model\Analytics\Collect;

use Klevu\PhpSDK\Model\Analytics\Collect\UserProfile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserProfile::class)]
class UserProfileTest extends TestCase
{
    #[Test]
    #[TestWith([null, null])]
    #[TestWith(["", ""])]
    #[TestWith([" ", " "])]
    #[TestWith(["foo", "bar"])]
    #[TestWith(["127.0.0.1", "contact@klevu.com"])]
    public function testConstructor_Valid(
        ?string $ipAddress,
        ?string $email,
    ): void {
        $userProfile = new UserProfile(
            ipAddress: $ipAddress,
            email: $email,
        );

        $this->assertSame($ipAddress, $userProfile->ipAddress);
        $this->assertSame($email, $userProfile->email);
    }
}
