<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Model\Indexing;

use Klevu\PhpSDK\Model\Indexing\Record;
use Klevu\PhpSDK\Model\Indexing\RecordFactory;
use Klevu\PhpSDK\Model\Indexing\RecordIterator;
use Klevu\PhpSDK\Test\Unit\Model\AbstractIteratorTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RecordIterator::class)]
class RecordIteratorTest extends AbstractIteratorTestCase
{
    /**
     * @var string
     */
    protected string $iteratorFqcn = RecordIterator::class;
    /**
     * @var string
     */
    protected string $itemFqcn = Record::class;

    /**
     * @return Record[][][]
     */
    public static function dataProvider_valid(): array
    {
        $recordFactory = new RecordFactory();

        return [
            [
                [
                    new Record(
                        id: '12345',
                        type: 'KLEVU_PRODUCT',
                    ),
                    $recordFactory->create([
                        'id' => '12345',
                        'type' => 'KLEVU_PRODUCT',
                        'relations' => null,
                        'attributes' => [],
                        'channels' => null,
                    ]),
                ],
            ],
            [
                [
                    new Record(
                        id: '12345',
                        type: 'KLEVU_PRODUCT',
                    ),
                ],
            ],
            [
                [
                    new Record(
                        id: '12345',
                        type: 'KLEVU_PRODUCT',
                        relations: [
                            'categories' => [
                                'type' => 'KLEVU_CATEGORY',
                                'values' => [
                                    'foo',
                                ],
                            ],
                        ],
                        attributes: [
                            'name' => [
                                'default' => 'Foo',
                            ],
                        ],
                        channels: [
                            'default' => [
                                'foo' => 'bar',
                            ],
                        ],
                    ),
                    $recordFactory->create([
                        'id' => '12345',
                        'type' => 'KLEVU_PRODUCT',
                        'relations' => [
                            'categories' => [
                                'type' => 'KLEVU_CATEGORY',
                                'values' => [
                                    'foo',
                                ],
                            ],
                        ],
                        'attributes' => [
                            'name' => [
                                'default' => 'Foo',
                            ],
                        ],
                        'channels' => [
                            'default' => [
                                'foo' => 'bar',
                            ],
                        ],
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
                    new Record(
                        id: '123-456',
                        type: 'KLEVU_PRODUCT',
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
                    new Record(
                        id: '123-456',
                        type: 'KLEVU_PRODUCT',
                    ),
                    new Record(
                        id: '123-456',
                        type: 'KLEVU_CATEGORY',
                    ),
                ],
                static fn (Record $record): bool => $record->getType() !== 'KLEVU_PRODUCT',
                [
                    new Record(
                        id: '123-456',
                        type: 'KLEVU_CATEGORY',
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
        $recordFactory = new RecordFactory();
        $expectedRecords = [
            $recordFactory->create([
                'id' => '123-456',
                'type' => 'KLEVU_PRODUCT',
                'attributes' => [
                    'updated_type' => 'KLEVU_PRODUCT',
                ],
            ]),
            $recordFactory->create([
                'id' => '123-456',
                'type' => 'KLEVU_CATEGORY',
                'attributes' => [
                    'updated_type' => 'KLEVU_CATEGORY',
                ],
            ]),
        ];

        return [
            [
                [
                    new Record(
                        id: '123-456',
                        type: 'KLEVU_PRODUCT',
                    ),
                    new Record(
                        id: '123-456',
                        type: 'KLEVU_CATEGORY',
                    ),
                ],
                static function (Record $record): void {
                    $record->addAttribute('updated_type', $record->getType());
                },
                $expectedRecords,
            ],
        ];
    }
}
