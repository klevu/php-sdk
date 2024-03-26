<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Validator\Indexing\Record;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Validator\Indexing\Record\AttributesValidator;
use Klevu\PhpSDK\Validator\ValidatorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AttributesValidator::class)]
class AttributesValidatorTest extends TestCase
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
            [null],
            [true],
            [static fn () => 'foo'],
            [(object)['foo' => 'bar']],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidType')]
    public function testExecute_InvalidType(mixed $data): void
    {
        $attributesValidator = new AttributesValidator();

        $this->expectException(InvalidTypeValidationException::class);
        try {
            $attributesValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Attributes',
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
        $attributesValidator = new AttributesValidator();

        $this->expectException(InvalidDataValidationException::class);
        $this->expectExceptionMessage('Invalid keys for attributes');
        try {
            $attributesValidator->execute($data);
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
                    'name' => [
                        'default' => 'foo',
                        'additionalProp1' => 'bar',
                        'additionalProp2' => 'baz',
                    ],
                    'sku' => 'TEST_PRODUCT',
                    'images' => [
                        [
                            'url' => 'https://klevu.com/foo.png',
                            'type' => 'default',
                            'height' => 0,
                            'width' => 0,
                        ],
                    ],
                    'prices' => [
                        [
                            'amount' => 0,
                            'currency' => 'GBP',
                            'type' => 'defaultPrice',
                        ],
                    ],
                    'categoryPath' => 'foo;;bar;baz',
                    'url' => 'https://www.klevu.com/test-product.html',
                    'inStock' => true,
                    'shortDescription' => [
                        'default' => '<h1>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</h1> 
                            <p>Pellentesque pellentesque dapibus erat eget efficitur.</p>',
                    ],
                    'description' => [
                        'default' => '&lt;h1&gt;Lorem ipsum dolor sit amet, consectetur adipiscing elit.&lt;/h1&gt; 
                            &lt;p&gt;Pellentesque pellentesque dapibus erat eget efficitur.&lt;/p&gt;',
                    ],
                    'boosting' => 1,
                    'rating' => 3.14,
                    'ratingCount' => 0,
                    'tags' => [
                        'foo',
                        'bar',
                        'baz',
                    ],
                    'colors' => [
                        [
                            'label' => [
                                'default' => 'foo',
                                'additionalProp1' => 'bar',
                            ],
                            'value' => 'baz',
                        ],
                    ],
                    'swatches' => [
                        [
                            'id' => 'foo',
                            'color' => 'red',
                            'swatchImage' => 'https://www.klevu.com/swatch.webp',
                            'image' => 'https://www.klevu.com/image.gif',
                            'numberOfAdditionalVariants' => 99,
                        ],
                    ],
                    'visibility' => 'catalog-search',
                    'additionalProp1' => 'string',
                ],
            ],

        ];
    }

    /**
     * @param mixed[] $data
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testExecute_Valid')]
    public function testExecute_Valid(array $data): void
    {
        $attributesValidator = new AttributesValidator();

        $this->expectNotToPerformAssertions();
        $attributesValidator->execute($data);
    }

    #[Test]
    public function testConstruct_AttributeNameValidator(): void
    {
        $mockAttributeNameValidator = $this->createMock(ValidatorInterface::class);
        $mockAttributeNameValidator->expects($this->exactly(3))
            ->method('execute')
            ->willThrowException(new ValidationException(
                errors: ['Test'],
            ));

        $attributesValidator = new AttributesValidator(
            attributeNameValidator: $mockAttributeNameValidator,
        );

        $this->expectException(InvalidDataValidationException::class);
        $attributesValidator->execute([
            'sku' => 'Test Product',
            'name' => [
                'default' => 'bar',
            ],
            'foo' => 'bar',
        ]);
    }

    #[Test]
    public function testConstruct_DataValidators(): void
    {
        $mockDataValidatorFail = $this->createMock(ValidatorInterface::class);
        $mockDataValidatorFail->expects($this->once())
            ->method('execute')
            ->willThrowException(new ValidationException(
                errors: ['Test'],
            ));

        $mockDataValidatorPass = $this->createMock(ValidatorInterface::class);
        $mockDataValidatorPass->expects($this->once())
            ->method('execute');

        $attributesValidator = new AttributesValidator(
            dataValidators: [
                $mockDataValidatorFail,
                'success' => $mockDataValidatorPass,
            ],
        );

        $this->expectException(InvalidDataValidationException::class);
        $this->expectExceptionMessage('Attributes data is not valid');
        try {
            $attributesValidator->execute([
                'sku' => 'Test Product',
                'name' => [
                    'default' => 'bar',
                ],
                'foo' => 'bar',
            ]);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();

            $this->assertCount(1, $errors);
            $this->assertSame('Test', $errors[0] ?? null);

            throw $exception;
        }
    }

    #[Test]
    public function testConstruct_DataValidators_Unset(): void
    {
        $dataValidators = [];

        $dataValidators['fail'] = $this->createMock(ValidatorInterface::class);
        $dataValidators['fail']->expects($this->never())
            ->method('execute')
            ->willThrowException(new ValidationException(
                errors: ['Test'],
            ));

        $dataValidators['pass'] = $this->createMock(ValidatorInterface::class);
        $dataValidators['pass']->expects($this->once())
            ->method('execute');

        $dataValidators['fail'] = null;

        $attributesValidator = new AttributesValidator(
            dataValidators: $dataValidators,
        );

        $attributesValidator->execute([
            'sku' => 'Test Product',
            'name' => [
                'default' => 'bar',
            ],
            'foo' => 'bar',
        ]);
    }
}
