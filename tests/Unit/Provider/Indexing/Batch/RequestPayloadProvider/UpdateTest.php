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
use Klevu\PhpSDK\Model\Indexing\UpdateOperations;
use Klevu\PhpSDK\Model\IteratorInterface;
use Klevu\PhpSDK\Provider\Indexing\Batch\RequestPayloadProvider\Update;
use Klevu\PhpSDK\Provider\Indexing\Batch\RequestPayloadProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Update::class)]
class UpdateTest extends TestCase
{
    #[Test]
    public function testImplementsInterface(): void
    {
        $requestPayloadProvider = new Update();

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
                '',
            ],
            [
                new UpdateIterator([
                    $updateFactory->create([
                        'record_id' => 'PRODUCT001',
                        'op' => 'add',
                        'path' => '/foo/bar',
                        'value' => 'baz',
                    ]),
                ]),
                json_encode([
                    'PRODUCT001' => [
                        [
                            'op' => 'add',
                            'path' => '/foo/bar',
                            'value' => 'baz',
                        ],
                    ],
                ]),
            ],
            [
                new UpdateIterator([
                    $updateFactory->create([
                        'record_id' => 'PRODUCT001',
                        'op' => UpdateOperations::REMOVE,
                        'path' => '/foo/bar',
                        'value' => null,
                    ]),
                    $updateFactory->create([
                        'record_id' => 'PRODUCT003',
                        'op' => UpdateOperations::ADD,
                        'path' => '/',
                        'value' => [
                            'wom' => 'bat',
                            'a' => [
                                'b' => [
                                    'c',
                                ],
                            ],
                        ],
                    ]),
                    $updateFactory->create([
                        'record_id' => 'PRODUCT001',
                        'value' => false,
                        'op' => UpdateOperations::REPLACE,
                        'path' => '/a/b/c',
                    ]),
                    $updateFactory->create([
                        'record_id' => 'PRODUCT002',
                        'op' => '',
                        'path' => '@1234',
                        'value' => 'foo',
                    ]),
                ]),
                json_encode([
                    'PRODUCT001' => [
                        [
                            'op' => 'remove',
                            'path' => '/foo/bar',
                        ],
                        [
                            'op' => 'replace',
                            'path' => '/a/b/c',
                            'value' => false,
                        ],
                    ],
                    'PRODUCT003' => [
                        [
                            'op' => 'add',
                            'path' => '/',
                            'value' => [
                                'wom' => 'bat',
                                'a' => [
                                    'b' => [
                                        'c',
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
        $requestPayloadProvider = new Update();

        $result = $requestPayloadProvider->get($records);

        $this->assertSame(
            expected: $expectedResult,
            actual: $result,
        );
    }
}
