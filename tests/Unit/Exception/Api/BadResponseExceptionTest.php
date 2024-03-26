<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Exception\Api;

use Klevu\PhpSDK\Exception\Api\BadResponseException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(BadResponseException::class)]
class BadResponseExceptionTest extends TestCase
{
    /**
     * @param string[] $errors
     * @param string $message
     * @param int $code
     *
     * @return void
     * @throws Exception
     */
    #[Test]
    #[TestWith(['Error Message', 418, ['Error 1', 'Error 2']])]
    #[TestWith(['Error Message', 418, [123]])]
    public function testConstructor_WithArgs(
        string $message,
        int $code,
        array $errors,
    ): void {
        $previousException = $this->createMock(\Throwable::class);
        $exception = new BadResponseException(
            message: $message,
            code: $code,
            errors: $errors,
            previous: $previousException,
        );

        $this->assertSame(array_map('strval', $errors), $exception->getErrors());
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    #[Test]
    public function testConstructor_WithoutArgs(): void
    {
        $exception = new BadResponseException(
            message: '',
            code: 0,
        );

        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame([], $exception->getErrors());
        $this->assertNull($exception->getPrevious());
    }
}
