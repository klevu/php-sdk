<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Model\Indexing;

use Klevu\PhpSDK\Model\Indexing\Attribute;
use Klevu\PhpSDK\Model\Indexing\AttributeFactory;
use Klevu\PhpSDK\Model\Indexing\DataType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AttributeFactory::class)]
class AttributeFactoryTest extends TestCase
{
    /**
     * @return mixed[][]
     */
    public static function dataProvider_testCreate_Invalid(): array
    {
        return [
            [
                [],
                \TypeError::class,
            ],
            [
                [
                    'id' => 'test_attribute',
                    'datatype' => 'STRING',
                ],
                \TypeError::class,
            ],
            [
                [
                    'attributeName' => 'test_attribute',
                    'datatype' => 'STRING',
                    'label' => true,
                ],
                \TypeError::class,
            ],
            [
                [
                    'attributeName' => 'test_attribute',
                    'datatype' => 'STRING',
                    'label' => [
                        'default' => true,
                    ],
                ],
                \TypeError::class,
            ],
            [
                [
                    'attributeName' => 'test_attribute',
                    'datatype' => 'STRING',
                    'label' => [
                        0 => 'Foo',
                        1 => 'Bar',
                    ],
                ],
                \TypeError::class,
            ],
            [
                [
                    'attributeName' => 'test_attribute',
                    'datatype' => DataType::STRING,
                    'searchable' => 'Foo',
                ],
                \TypeError::class,
            ],
            [
                [
                    'attributeName' => 'test_attribute',
                    'datatype' => DataType::STRING,
                    'filterable' => 'Foo',
                ],
                \TypeError::class,
            ],
            [
                [
                    'attributeName' => 'test_attribute',
                    'datatype' => DataType::STRING,
                    'returnable' => 'Foo',
                ],
                \TypeError::class,
            ],
        ];
    }

    /**
     * @param mixed[] $data
     * @param class-string<\Throwable> $expectedExceptionFqcn
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testCreate_Invalid')]
    public function testCreate_Invalid(
        array $data,
        string $expectedExceptionFqcn,
    ): void {
        $attributeFactory = new AttributeFactory();

        $this->expectException($expectedExceptionFqcn);
        $attributeFactory->create($data);
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testCreate_Valid(): array
    {
        return [
            [
                [
                    'attributeName' => 'test_attribute',
                    'datatype' => 'STRING',
                ],
                [
                    'attributeName' => 'test_attribute',
                    'datatype' => 'STRING',
                    'label' => [],
                    'searchable' => true,
                    'filterable' => true,
                    'returnable' => true,
                    'immutable' => false,
                ],
            ],
            [
                [
                    'attributeName' => 'test_attribute',
                    'datatype' => DataType::STRING,
                ],
                [
                    'attributeName' => 'test_attribute',
                    'datatype' => 'STRING',
                    'label' => [],
                    'searchable' => true,
                    'filterable' => true,
                    'returnable' => true,
                    'immutable' => false,
                ],
            ],
            [
                [
                    'attributeName' => 'test_attribute',
                    'datatype' => DataType::STRING,
                    'label' => 'Test Attribute',
                ],
                [
                    'attributeName' => 'test_attribute',
                    'datatype' => 'STRING',
                    'label' => [
                        'default' => 'Test Attribute',
                    ],
                    'searchable' => true,
                    'filterable' => true,
                    'returnable' => true,
                    'immutable' => false,
                ],
            ],
            [
                [
                    'attributeName' => 'test_attribute',
                    'datatype' => DataType::STRING,
                    'label' => [
                        'foo' => 'Test Attribute',
                    ],
                ],
                [
                    'attributeName' => 'test_attribute',
                    'datatype' => 'STRING',
                    'label' => [
                        'foo' => 'Test Attribute',
                    ],
                    'searchable' => true,
                    'filterable' => true,
                    'returnable' => true,
                    'immutable' => false,
                ],
            ],
            [
                [
                    'attributeName' => 'test_attribute',
                    'datatype' => DataType::STRING,
                    'label' => [
                        'foo' => 'Test Attribute',
                    ],
                    'searchable' => false,
                    'filterable' => false,
                    'returnable' => false,
                    'immutable' => false,
                ],
                [
                    'attributeName' => 'test_attribute',
                    'datatype' => 'STRING',
                    'label' => [
                        'foo' => 'Test Attribute',
                    ],
                    'searchable' => false,
                    'filterable' => false,
                    'returnable' => false,
                    'immutable' => false,
                ],
            ],
        ];
    }

    /**
     * @param mixed[] $data
     * @param mixed[] $expectedResult
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testCreate_Valid')]
    public function testCreate_Valid(
        array $data,
        array $expectedResult,
    ): void {
        $attributeFactory = new AttributeFactory();

        $attribute = $attributeFactory->create($data);
        $this->assertInstanceOf(Attribute::class, $attribute);
        $this->assertSame(
            expected: $expectedResult,
            actual: $attribute->toArray(),
        );
    }
}
