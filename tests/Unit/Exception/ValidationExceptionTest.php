<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Exception;

use Klevu\PhpSDK\Exception\ValidationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

/**
 * @todo Add tests for unhappy paths : getErrors()
 */
#[CoversClass(ValidationException::class)]
class ValidationExceptionTest extends TestCase
{
    /**
     * @param mixed[] $errorsInput
     * @param string[] $expectedOutput
     *
     * @return void
     */
    #[Test]
    #[TestWith([[], []])]
    #[TestWith([['foo'], ['foo']])]
    #[TestWith([[1, 2, 3], ['1', '2', '3']])]
    public function testGetErrors_Valid(array $errorsInput, array $expectedOutput): void
    {
        $validationException = new ValidationException($errorsInput);

        $this->assertSame($expectedOutput, $validationException->getErrors());
    }
}
