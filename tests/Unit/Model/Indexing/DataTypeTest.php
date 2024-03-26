<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Model\Indexing;

use Klevu\PhpSDK\Model\Indexing\DataType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DataType::class)]
class DataTypeTest extends TestCase
{
    #[Test]
    public function testIsAvailableToCustomAttributes(): void
    {
        $this->assertTrue(DataType::STRING->isAvailableToCustomAttributes());
        $this->assertTrue(DataType::MULTIVALUE->isAvailableToCustomAttributes());
        // Future support
        $this->assertFalse(DataType::DATETIME->isAvailableToCustomAttributes());
        $this->assertFalse(DataType::NUMBER->isAvailableToCustomAttributes());
        // Core only
        $this->assertFalse(DataType::JSON->isAvailableToCustomAttributes());
        $this->assertFalse(DataType::BOOLEAN->isAvailableToCustomAttributes());
    }
}
