<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Validator\Indexing\Record;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Validator\Indexing\Record\GroupsValidator;
use Klevu\PhpSDK\Validator\ValidatorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GroupsValidator::class)]
class GroupsValidatorTest extends TestCase
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
        $groupsValidator = new GroupsValidator();

        $this->expectException(InvalidTypeValidationException::class);
        try {
            $groupsValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Groups',
                haystack: $error,
            );
            throw $exception;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_InvalidGroupName(): array
    {
        return [
            [
                [
                    '' => [
                        'error' => 'Empty Group',
                    ],
                ],
                [
                    0,
                ],
                [
                    '[] Group Name is required',
                ],
            ],
            [
                [
                    1 => [
                        'error' => 'Numeric group name',
                    ],
                ],
                [
                    0,
                ],
                [
                    '[1] Group Name must be string, received int',
                ],
            ],
            [
                [
                    0 => [
                        'error' => 'Numeric group name',
                    ],
                    'fr_FR' => [],
                    '' => [
                        'error' => 'Empty',
                    ],
                    ' ' => [
                        'error' => 'Empty (after trim)',
                    ],
                    'fr-FR' => [
                        'error' => 'Invalid format',
                    ],
                ],
                [
                    0,
                    2,
                    3,
                    4,
                ],
                [
                    '[0] Group Name must be string, received int',
                    '[] Group Name is required',
                    '[ ] Group Name is required',
                    '[fr-FR] Group Name must be alphanumeric, and can include underscores (_)',
                ],
            ],
            // phpcs:disable Generic.Files.LineLength.TooLong
            [
                [
                    'GROUP1' => [
                        'attributes' => [
                            '' => [],
                            ' ' => [],
                            'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa' => [],
                            '   aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa   ' => [],
                        ],
                    ],
                    'group__2' => [
                        'attributes' => [
                            '_foo' => [],
                            'foo_' => [],
                            '_foo_' => [],
                            'product-name' => [],
                            'foo!bar' => [],
                            'テスト属性' => [],
                        ],
                    ],
                ],
                [
                    0,
                    1,
                ],
                [
                    '[GROUP1] '
                        . '[] Attribute Name is required; '
                        . '[ ] Attribute Name is required; '
                        . '[aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa] Attribute Name must be less than or equal to 200 characters; '
                        . '[   aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa   ] Attribute Name must be less than or equal to 200 characters',
                    '[group__2] '
                        . '[_foo] Attribute Name must be alphanumeric, can include underscores (_) but cannot start or end with an underscore; '
                        . '[foo_] Attribute Name must be alphanumeric, can include underscores (_) but cannot start or end with an underscore; '
                        . '[_foo_] Attribute Name must be alphanumeric, can include underscores (_) but cannot start or end with an underscore; '
                        . '[product-name] Attribute Name must be alphanumeric, can include underscores (_) but cannot start or end with an underscore; '
                        . '[foo!bar] Attribute Name must be alphanumeric, can include underscores (_) but cannot start or end with an underscore; '
                        . '[テスト属性] Attribute Name must be alphanumeric, can include underscores (_) but cannot start or end with an underscore',
                ],
            ],
            // phpcs:enable Generic.Files.LineLength.TooLong
        ];
    }

    /**
     * @param mixed[] $data
     * @param int[] $invalidRowIndexes
     * @param string[] $expectedErrors
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidGroupName')]
    public function testExecute_InvalidGroupName(
        array $data,
        array $invalidRowIndexes,
        array $expectedErrors,
    ): void {
        $groupsValidator = new GroupsValidator();

        $this->expectException(InvalidDataValidationException::class);
        $this->expectExceptionMessage('Invalid row(s) for groups: ' . implode(', ', $invalidRowIndexes));
        try {
            $groupsValidator->execute($data);
        } catch (ValidationException $exception) {
            $this->assertEquals(
                expected: $expectedErrors,
                actual: $exception->getErrors(),
            );

            throw $exception;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_InvalidGroupData(): array
    {
        return [
            [
                [
                    'null' => null,
                    'str' => 'foo',
                    'int' => 42,
                    'float' => 3.14,
                    'bool' => true,
                    'obj' => (object)[
                        'attributes' => [
                            'foo' => 'bar',
                        ],
                    ],
                ],
                [
                    0,
                    1,
                    2,
                    3,
                    4,
                    5,
                ],
                [
                    '[null] Group data must be array, received null',
                    '[str] Group data must be array, received string',
                    '[int] Group data must be array, received int',
                    '[float] Group data must be array, received float',
                    '[bool] Group data must be array, received bool',
                    '[obj] Group data must be array, received stdClass',
                ],
            ],
        ];
    }

    /**
     * @param mixed[] $data
     * @param int[] $invalidRowIndexes
     * @param string[] $expectedErrors
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidGroupData')]
    public function testExecute_InvalidGroupData(
        array $data,
        array $invalidRowIndexes,
        array $expectedErrors,
    ): void {
        $mockAttributesValidator = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAttributesValidator->expects($this->never())
            ->method('execute');

        $groupsValidator = new GroupsValidator(
            attributesValidator: $mockAttributesValidator,
        );

        $this->expectException(InvalidDataValidationException::class);
        $this->expectExceptionMessage('Invalid row(s) for groups: ' . implode(', ', $invalidRowIndexes));
        try {
            $groupsValidator->execute($data);
        } catch (ValidationException $exception) {
            $this->assertEquals(
                expected: $expectedErrors,
                actual: $exception->getErrors(),
            );

            throw $exception;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_InvalidAttributesData(): array
    {
        return [
            [
                [
                    'str' => [
                        'attributes' => 'foo',
                    ],
                    'int' => [
                        'attributes' => 42,
                    ],
                    'float' => [
                        'attributes' => 3.14,
                    ],
                    'bool' => [
                        'attributes' => true,
                    ],
                    'array' => [
                        'attributes' => [],
                    ],
                    'obj' => [
                        'attributes' => (object)[
                            'foo' => 'bar',
                        ],
                    ],
                ],
                [
                    0,
                    1,
                    2,
                    3,
                    5,
                ],
                [
                    '[str] Attributes must be array, received string',
                    '[int] Attributes must be array, received int',
                    '[float] Attributes must be array, received float',
                    '[bool] Attributes must be array, received bool',
                    '[obj] Attributes must be array, received stdClass',
                ],
            ],
        ];
    }

    /**
     * @param mixed[] $data
     * @param int[] $invalidRowIndexes
     * @param string[] $expectedErrors
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidAttributesData')]
    public function testExecute_InvalidAttributesData(
        array $data,
        array $invalidRowIndexes,
        array $expectedErrors,
    ): void {
        $groupsValidator = new GroupsValidator();

        $this->expectException(InvalidDataValidationException::class);
        $this->expectExceptionMessage('Invalid row(s) for groups: ' . implode(', ', $invalidRowIndexes));
        try {
            $groupsValidator->execute($data);
        } catch (ValidationException $exception) {
            $this->assertEquals(
                expected: $expectedErrors,
                actual: $exception->getErrors(),
            );

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
                    'en_GB' => [
                        'attributes' => [
                            'sku' => 'TEST_PRODUCT',
                        ],
                    ],
                ],
            ],
            [
                [
                    'en_GB' => [],
                ],
            ],
            [
                [
                    'en_GB' => [
                        'attributes' => [],
                    ],
                ],
            ],
            [
                [
                    '_my_new_group_' => [
                        'attributes' => [
                            'name' => [
                                'default' => 'Test Product',
                                'es_ES' => 'Producto de Ensayo',
                            ],
                            'sku' => '123TEST',
                        ],
                    ],
                ],
            ],
            [
                [
                    'GROUP1' => [
                        'attributes' => [
                            'additionalProp1' => [],
                            'additionalProp2' => [],
                        ],
                    ],
                    'group__2' => [
                        'attributes' => [
                            'additionalProp3' => [],
                        ],
                    ],
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
        $groupsValidator = new GroupsValidator();

        $this->expectNotToPerformAssertions();
        $groupsValidator->execute($data);
    }
}
