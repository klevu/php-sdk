<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Model\Indexing;

use Klevu\PhpSDK\Model\Indexing\Record;
use Klevu\PhpSDK\Model\Indexing\RecordFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RecordFactory::class)]
class RecordFactoryTest extends TestCase
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
                    'id' => 12345,
                    'type' => 'KLEVU_PRODUCT',
                ],
                \TypeError::class,
            ],
            [
                [
                    'id' => '12345',
                    'type' => [
                        'KLEVU_PRODUCT',
                    ],
                ],
                \TypeError::class,
            ],
            [
                [
                    'id' => '12345',
                    'type' => 'KLEVU_PRODUCT',
                    'relations' => true,
                ],
                \TypeError::class,
            ],
            [
                [
                    'id' => '12345',
                    'type' => 'KLEVU_PRODUCT',
                    'attributes' => 3.14,
                ],
                \TypeError::class,
            ],
            [
                [
                    'id' => '12345',
                    'type' => 'KLEVU_PRODUCT',
                    'channels' => (object)['foo' => 'bar'],
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
        $recordFactory = new RecordFactory();

        $this->expectException($expectedExceptionFqcn);
        $recordFactory->create($data);
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testCreate_Valid(): array
    {
        return [
            [
                [
                    'id' => '12345',
                    'type' => 'KLEVU_PRODUCT',
                ],
                [
                    'id' => '12345',
                    'type' => 'KLEVU_PRODUCT',
                    'relations' => null,
                    'attributes' => [],
                    'groups' => null,
                    'channels' => null,
                ],
            ],
            [
                [
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
                            'attributes' => [
                                'foo' => 'bar',
                            ],
                        ],
                    ],
                ],
                [
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
                    'groups' => null,
                    'channels' => [
                        'default' => [
                            'attributes' => [
                                'foo' => 'bar',
                            ],
                        ],
                    ],
                ],
            ],
            [
                [
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
                    'groups' => [
                        'fr_FR' => [
                            'attributes' => [
                                'wom' => [
                                    'bat',
                                ],
                            ],
                        ],
                    ],
                    'channels' => [
                        'default' => [
                            'attributes' => [
                                'foo' => 'bar',
                            ],
                            'groups' => [
                                'fr_FR' => [
                                    'attributes' => [
                                        'wom' => 'bat',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
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
                    'groups' => [
                        'fr_FR' => [
                            'attributes' => [
                                'wom' => [
                                    'bat',
                                ],
                            ],
                        ],
                    ],
                    'channels' => [
                        'default' => [
                            'attributes' => [
                                'foo' => 'bar',
                            ],
                            'groups' => [
                                'fr_FR' => [
                                    'attributes' => [
                                        'wom' => 'bat',
                                    ],
                                ],
                            ],
                        ],
                    ],
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
        $recordFactory = new RecordFactory();

        $record = $recordFactory->create($data);
        $this->assertInstanceOf(Record::class, $record);
        $this->assertSame(
            expected: $expectedResult,
            actual: $record->toArray(),
        );
    }
}
