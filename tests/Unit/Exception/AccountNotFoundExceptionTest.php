<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Exception;

use Klevu\PhpSDK\Exception\AccountNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @todo Add tests for unhappy paths : getErrors()
 */
#[CoversClass(AccountNotFoundException::class)]
class AccountNotFoundExceptionTest extends TestCase
{
    #[Test]
    public function testConstruct_WithMessage(): void
    {
        $exception = new AccountNotFoundException(
            jsApiKey: '',
            message: 'Test Exception Message',
        );

        $this->assertSame(
            'Test Exception Message',
            $exception->getMessage(),
        );
    }

    #[Test]
    public function testConstruct_WithoutMessage(): void
    {
        $exception = new AccountNotFoundException(
            jsApiKey: 'klevu-1234567890',
        );

        $this->assertSame(
            'Klevu account not found for key "klevu-1234567890"',
            $exception->getMessage(),
        );
    }
}
