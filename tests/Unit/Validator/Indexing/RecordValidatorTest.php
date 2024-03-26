<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Validator\Indexing;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\Indexing\Record;
use Klevu\PhpSDK\Test\Fixture\Validator\InvalidDataValidator;
use Klevu\PhpSDK\Test\Fixture\Validator\InvalidTypeValidator;
use Klevu\PhpSDK\Validator\Indexing\RecordValidator;
use Klevu\PhpSDK\Validator\ValidatorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RecordValidator::class)]
class RecordValidatorTest extends TestCase
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
        $recordValidator = new RecordValidator();

        $this->expectException(InvalidTypeValidationException::class);
        try {
            $recordValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Record',
                haystack: $error,
            );
            throw $exception;
        }
    }

    /**
     * @return Record[][]
     */
    public static function dataProvider_testExecute_InvalidRecordId(): array
    {
        return [
            [
                new Record(
                    id: '',
                    type: 'KLEVU_PRODUCT',
                ),
            ],
            [
                new Record(
                    id: ' ' . PHP_EOL . ' ',
                    type: 'KLEVU_PRODUCT',
                ),
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidRecordId')]
    public function testExecute_InvalidRecordId(mixed $data): void
    {
        $recordValidator = new RecordValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $recordValidator->execute($data);
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

    /**
     * @return Record[][]
     */
    public static function dataProvider_testExecute_InvalidRecordType(): array
    {
        return [
            [
                new Record(
                    id: '123-456',
                    type: '',
                ),
            ],
            [
                new Record(
                    id: '123-456',
                    type: ' ' . PHP_EOL . ' ',
                ),
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidRecordType')]
    public function testExecute_InvalidRecordType(mixed $data): void
    {
        $recordValidator = new RecordValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $recordValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Record Type',
                haystack: $error,
            );
            throw $exception;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_InvalidRelations(): array
    {
        return [
            [
                null,
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidRelations')]
    public function testExecute_InvalidRelations(
        mixed $data, // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    ): void {
        $this->markTestIncomplete('No validation currently testable due to typecasting on Record->getRelations()');
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_InvalidAttributes(): array
    {
        return [
            [
                new Record(
                    id: '123-456',
                    type: 'KLEVU_PRODUCT',
                    attributes: [
                        '' => 'foo',
                        ' ' => 'bar',
                    ],
                ),
                [
                    'attributes: [] Attribute Name is required',
                    'attributes: [ ] Attribute Name is required',
                ],
            ],
        ];
    }

    /**
     * @param mixed $data
     * @param string[] $expectedErrors
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidAttributes')]
    public function testExecute_InvalidAttributes(
        mixed $data,
        array $expectedErrors,
    ): void {
        $recordValidator = new RecordValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $recordValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(
                expectedCount: count($expectedErrors),
                haystack: $errors,
            );

            sort($expectedErrors);
            sort($errors);
            $this->assertSame(
                expected: $expectedErrors,
                actual: $errors,
            );

            throw $exception;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_InvalidDisplay(): array
    {
        return [
            [
                new Record(
                    id: '123-456',
                    type: 'KLEVU_PRODUCT',
                    display: [
                        '' => 'foo',
                        ' ' => 'bar',
                    ],
                ),
                [
                    'display: [] Attribute Name is required',
                    'display: [ ] Attribute Name is required',
                ],
            ],
        ];
    }

    /**
     * @param mixed $data
     * @param string[] $expectedErrors
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidDisplay')]
    public function testExecute_InvalidDisplay(
        mixed $data,
        array $expectedErrors,
    ): void {
        $recordValidator = new RecordValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $recordValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(
                expectedCount: count($expectedErrors),
                haystack: $errors,
            );

            sort($expectedErrors);
            sort($errors);
            $this->assertSame(
                expected: $expectedErrors,
                actual: $errors,
            );

            throw $exception;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_MultipleFailures(): array
    {
        return [
            [
                new Record(
                    id: '',
                    type: '',
                    relations: [],
                    attributes: [
                        '' => 'foo',
                    ],
                    display: [
                        ' ' => 'bar',
                    ],
                ),
                [
                    'id: Record Id is required',
                    'type: Record Type is required',
                    'attributes: [] Attribute Name is required',
                    'display: [ ] Attribute Name is required',
                ],
            ],
        ];
    }

    /**
     * @param mixed $data
     * @param string[] $expectedErrors
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testExecute_MultipleFailures')]
    public function testExecute_MultipleFailures(
        mixed $data,
        array $expectedErrors,
    ): void {
        $recordValidator = new RecordValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $recordValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(
                expectedCount: count($expectedErrors),
                haystack: $errors,
            );

            sort($expectedErrors);
            sort($errors);
            $this->assertSame(
                expected: $expectedErrors,
                actual: $errors,
            );

            throw $exception;
        }
    }

    /**
     * @return Record[][]
     */
    public static function dataProvider_testExecute_Valid(): array
    {
        return [
            [
                new Record(
                    id: '123-456',
                    type: 'KLEVU_PRODUCT',
                    relations: null,
                    attributes: [],
                    display: null,
                ),
            ],
            [
                new Record(
                    id: '123-456',
                    type: 'KLEVU_PRODUCT',
                    relations: [],
                    attributes: [],
                    display: [],
                ),
            ],
            [
                new Record(
                    id: '123-456',
                    type: 'KLEVU_PRODUCT',
                    relations: [
                        'categories' => [
                            'type' => 'KLEVU_PRODUCT',
                            'values' => [
                                'foo',
                            ],
                        ],
                        'channels' => [
                            'additionalProp1' => [
                                'type' => 'KLEVU_PRODUCT',
                                'values' => [
                                    'foo',
                                ],
                            ],
                        ],
                        'grouping' => [
                            'type' => 'KLEVU_PRODUCT',
                            'values' => [
                                'foo',
                            ],
                            'channels' => [
                                'additionalProp1' => [
                                    'type' => 'KLEVU_PRODUCT',
                                    'values' => [
                                        'foo',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    attributes: [
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
                    display: [
                        'additionalProp1' => [],
                        'additionalProp2' => [],
                        'additionalProp3' => [],
                    ],
                ),
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_Valid')]
    public function testExecute_Valid(mixed $data): void
    {
        $recordValidator = new RecordValidator();

        $this->expectNotToPerformAssertions();
        $recordValidator->execute($data);
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_Construct_DataValidators_Invalid(): array
    {
        $validRecord = new Record(
            id: '123-456',
            type: 'KLEVU_PRODUCT',
            relations: [],
            attributes: [],
            display: [],
        );

        return [
            [
                [
                    'id' => [
                        new InvalidTypeValidator(['ID: Invalid Type']),
                    ],
                    'type' => [
                        new InvalidDataValidator(['Type: Invalid Data']),
                    ],
                    'relations' => [
                        new InvalidTypeValidator(['Relations: Invalid Type']),
                    ],
                    'attributes' => [
                        new InvalidDataValidator(['Attributes: Invalid Data']),
                    ],
                    'display' => [
                        new InvalidTypeValidator(['Display: Invalid Type']),
                    ],
                    'channels' => [
                        new InvalidDataValidator(['Channels: Invalid Data']),
                    ],
                ],
                $validRecord,
                [
                    'id: ID: Invalid Type',
                    'type: Type: Invalid Data',
                    'relations: Relations: Invalid Type',
                    'attributes: Attributes: Invalid Data',
                    'display: Display: Invalid Type',
                    // Channels not yet implemented
//                    'channels: Channels: Invalid Data',
                ],
            ],
            [
                [
                    'id' => [
                        new InvalidTypeValidator(['ID: Invalid Type']),
                    ],
                    'type' => [
                        new InvalidDataValidator(['Type: Invalid Data']),
                    ],
                    'relations' => null,
                    'attributes' => [
                        new InvalidDataValidator(['Attributes: Invalid Data']),
                    ],
                    'display' => [
                        new InvalidTypeValidator(['Display: Invalid Type']),
                    ],
                    'channels' => [
                        new InvalidDataValidator(['Channels: Invalid Data']),
                    ],
                ],
                $validRecord,
                [
                    'id: ID: Invalid Type',
                    'type: Type: Invalid Data',
                    'attributes: Attributes: Invalid Data',
                    'display: Display: Invalid Type',
                    // Channels not yet implemented
//                    'channels: Channels: Invalid Data',
                ],
            ],
        ];
    }

    /**
     * @param ValidatorInterface[] $dataValidators
     * @param mixed $data
     * @param string[] $expectedErrors
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testExecute_Construct_DataValidators_Invalid')]
    public function testExecute_Construct_DataValidators_Invalid(
        array $dataValidators,
        mixed $data,
        array $expectedErrors,
    ): void {
        $recordValidator = new RecordValidator(
            dataValidators: $dataValidators,
        );

        $this->expectException(InvalidDataValidationException::class);
        try {
            $recordValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(
                expectedCount: count($expectedErrors),
                haystack: $errors,
            );

            sort($expectedErrors);
            sort($errors);
            $this->assertSame(
                expected: $expectedErrors,
                actual: $errors,
            );

            throw $exception;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_Construct_DataValidators_Valid(): array
    {
        return [
            [
                [],
                new Record(
                    id: '',
                    type: '',
                    relations: [],
                    attributes: [
                        '' => 'foo',
                    ],
                    display: [
                        ' ' => 'bar',
                    ],
                ),
            ],
        ];
    }

    /**
     * @param ValidatorInterface[] $dataValidators
     * @param mixed $data
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testExecute_Construct_DataValidators_Valid')]
    public function testExecute_Construct_DataValidators_Valid(
        array $dataValidators,
        mixed $data,
    ): void {
        $recordValidator = new RecordValidator(
            dataValidators: $dataValidators,
        );

        $this->expectNotToPerformAssertions();
        $recordValidator->execute($data);
    }
}
