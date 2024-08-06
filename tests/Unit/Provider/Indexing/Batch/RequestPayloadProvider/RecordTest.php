<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Provider\Indexing\Batch\RequestPayloadProvider;

use Klevu\PhpSDK\Model\Indexing\Record as RecordModel;
use Klevu\PhpSDK\Model\Indexing\RecordIterator;
use Klevu\PhpSDK\Model\Indexing\UpdateFactory;
use Klevu\PhpSDK\Model\Indexing\UpdateIterator;
use Klevu\PhpSDK\Model\IteratorInterface;
use Klevu\PhpSDK\Provider\Indexing\Batch\RequestPayloadProvider\Record;
use Klevu\PhpSDK\Provider\Indexing\Batch\RequestPayloadProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Record::class)]
class RecordTest extends TestCase
{
    #[Test]
    public function testImplementsInterface(): void
    {
        $requestPayloadProvider = new Record();

        $this->assertInstanceOf(
            expected: RequestPayloadProviderInterface::class,
            actual: $requestPayloadProvider,
        );
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testGet(): array
    {
        $updateFactory = new UpdateFactory();

        return [
            [
                new RecordIterator([
                    new RecordModel(
                        id: 'category_1',
                        type: 'KLEVU_CATEGORY',
                    ),
                ]),
                json_encode([
                    [
                        'id' => 'category_1',
                        'type' => 'KLEVU_CATEGORY',
                        'attributes' => [],
                    ],
                ]),
            ],
            [
                new UpdateIterator([
                    $updateFactory->create([
                        'op' => 'add',
                        'path' => '/foo/bar',
                        'value' => 'baz',
                    ]),
                ]),
                '',
            ],
            [
                new RecordIterator([
                    new RecordModel(
                        id: '123-456',
                        type: 'KLEVU_PRODUCT_RETURN_PARENT',
                        relations: [
                            'categories' => [
                                'type' => 'KLEVU_CATEGORY',
                                'values' => [
                                    'category_1',
                                ],
                            ],
                            'parentProduct' => [
                                'type' => 'KLEVU_PRODUCT',
                                'values' => [
                                    '123',
                                ],
                            ],
                        ],
                        attributes: [
                            'sku' => 'Test Child',
                        ],
                    ),
                    new RecordModel(
                        id: 'category_1',
                        type: 'KLEVU_CATEGORY',
                    ),
                    new RecordModel(
                        id: '123',
                        type: 'KLEVU_PRODUCT',
                        channels: [
                            'default' => [
                                'attributes' => [
                                    'name' => 'Parent Product',
                                ],
                            ],
                        ],
                    ),
                    new RecordModel(
                        id: '123',
                        type: 'KLEVU_PRODUCT',
                        groups: [
                            'grp_1' => [
                                'attributes' => [
                                    'price' => [
                                        'GBP' => [
                                            'salePrice' => 10.0,
                                            'defaultPrice' => 11.0,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        channels: [
                            'fr_FR' => [
                                'attributes' => [
                                    'name' => [
                                        'default' => 'Product',
                                    ],
                                ],
                                'groups' => [
                                    'grp_1' => [
                                        'attributes' => [
                                            'price' => [
                                                'GBP' => [
                                                    'salePrice' => 12.0,
                                                    'defaultPrice' => 13.0,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ),
                ]),
                json_encode([
                    [
                        'id' => '123-456',
                        'type' => 'KLEVU_PRODUCT_RETURN_PARENT',
                        'relations' => [
                            'categories' => [
                                'type' => 'KLEVU_CATEGORY',
                                'values' => [
                                    'category_1',
                                ],
                            ],
                            'parentProduct' => [
                                'type' => 'KLEVU_PRODUCT',
                                'values' => [
                                    '123',
                                ],
                            ],
                        ],
                        'attributes' => [
                            'sku' => 'Test Child',
                        ],
                    ],
                    [
                        'id' => 'category_1',
                        'type' => 'KLEVU_CATEGORY',
                        'attributes' => [],
                    ],
                    [
                        'id' => '123',
                        'type' => 'KLEVU_PRODUCT',
                        'attributes' => [],
                        'channels' => [
                            'default' => [
                                'attributes' => [
                                    'name' => 'Parent Product',
                                ],
                            ],
                        ],
                    ],
                    [
                        'id' => '123',
                        'type' => 'KLEVU_PRODUCT',
                        'attributes' => [],
                        'groups' => [
                            'grp_1' => [
                                'attributes' => [
                                    'price' => [
                                        'GBP' => [
                                            'salePrice' => 10.0,
                                            'defaultPrice' => 11.0,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'channels' => [
                            'fr_FR' => [
                                'attributes' => [
                                    'name' => [
                                        'default' => 'Product',
                                    ],
                                ],
                                'groups' => [
                                    'grp_1' => [
                                        'attributes' => [
                                            'price' => [
                                                'GBP' => [
                                                    'salePrice' => 12.0,
                                                    'defaultPrice' => 13.0,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]),
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testGet')]
    public function testGet(
        IteratorInterface $records,
        string $expectedResult,
    ): void {
        $requestPayloadProvider = new Record();

        $result = $requestPayloadProvider->get($records);

        $this->assertSame(
            expected: $expectedResult,
            actual: $result,
        );
    }
}
