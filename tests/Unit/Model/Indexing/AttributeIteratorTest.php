<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Model\Indexing;

use Klevu\PhpSDK\Model\Indexing\Attribute;
use Klevu\PhpSDK\Model\Indexing\AttributeFactory;
use Klevu\PhpSDK\Model\Indexing\AttributeIterator;
use Klevu\PhpSDK\Model\Indexing\DataType;
use Klevu\PhpSDK\Test\Unit\Model\AbstractIteratorTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AttributeIterator::class)]
class AttributeIteratorTest extends AbstractIteratorTestCase
{
    /**
     * @var string
     */
    protected string $iteratorFqcn = AttributeIterator::class;
    /**
     * @var string
     */
    protected string $itemFqcn = Attribute::class;

    /**
     * @return Attribute[][][]
     */
    public static function dataProvider_valid(): array
    {
        $attributeFactory = new AttributeFactory();

        return [
            [
                [
                    new Attribute(
                        attributeName: 'testAttribute1',
                        datatype: DataType::DATETIME->value,
                    ),
                    $attributeFactory->create([
                        'attributeName' => 'testAttribute2',
                        'datatype' => DataType::STRING->value,
                        'searchable' => false,
                    ]),
                ],
            ],
        ];
    }

    /**
     * @return mixed[][][]
     */
    public static function dataProvider_invalid(): array
    {
        return [
            [
                [
                    (object)['foo' => 'bar'],
                ],
            ],
            [
                [
                    'foo',
                ],
            ],
            [
                [
                    12345,
                ],
            ],
            [
                [
                    new Attribute(
                        attributeName: 'testAttribute1',
                        datatype: DataType::DATETIME->value,
                    ),
                    null,
                ],
            ],
        ];
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_filter(): array
    {
        return [
            [
                [
                    new Attribute(
                        attributeName: 'testAttribute1',
                        datatype: DataType::DATETIME->value,
                    ),
                    new Attribute(
                        attributeName: 'testAttribute2',
                        datatype: DataType::STRING->value,
                    ),
                ],
                static fn (Attribute $attribute): bool => $attribute->getDatatype() !== DataType::STRING->value,
                [
                    new Attribute(
                        attributeName: 'testAttribute1',
                        datatype: DataType::DATETIME->value,
                    ),
                ],
            ],
        ];
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_walk(): array
    {
        $expectedAttributes = [
            new Attribute(
                attributeName: 'testAttribute1',
                datatype: DataType::DATETIME->value,
            ),
            new Attribute(
                attributeName: 'testAttribute2',
                datatype: DataType::STRING->value,
            ),
        ];
        $expectedAttributes[0]->addLabel('Updated in walk', 'foo');
        $expectedAttributes[1]->addLabel('Updated in walk', 'foo');

        return [
            [
                [
                    new Attribute(
                        attributeName: 'testAttribute1',
                        datatype: DataType::DATETIME->value,
                    ),
                    new Attribute(
                        attributeName: 'testAttribute2',
                        datatype: DataType::STRING->value,
                    ),
                ],
                static function (Attribute $attribute): void {
                    $attribute->addLabel('Updated in walk', 'foo');
                },
                $expectedAttributes,
            ],
        ];
    }
}
