<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Model\Indexing;

use Klevu\PhpSDK\Model\Indexing\UpdateOperations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(UpdateOperations::class)]
class UpdateOperationsTest extends TestCase
{
    #[Test]
    #[TestWith([UpdateOperations::ADD, true])]
    #[TestWith([UpdateOperations::REMOVE, true])]
    #[TestWith([UpdateOperations::REPLACE, true])]
    public function testRequiresPath(
        UpdateOperations $subject,
        bool $expectedResult,
    ): void {
        $this->assertSame(
            expected: $expectedResult,
            actual: $subject->requiresPath(),
        );
    }

    #[Test]
    #[TestWith([UpdateOperations::ADD, true])]
    #[TestWith([UpdateOperations::REMOVE, false])]
    #[TestWith([UpdateOperations::REPLACE, true])]
    public function testRequiresValue(
        UpdateOperations $subject,
        bool $expectedResult,
    ): void {
        $this->assertSame(
            expected: $expectedResult,
            actual: $subject->requiresValue(),
        );
    }
}
