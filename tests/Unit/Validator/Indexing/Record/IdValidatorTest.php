<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Validator\Indexing\Record;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Validator\Indexing\Record\IdValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(IdValidator::class)]
class IdValidatorTest extends TestCase
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
            [true],
            [static fn () => 'foo'],
            [(object)['foo' => 'bar']],
            [[]],
            [['foo']],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidType')]
    public function testExecute_InvalidType(mixed $data): void
    {
        $idValidator = new IdValidator();

        $this->expectException(InvalidTypeValidationException::class);
        try {
            $idValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Record Id',
                haystack: $error,
            );
            throw $exception;
        }
    }

    #[Test]
    #[TestWith([null])]
    #[TestWith([''])]
    #[TestWith([' '])]
    public function testExecute_Empty(mixed $data): void
    {
        $idValidator = new IdValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $idValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Record Id',
                haystack: $error,
            );
            throw $exception;
        }
    }

    #[Test]
    #[TestWith(['123-456'])]
    #[TestWith(['TEST_PRODUCT'])]
    #[TestWith(['0'])]
    public function testExecute_Valid(mixed $data): void
    {
        $idValidator = new IdValidator();

        $this->expectNotToPerformAssertions();
        $idValidator->execute($data);
    }
}
