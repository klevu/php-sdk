<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Validator;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Validator\RestAuthKeyValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(RestAuthKeyValidator::class)]
class RestAuthKeyValidatorTest extends TestCase
{
    /**
     * @return mixed[][]
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public static function dataProvider_testExecute_InvalidType(): array
    {
        return [
            [42],
            [3.14],
            [null],
            [true],
            [['foo', 'bar']],
            [static fn () => 'foo'],
            [(object)['foo' => 'bar']],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidType')]
    public function testExecute_InvalidType(mixed $data): void
    {
        $restAuthKeyValidator = new RestAuthKeyValidator();

        $this->expectException(InvalidTypeValidationException::class);
        try {
            $restAuthKeyValidator->execute($data);
        } catch (ValidationException $e) {
            $this->assertCount(1, $e->getErrors());
            throw $e;
        }
    }

    #[Test]
    #[TestWith([''])]
    #[TestWith([' '])]
    #[TestWith(['12345'])]
    #[TestWith(['ab;<&'])]
    public function testExecute_InvalidData(string $data): void
    {
        $restAuthKeyValidator = new RestAuthKeyValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $restAuthKeyValidator->execute($data);
        } catch (ValidationException $e) {
            $this->assertCount(1, $e->getErrors());
            throw $e;
        }
    }

    #[Test]
    #[TestWith(['ABCDE1234567890'])]
    #[TestWith(['abc123+DEF456/GHI789='])]
    public function testExecute_Valid(string $data): void
    {
        $restAuthKeyValidator = new RestAuthKeyValidator();

        $this->expectNotToPerformAssertions();
        $restAuthKeyValidator->execute($data);
    }
}
