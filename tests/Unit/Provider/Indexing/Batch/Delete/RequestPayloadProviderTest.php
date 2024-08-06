<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Provider\Indexing\Batch\Delete;

use Klevu\PhpSDK\Model\Indexing\Record;
use Klevu\PhpSDK\Model\Indexing\RecordIterator;
use Klevu\PhpSDK\Model\Indexing\UpdateFactory;
use Klevu\PhpSDK\Model\Indexing\UpdateIterator;
use Klevu\PhpSDK\Model\IteratorInterface;
use Klevu\PhpSDK\Provider\Indexing\Batch\Delete\RequestPayloadProvider;
use Klevu\PhpSDK\Provider\Indexing\Batch\RequestPayloadProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RequestPayloadProvider::class)]
class RequestPayloadProviderTest extends TestCase
{
    #[Test]
    public function testImplementsInterface(): void
    {
        $requestPayloadProvider = new RequestPayloadProvider();

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
                    new Record(
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
                    new Record(
                        id: 'category_1',
                        type: 'KLEVU_CATEGORY',
                    ),
                    new Record(
                        id: '123',
                        type: 'KLEVU_PRODUCT',
                        channels: [
                            'default' => [
                                'name' => 'Parent Product',
                            ],
                        ],
                    ),
                ]),
                json_encode([
                    'ids' => [
                        '123-456',
                        'category_1',
                        '123',
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
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testGet')]
    public function testGet(
        IteratorInterface $records,
        string $expectedResult,
    ): void {
        $requestPayloadProvider = new RequestPayloadProvider();

        $result = $requestPayloadProvider->get($records);

        $this->assertSame(
            expected: $expectedResult,
            actual: $result,
        );
    }
}
