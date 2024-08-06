<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Validator\Indexing;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Validator\Indexing\AttributeNameValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(AttributeNameValidator::class)]
class AttributeNameValidatorTest extends TestCase
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
        $attributeNameValidator = new AttributeNameValidator();

        $this->expectException(InvalidTypeValidationException::class);
        try {
            $attributeNameValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Attribute Name',
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
        $attributeNameValidator = new AttributeNameValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $attributeNameValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Attribute Name is required',
                haystack: $error,
            );
            throw $exception;
        }
    }

    #[Test]
    #[TestWith(['aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'])] // phpcs:ignore Generic.Files.LineLength.TooLong
    #[TestWith(['   aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa   '])] // phpcs:ignore Generic.Files.LineLength.TooLong
    public function testExecute_InvalidLength(mixed $data): void
    {
        $attributeNameValidator = new AttributeNameValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $attributeNameValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertMatchesRegularExpression(
                pattern: '/^Attribute Name must be less than or equal to \d+ characters$/',
                string: $error,
            );
            throw $exception;
        }
    }

    #[Test]
    #[TestWith(['_foo'])]
    #[TestWith(['foo_'])]
    #[TestWith(['_foo_'])]
    #[TestWith(['product-name'])]
    #[TestWith(['foo!bar'])]
    #[TestWith(['テスト属性'])]
    public function testExecute_InvalidPattern(mixed $data): void
    {
        $attributeNameValidator = new AttributeNameValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $attributeNameValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Attribute Name',
                haystack: $error,
            );
            throw $exception;
        }
    }

    #[Test]
    #[TestWith(['new_attribute'])]
    #[TestWith(['Attribute12345'])]
    #[TestWith(['aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'])]
    #[TestWith(['0'])]
    public function testExecute_Valid(mixed $data): void
    {
        $attributeNameValidator = new AttributeNameValidator();

        $this->expectNotToPerformAssertions();
        $attributeNameValidator->execute($data);
    }
}
