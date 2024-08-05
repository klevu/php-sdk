<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Model\Indexing;

use Klevu\PhpSDK\Model\Indexing\Update;
use Klevu\PhpSDK\Model\Indexing\UpdateFactory;
use Klevu\PhpSDK\Model\Indexing\UpdateOperations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(UpdateFactory::class)]
class UpdateFactoryTest extends TestCase
{
    /**
     * @return mixed[][]
     */
    public static function dataProvider_testCreate_Invalid(): array
    {
        return [
            [
                ['record_id' => null],
                \TypeError::class,
            ],
            [
                ['record_id' => 42],
                \TypeError::class,
            ],
            [
                ['record_id' => 3.14],
                \TypeError::class,
            ],
            [
                ['record_id' => true],
                \TypeError::class,
            ],
            [
                ['record_id' => ['PRODUCT001']],
                \TypeError::class,
            ],
            [
                ['record_id' => (object)['record_id' => 'PRODUCT001']],
                \TypeError::class,
            ],

            [
                ['op' => null],
                \TypeError::class,
            ],
            [
                ['op' => 42],
                \TypeError::class,
            ],
            [
                ['op' => 3.14],
                \TypeError::class,
            ],
            [
                ['op' => true],
                \TypeError::class,
            ],
            [
                ['op' => ['add']],
                \TypeError::class,
            ],
            [
                ['op' => (object)['op' => 'add']],
                \TypeError::class,
            ],

            [
                ['path' => 42],
                \TypeError::class,
            ],
            [
                ['path' => 3.14],
                \TypeError::class,
            ],
            [
                ['path' => true],
                \TypeError::class,
            ],
            [
                ['path' => ['foo']],
                \TypeError::class,
            ],
            [
                ['path' => (object)['op' => 'add']],
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
        $updateFactory = new UpdateFactory();

        $this->expectException($expectedExceptionFqcn);
        $updateFactory->create($data);
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testCreate_Valid(): array
    {
        return [
            [
                ['record_id' => 'PRODUCT001'],
                [
                    Update::FIELD_RECORD_ID => 'PRODUCT001',
                    Update::FIELD_OP => '',
                    Update::FIELD_PATH => null,
                    Update::FIELD_VALUE => null,
                ],
            ],
            [
                ['op' => 'add'],
                [
                    Update::FIELD_RECORD_ID => '',
                    Update::FIELD_OP => 'add',
                    Update::FIELD_PATH => null,
                    Update::FIELD_VALUE => null,
                ],
            ],
            [
                ['op' => 'remove'],
                [
                    Update::FIELD_RECORD_ID => '',
                    Update::FIELD_OP => 'remove',
                    Update::FIELD_PATH => null,
                    Update::FIELD_VALUE => null,
                ],
            ],
            [
                ['op' => 'replace'],
                [
                    Update::FIELD_RECORD_ID => '',
                    Update::FIELD_OP => 'replace',
                    Update::FIELD_PATH => null,
                    Update::FIELD_VALUE => null,
                ],
            ],
            [
                ['op' => UpdateOperations::ADD],
                [
                    Update::FIELD_RECORD_ID => '',
                    Update::FIELD_OP => 'add',
                    Update::FIELD_PATH => null,
                    Update::FIELD_VALUE => null,
                ],
            ],
            [
                ['op' => UpdateOperations::REMOVE],
                [
                    Update::FIELD_RECORD_ID => '',
                    Update::FIELD_OP => 'remove',
                    Update::FIELD_PATH => null,
                    Update::FIELD_VALUE => null,
                ],
            ],
            [
                ['op' => UpdateOperations::REPLACE],
                [
                    Update::FIELD_RECORD_ID => '',
                    Update::FIELD_OP => 'replace',
                    Update::FIELD_PATH => null,
                    Update::FIELD_VALUE => null,
                ],
            ],
            [
                ['path' => null],
                [
                    Update::FIELD_RECORD_ID => '',
                    Update::FIELD_OP => '',
                    Update::FIELD_PATH => null,
                    Update::FIELD_VALUE => null,
                ],
            ],
            [
                ['path' => ''],
                [
                    Update::FIELD_RECORD_ID => '',
                    Update::FIELD_OP => '',
                    Update::FIELD_PATH => '',
                    Update::FIELD_VALUE => null,
                ],
            ],
            [
                ['path' => '/foo/bar.123/@~abc'],
                [
                    Update::FIELD_RECORD_ID => '',
                    Update::FIELD_OP => '',
                    Update::FIELD_PATH => '/foo/bar.123/@~abc',
                    Update::FIELD_VALUE => null,
                ],
            ],
            [
                [
                    'record_id' => 'PRODUCT001',
                    'op' => 'add',
                    'path' => '/attributes/prices',
                    'value' => [
                        'specialPrice' => 1.23,
                    ],
                ],
                [
                    Update::FIELD_RECORD_ID => 'PRODUCT001',
                    Update::FIELD_OP => 'add',
                    Update::FIELD_PATH => '/attributes/prices',
                    Update::FIELD_VALUE => [
                        'specialPrice' => 1.23,
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
        $updateFactory = new UpdateFactory();

        $update = $updateFactory->create($data);
        $this->assertInstanceOf(Update::class, $update);
        $this->assertSame(
            expected: $expectedResult,
            actual: $update->toArray(),
        );
    }
}
