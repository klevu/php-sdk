<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Provider\UserAgent\SystemInformation;

use Klevu\PhpSDK\Provider\UserAgent\SystemInformation\PhpVersionProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpVersionProvider::class)]
class PhpVersionProviderTest extends TestCase
{
    #[Test]
    public function testExecute(): void
    {
        $phpVersionProvider = new PhpVersionProvider();

        $this->assertSame(
            expected: 'PHP ' . phpversion(),
            actual: $phpVersionProvider->execute(),
        );
    }
}
