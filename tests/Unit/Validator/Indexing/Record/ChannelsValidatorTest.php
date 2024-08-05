<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Validator\Indexing\Record;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Validator\Indexing\Record\ChannelsValidator;
use Klevu\PhpSDK\Validator\ValidatorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ChannelsValidator::class)]
class ChannelsValidatorTest extends TestCase
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
        $channelsValidator = new ChannelsValidator();

        $this->expectException(InvalidTypeValidationException::class);
        try {
            $channelsValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Channels',
                haystack: $error,
            );
            throw $exception;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_InvalidChannelName(): array
    {
        return [
            [
                [
                    '' => [
                        'error' => 'Empty Channel',
                    ],
                ],
                [
                    0,
                ],
                [
                    '[] Channel Name is required',
                ],
            ],
            [
                [
                    1 => [
                        'error' => 'Numeric channel name',
                    ],
                ],
                [
                    0,
                ],
                [
                    '[1] Channel Name must be string, received int',
                ],
            ],
            [
                [
                    0 => [
                        'error' => 'Numeric channel name',
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
                    '[0] Channel Name must be string, received int',
                    '[] Channel Name is required',
                    '[ ] Channel Name is required',
                    '[fr-FR] Channel Name must be alphanumeric, and can include underscores (_)',
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
    #[DataProvider('dataProvider_testExecute_InvalidChannelName')]
    public function testExecute_InvalidChannelName(
        array $data,
        array $invalidRowIndexes,
        array $expectedErrors,
    ): void {
        $channelsValidator = new ChannelsValidator();

        $this->expectException(InvalidDataValidationException::class);
        $this->expectExceptionMessage('Invalid row(s) for channels: ' . implode(', ', $invalidRowIndexes));
        try {
            $channelsValidator->execute($data);
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
    public static function dataProvider_testExecute_InvalidChannelData(): array
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
                    '[null] Channel data must be array, received null',
                    '[str] Channel data must be array, received string',
                    '[int] Channel data must be array, received int',
                    '[float] Channel data must be array, received float',
                    '[bool] Channel data must be array, received bool',
                    '[obj] Channel data must be array, received stdClass',
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
    #[DataProvider('dataProvider_testExecute_InvalidChannelData')]
    public function testExecute_InvalidChannelData(
        array $data,
        array $invalidRowIndexes,
        array $expectedErrors,
    ): void {
        $mockAttributesValidator = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAttributesValidator->expects($this->never())
            ->method('execute');

        $channelsValidator = new ChannelsValidator(
            attributesValidator: $mockAttributesValidator,
        );

        $this->expectException(InvalidDataValidationException::class);
        $this->expectExceptionMessage('Invalid row(s) for channels: ' . implode(', ', $invalidRowIndexes));
        try {
            $channelsValidator->execute($data);
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
            // phpcs:disable Generic.Files.LineLength.TooLong
            [
                [
                    'CHANNEL1' => [
                        'attributes' => [
                            '' => [],
                            ' ' => [],
                            'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa' => [],
                            '   aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa   ' => [],
                        ],
                    ],
                    'channel__2' => [
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
                    '[CHANNEL1] '
                        . '[] Attribute Name is required; '
                        . '[ ] Attribute Name is required; '
                        . '[aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa] Attribute Name must be less than or equal to 200 characters; '
                        . '[   aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa   ] Attribute Name must be less than or equal to 200 characters',
                    '[channel__2] '
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
    #[DataProvider('dataProvider_testExecute_InvalidAttributesData')]
    public function testExecute_InvalidAttributesData(
        array $data,
        array $invalidRowIndexes,
        array $expectedErrors,
    ): void {
        $channelsValidator = new ChannelsValidator();

        $this->expectException(InvalidDataValidationException::class);
        $this->expectExceptionMessage('Invalid row(s) for channels: ' . implode(', ', $invalidRowIndexes));
        try {
            $channelsValidator->execute($data);
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
    public static function dataProvider_testExecute_InvalidGroupsData(): array
    {
        return [
            [
                [
                    'str' => [
                        'groups' => 'foo',
                    ],
                    'int' => [
                        'groups' => 42,
                    ],
                    'float' => [
                        'groups' => 3.14,
                    ],
                    'bool' => [
                        'groups' => true,
                    ],
                    'array' => [
                        'groups' => [],
                    ],
                    'obj' => [
                        'groups' => (object)[
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
                    '[str] Groups must be array or null, received string',
                    '[int] Groups must be array or null, received int',
                    '[float] Groups must be array or null, received float',
                    '[bool] Groups must be array or null, received bool',
                    '[obj] Groups must be array or null, received stdClass',
                ],
            ],
            [
                [
                    'fr_FR' => [
                        'groups' => [
                            'fr-FR' => [
                                'attributes' => [
                                    'attribute' => 'value',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    0,
                ],
                [
                    '[fr_FR] [fr-FR] Group Name must be alphanumeric, and can include underscores (_)',
                ],
            ],
            [
                [
                    'fr_FR' => [
                        'groups' => [
                            'fr_FR' => [
                                'attributes' => 'foo',
                            ],
                        ],
                    ],
                ],
                [
                    0,
                ],
                [
                    '[fr_FR] [fr_FR] Attributes must be array, received string',
                ],
            ],
            // phpcs:disable Generic.Files.LineLength.TooLong
            [
                [
                    'CHANNEL1' => [
                        'groups' => [
                            'fr_FR' => [
                                'attributes' => [
                                    '' => [],
                                    ' ' => [],
                                    'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa' => [],
                                    '   aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa   ' => [],
                                ],
                            ],
                        ],
                    ],
                    'channel__2' => [
                        'groups' => [
                            'fr_FR' => [
                                'attributes' => [
                                    '_foo' => [],
                                    'foo_' => [],
                                    '_foo_' => [],
                                ],
                            ],
                            'en_GB' => [
                                'attributes' => [
                                    'product-name' => [],
                                    'foo!bar' => [],
                                    'テスト属性' => [],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    0,
                    1,
                ],
                [
                    '[CHANNEL1] '
                        . '[fr_FR] '
                            . '[] Attribute Name is required; '
                            . '[ ] Attribute Name is required; '
                            . '[aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa] Attribute Name must be less than or equal to 200 characters; '
                            . '[   aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa   ] Attribute Name must be less than or equal to 200 characters',
                    '[channel__2] '
                        . '[fr_FR] '
                            . '[_foo] Attribute Name must be alphanumeric, can include underscores (_) but cannot start or end with an underscore; '
                            . '[foo_] Attribute Name must be alphanumeric, can include underscores (_) but cannot start or end with an underscore; '
                            . '[_foo_] Attribute Name must be alphanumeric, can include underscores (_) but cannot start or end with an underscore; '
                        . '[en_GB] '
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
    #[DataProvider('dataProvider_testExecute_InvalidGroupsData')]
    public function testExecute_InvalidGroupsData(
        array $data,
        array $invalidRowIndexes,
        array $expectedErrors,
    ): void {
        $channelsValidator = new ChannelsValidator();

        $this->expectException(InvalidDataValidationException::class);
        $this->expectExceptionMessage('Invalid row(s) for channels: ' . implode(', ', $invalidRowIndexes));
        try {
            $channelsValidator->execute($data);
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
                    '_my_new_channel_' => [
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
                    'CHANNEL1' => [
                        'attributes' => [
                            'additionalProp1' => [],
                            'additionalProp2' => [],
                        ],
                    ],
                    'channel__2' => [
                        'attributes' => [
                            'additionalProp3' => [],
                        ],
                    ],
                ],
            ],
            [
                [
                    'CHANNEL1' => [
                        'attributes' => [
                            'additionalProp1' => [],
                            'additionalProp2' => [],
                        ],
                        'groups' => [
                            'attributes' => [
                                'foo' => 'bar',
                            ],
                        ],
                    ],
                    'channel__2' => [
                        'attributes' => [
                            'additionalProp3' => [],
                        ],
                        'groups' => [
                            'attributes' => [
                                'wom' => [
                                    'bat',
                                ],
                            ],
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
        $channelsValidator = new ChannelsValidator();

        $this->expectNotToPerformAssertions();
        $channelsValidator->execute($data);
    }
}
