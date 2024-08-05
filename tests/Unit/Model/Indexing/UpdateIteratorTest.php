<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Model\Indexing;

use Klevu\PhpSDK\Api\Model\Indexing\UpdateInterface;
use Klevu\PhpSDK\Model\Indexing\Update;
use Klevu\PhpSDK\Model\Indexing\UpdateFactory;
use Klevu\PhpSDK\Model\Indexing\UpdateIterator;
use Klevu\PhpSDK\Test\Unit\Model\AbstractIteratorTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UpdateIterator::class)]
class UpdateIteratorTest extends AbstractIteratorTestCase
{
    /**
     * @var string
     */
    protected string $iteratorFqcn = UpdateIterator::class;
    /**
     * @var string
     */
    protected string $itemFqcn = Update::class;

    /**
     * @return mixed[][]
     */
    public static function dataProvider_valid(): array
    {
        $updateFactory = new UpdateFactory();

        return [
            [
                [
                    new Update(),
                    $updateFactory->create([
                        'record_id' => 'PRODUCT001',
                        'op' => 'add',
                        'path' => '/foo/bar',
                        'value' => (object)[
                            'foo' => 'bar',
                        ],
                    ]),
                ],
            ],
            [
                [
                    $updateFactory->create([
                        'record_id' => 'PRODUCT001',
                        'op' => 'foo',
                        'path' => '\\\\bar',
                        'value' => null,
                    ]),
                ],
            ],
        ];
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_invalid(): array
    {
        return [
            [
                [
                    null,
                ],
            ],
            [
                [
                    'foo',
                ],
            ],
            [
                [
                    42,
                ],
            ],
            [
                [
                    3.14,
                ],
            ],
            [
                [
                    true,
                ],
            ],
            [
                [
                    ['foo' => 'bar'],
                ],
            ],
            [
                [
                    (object)['foo' => 'bar'],
                ],
            ],
        ];
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_filter(): array
    {
        $updateFactory = new UpdateFactory();

        return [
            [
                [
                    $updateFactory->create([
                        'record_id' => 'PRODUCT001',
                        'op' => 'add',
                        'path' => '/foo/bar',
                        'value' => null,
                    ]),
                    $updateFactory->create([
                        'record_id' => 'PRODUCT001',
                        'op' => 'replace',
                        'path' => '/foo/bar',
                        'value' => null,
                    ]),
                    $updateFactory->create([
                        'record_id' => 'PRODUCT001',
                        'op' => 'add',
                        'path' => '/wom/bat',
                        'value' => null,
                    ]),
                ],
                static fn (UpdateInterface $update): bool => $update->getOp() !== 'add',
                [
                    $updateFactory->create([
                        'record_id' => 'PRODUCT001',
                        'op' => 'replace',
                        'path' => '/foo/bar',
                        'value' => null,
                    ]),
                ],
            ],
        ];
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_walk(): array
    {
        $updateFactory = new UpdateFactory();

        return [
            [
                [
                    $updateFactory->create([
                        'record_id' => 'PRODUCT001',
                        'op' => 'add',
                        'path' => '/foo/bar',
                        'value' => null,
                    ]),
                    $updateFactory->create([
                        'record_id' => 'PRODUCT001',
                        'op' => 'replace',
                        'path' => '/foo/bar',
                        'value' => null,
                    ]),
                    $updateFactory->create([
                        'record_id' => 'PRODUCT001',
                        'op' => 'add',
                        'path' => '/wom/bat',
                        'value' => null,
                    ]),
                ],
                static function (Update $update): void {
                    $update->setValue('foo::' . $update->getOp());
                },
                [
                    $updateFactory->create([
                        'record_id' => 'PRODUCT001',
                        'op' => 'add',
                        'path' => '/foo/bar',
                        'value' => 'foo::add',
                    ]),
                    $updateFactory->create([
                        'record_id' => 'PRODUCT001',
                        'op' => 'replace',
                        'path' => '/foo/bar',
                        'value' => 'foo::replace',
                    ]),
                    $updateFactory->create([
                        'record_id' => 'PRODUCT001',
                        'op' => 'add',
                        'path' => '/wom/bat',
                        'value' => 'foo::add',
                    ]),
                ],
            ],
        ];
    }
}
