<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Validator\Indexing\Record;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Validator\Indexing\Record\DisplayValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DisplayValidator::class)]
class DisplayValidatorTest extends TestCase
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
            [static fn () => 'foo'],
            [(object)['foo' => 'bar']],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidType')]
    public function testExecute_InvalidType(mixed $data): void
    {
        $displayValidator = new DisplayValidator();

        $this->expectException(InvalidTypeValidationException::class);
        try {
            $displayValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Display',
                haystack: $error,
            );
            throw $exception;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_InvalidAttributeName(): array
    {
        return [
            [
                [
                    '' => 'Empty Attribute',
                ],
                [
                    '',
                ],
            ],
            [
                [
                    1 => 'Numeric attribute name',
                ],
                [
                    1,
                ],
            ],
            [
                [
                    0 => 'Numeric attribute name',
                    'sku' => 'SKU',
                    '' => 'Empty',
                    ' ' => 'Empty (after trim)',
                ],
                [
                    0,
                    '',
                    ' ',
                ],
            ],
        ];
    }

    /**
     * @param mixed[] $data
     * @param string[] $invalidAttributeNames
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidAttributeName')]
    public function testExecute_InvalidAttributeName(
        array $data,
        array $invalidAttributeNames,
    ): void {
        $displayValidator = new DisplayValidator();

        $this->expectException(InvalidDataValidationException::class);
        $this->expectExceptionMessage('Invalid keys for display');
        try {
            $displayValidator->execute($data);
        } catch (ValidationException $exception) {
            foreach ($invalidAttributeNames as $invalidAttributeName) {
                $this->assertStringContainsString(
                    needle: '"' . $invalidAttributeName . '"',
                    haystack: $exception->getMessage(),
                );
            }

            $errors = $exception->getErrors();
            $this->assertCount(count($invalidAttributeNames), $errors);

            throw $exception;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_Valid(): array
    {
        return [
            [
                null,
            ],
            [
                [],
            ],
            [
                [
                    'sku' => 'TEST_PRODUCT',
                ],
            ],
            [
                [
                    'name' => [
                        'default' => 'Test Product',
                        'es_ES' => 'Producto de Ensayo',
                    ],
                    'sku' => '123TEST',
                ],
            ],
            [
                [
                    'additionalProp1' => [],
                    'additionalProp2' => [],
                    'additionalProp3' => [],
                ],
            ],
        ];
    }

    /**
     * @param mixed[]|null $data
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testExecute_Valid')]
    public function testExecute_Valid(?array $data): void
    {
        $displayValidator = new DisplayValidator();

        $this->expectNotToPerformAssertions();
        $displayValidator->execute($data);
    }
}
