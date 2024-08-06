<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Model\Analytics\Collect;

use Klevu\PhpSDK\Model\Analytics\Collect\Event;
use Klevu\PhpSDK\Model\Analytics\Collect\EventIterator;
use Klevu\PhpSDK\Model\Analytics\Collect\EventType;
use Klevu\PhpSDK\Test\Unit\Model\AbstractIteratorTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(EventIterator::class)]
class EventIteratorTest extends AbstractIteratorTestCase
{
    /**
     * @var string
     */
    protected string $iteratorFqcn = EventIterator::class;
    /**
     * @var string
     */
    protected string $itemFqcn = Event::class;

    /**
     * @return Event[][][]
     */
    public static function dataProvider_valid(): array
    {
        return [
            [
                [
                    new Event(
                        event: EventType::ORDER_PURCHASE,
                        apikey: 'klevu-1234567890',
                        version: '1.0.0',
                        data: ['foo' => 'bar'],
                    ),
                    new Event(
                        event: EventType::ORDER_PURCHASE,
                        apikey: 'klevu-9876543210',
                        version: '1.0.0',
                        data: ['wom' => 'bat'],
                    ),
                ],
            ],
            [
                [
                    new Event(
                        event: EventType::ORDER_PURCHASE,
                        apikey: 'klevu-1234567890',
                        version: '1.0.0',
                        data: ['foo' => 'bar'],
                    ),
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
                    new Event(
                        event: EventType::ORDER_PURCHASE,
                        apikey: 'klevu-1234567890',
                        version: '1.0.0',
                        data: ['foo' => 'bar'],
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
                    new Event(
                        event: EventType::ORDER_PURCHASE,
                        apikey: 'klevu-1234567890',
                        version: '1.0.0',
                        data: ['foo' => 'bar'],
                    ),
                    new Event(
                        event: EventType::ORDER_PURCHASE,
                        apikey: 'klevu-9876543210',
                        version: '1.1.0',
                        data: ['wom' => 'bat'],
                    ),
                ],
                static fn (Event $event): bool => version_compare($event->version, '1.0.0', 'eq'),
                [
                    new Event(
                        event: EventType::ORDER_PURCHASE,
                        apikey: 'klevu-1234567890',
                        version: '1.0.0',
                        data: ['foo' => 'bar'],
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
        return [
            [
                [
                    new Event(
                        event: EventType::ORDER_PURCHASE,
                        apikey: 'klevu-1234567890',
                        version: '1.0.0',
                        data: ['foo' => 'bar'],
                    ),
                    new Event(
                        event: EventType::ORDER_PURCHASE,
                        apikey: 'klevu-9876543210',
                        version: '1.1.0',
                        data: ['wom' => 'bat'],
                    ),
                ],
                // phpcs:ignore SlevomatCodingStandard.PHP.DisallowReference.DisallowedPassingByReference
                static function (Event &$event): void {
                    $event = new Event(
                        event: $event->event,
                        apikey: $event->apikey,
                        version: '2.0.0',
                        data: array_merge($event->data, ['additional' => 'data']),
                    );
                },
                [
                    new Event(
                        event: EventType::ORDER_PURCHASE,
                        apikey: 'klevu-1234567890',
                        version: '2.0.0',
                        data: [
                            'foo' => 'bar',
                            'additional' => 'data',
                        ],
                    ),
                    new Event(
                        event: EventType::ORDER_PURCHASE,
                        apikey: 'klevu-9876543210',
                        version: '2.0.0',
                        data: [
                            'wom' => 'bat',
                            'additional' => 'data',
                        ],
                    ),
                ],
            ],
        ];
    }
}
