<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Exception\Validation;

use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(InvalidTypeValidationException::class)]
class InvalidTypeValidationExceptionTest extends TestCase
{
    /**
     * @param string[] $errors
     * @param string $message
     * @param int $code
     *
     * @return void
     */
    #[Test]
    #[TestWith([['Error 1', 'Error 2'], 'Error Message', 418])]
    public function testConstructor_WithArgs(
        array $errors,
        string $message,
        int $code,
    ): void {
        $exception = new InvalidTypeValidationException(
            errors: $errors,
            message: $message,
            code: $code,
        );

        $this->assertSame($errors, $exception->getErrors());
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    #[Test]
    public function testConstructor_WithoutArgs(): void
    {
        $exception = new InvalidTypeValidationException([]);

        $this->assertSame('Invalid data type received', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}
