<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Validator\Indexing;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\Indexing\Attribute;
use Klevu\PhpSDK\Model\Indexing\DataType;
use Klevu\PhpSDK\Validator\Indexing\AttributeValidator;
use Klevu\PhpSDK\Validator\ValidatorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(AttributeValidator::class)]
class AttributeValidatorTest extends TestCase
{
    /**
     * @return mixed[][]
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public static function dataProvider_testExecute_InvalidType(): array
    {
        return [
            ['foo'],
            [42],
            [3.14],
            [true],
            [null],
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
        $attributeValidator = new AttributeValidator();

        $this->expectException(InvalidTypeValidationException::class);
        try {
            $attributeValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Attribute must be instance of',
                haystack: $error,
            );

            throw $exception;
        }
    }

    #[Test]
    #[TestWith([''])]
    #[TestWith([' '])]
    #[TestWith(['_foo'])]
    #[TestWith(['foo_'])]
    #[TestWith(['_foo_'])]
    #[TestWith(['foo!bar'])]
    #[TestWith(['テスト属性'])]
    #[TestWith(['aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'])] // phpcs:ignore Generic.Files.LineLength.TooLong
    #[TestWith(['    aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa    '])] // phpcs:ignore Generic.Files.LineLength.TooLong
    public function testExecute_InvalidAttributeName(string $data): void
    {
        $attributeValidator = new AttributeValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $attribute = new Attribute(
                attributeName: $data,
                datatype: 'STRING',
            );

            $attributeValidator->execute($attribute);
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
    #[TestWith([''])]
    #[TestWith([' '])]
    #[TestWith(['new_attribute'])]
    #[TestWith(['Attribute12345'])]
    #[TestWith(['aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'])]
    #[TestWith(['0'])]
    public function testExecute_InjectedAttributeNameValidator_Valid(string $data): void
    {
        /** @var ValidatorInterface&MockObject $mockAttributeNameValidator */
        $mockAttributeNameValidator = $this->createMock(
            ValidatorInterface::class,
        );
        $mockAttributeNameValidator->expects($this->once())
            ->method('execute');

        $attributeValidator = new AttributeValidator(
            attributeNameValidator: $mockAttributeNameValidator,
        );

        $attribute = new Attribute(
            attributeName: $data,
            datatype: 'STRING',
        );

        $attributeValidator->execute($attribute);
    }

    #[Test]
    #[TestWith(['test_attribute'])]
    public function testExecute_InjectedAttributeNameValidator_Invalid(string $data): void
    {
        /** @var ValidatorInterface&MockObject $mockAttributeNameValidator */
        $mockAttributeNameValidator = $this->createMock(
            ValidatorInterface::class,
        );
        $mockAttributeNameValidator->expects($this->once())
            ->method('execute')
            ->willThrowException(
                exception: new InvalidDataValidationException(
                    errors: [
                        'Test Error',
                    ],
                    message: 'Test Exception',
                ),
            );

        $attributeValidator = new AttributeValidator(
            attributeNameValidator: $mockAttributeNameValidator,
        );

        $this->expectException(InvalidDataValidationException::class);
        try {
            $attribute = new Attribute(
                attributeName: $data,
                datatype: 'STRING',
            );

            $attributeValidator->execute($attribute);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Test Error',
                haystack: $error,
            );

            throw $exception;
        }
    }

    #[Test]
    #[TestWith([''])]
    #[TestWith([' '])]
    public function testExecute_EmptyDatatype(string $data): void
    {
        $attributeValidator = new AttributeValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $attribute = new Attribute(
                attributeName: 'test_attribute',
                datatype: $data,
            );

            $attributeValidator->execute($attribute);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Attribute datatype must not be empty',
                haystack: $error,
            );

            throw $exception;
        }
    }

    #[Test]
    #[TestWith(['0'])]
    #[TestWith(['string'])]
    #[TestWith(['FOO'])]
    #[TestWith([' STRING'])]
    public function testExecute_InvalidDatatype(string $data): void
    {
        $attributeValidator = new AttributeValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $attribute = new Attribute(
                attributeName: 'test_attribute',
                datatype: $data,
            );

            $attributeValidator->execute($attribute);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertMatchesRegularExpression(
                pattern: '/Attribute datatype .* is not a recognised value/',
                string: $error,
            );

            throw $exception;
        }
    }

    #[Test]
    #[TestWith(['DATETIME'])]
    #[TestWith(['JSON'])]
    #[TestWith(['BOOLEAN'])]
    public function testExecute_DatatypeNotAvailable(string $data): void
    {
        $attributeValidator = new AttributeValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $attribute = new Attribute(
                attributeName: 'test_attribute',
                datatype: $data,
                immutable: false,
            );

            $attributeValidator->execute($attribute);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertMatchesRegularExpression(
                pattern: '/Attribute datatype .* is not available to custom attributes/',
                string: $error,
            );

            throw $exception;
        }
    }

    /**
     * @return Attribute[][]
     */
    public static function dataProvider_testExecute_Valid(): array
    {
        $return = [];
        foreach (DataType::cases() as $datatype) {
            $return[] = [
                new Attribute(
                    attributeName: 'attr_' . $datatype->value,
                    datatype: $datatype->value,
                    immutable: true,
                ),
            ];
        }

        $return[] = [
            new Attribute(
                attributeName: 'test_attribute',
                datatype: 'STRING',
                label: [
                    'default' => 'Test',
                ],
                searchable: false,
                filterable: false,
                returnable: false,
                immutable: false,
            ),
            new Attribute(
                attributeName: 'test_attribute',
                datatype: 'MULTIVALUE',
                label: [
                    'default' => 'Test',
                ],
                searchable: false,
                filterable: false,
                returnable: false,
            ),
            new Attribute(
                attributeName: 'test_attribute',
                datatype: 'NUMBER',
                label: [
                    'default' => 'Test',
                ],
                searchable: false,
                filterable: false,
                returnable: false,
            ),
            new Attribute(
                attributeName: 'test_attribute',
                datatype: 'MULTIVALUE_NUMBER',
                label: [
                    'default' => 'Test',
                ],
                searchable: false,
                filterable: false,
                rangeable: true,
                returnable: false,
            ),
        ];

        return $return;
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_Valid')]
    public function testExecute_Valid(mixed $data): void
    {
        $attributeValidator = new AttributeValidator();

        $this->expectNotToPerformAssertions();
        $attributeValidator->execute($data);
    }
}
