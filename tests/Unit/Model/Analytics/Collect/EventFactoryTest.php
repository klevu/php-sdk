<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Model\Analytics\Collect;

use Klevu\PhpSDK\Model\Analytics\Collect\Event;
use Klevu\PhpSDK\Model\Analytics\Collect\EventFactory;
use Klevu\PhpSDK\Model\Analytics\Collect\EventType;
use Klevu\PhpSDK\Model\Analytics\Collect\UserProfile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(EventFactory::class)]
class EventFactoryTest extends TestCase
{
    /**
     * @return mixed[][]
     */
    public static function dataProvider_testCreate_Invalid(): array
    {
        return [
            [
                [],
                \TypeError::class, // Missing Event
            ],
            [
                [
                    'EVENT' => EventType::ORDER_PURCHASE,
                ],
                \TypeError::class, // Missing Event
            ],
            [
                [
                    'event' => 'foo',
                    'apikey' => '',
                    'version' => '',
                    'data' => [],
                    'userProfile' => null,
                ],
                \ValueError::class, // Invalid Event
            ],
            [
                [
                    'event' => EventType::ORDER_PURCHASE,
                    'apikey' => 2,
                    'version' => '',
                    'data' => [],
                    'userProfile' => null,
                ],
                \TypeError::class, // Invalid apikey
            ],
            [
                [
                    'event' => EventType::ORDER_PURCHASE,
                    'apikey' => '',
                    'version' => 3.14,
                    'data' => [],
                    'userProfile' => null,
                ],
                \TypeError::class, // Invalid version
            ],
            [
                [
                    'event' => EventType::ORDER_PURCHASE,
                    'apikey' => '',
                    'version' => '',
                    'data' => 'foo',
                    'userProfile' => null,
                ],
                \TypeError::class, // Invalid data
            ],
            [
                [
                    'event' => EventType::ORDER_PURCHASE,
                    'apikey' => '',
                    'version' => '',
                    'data' => [],
                    'userProfile' => 'foo',
                ],
                \TypeError::class, // Invalid userProfile
            ],
        ];
    }

    /**
     * @param mixed[] $data
     * @param class-string<\Throwable> $expectedExceptionFqcn
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testCreate_Invalid')]
    public function testCreate_Invalid(
        array $data,
        string $expectedExceptionFqcn,
    ): void {
        $eventFactory = new EventFactory();

        $this->expectException($expectedExceptionFqcn);
        $eventFactory->create($data);
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testCreate_Valid(): array
    {
        return [
            [
                [
                    'event' => EventType::ORDER_PURCHASE,
                    // Empty data is fine in the factory - validation is performed when object is used
                ],
                new Event(
                    event: EventType::ORDER_PURCHASE,
                    apikey: '',
                    version: '',
                    data: [],
                    userProfile: null,
                ),
            ],
            [
                [
                    'event' => EventType::ORDER_PURCHASE->value,
                    // Empty data is fine in the factory - validation is performed when object is used
                    'apikey' => null,
                    'version' => null,
                    'data' => null,
                    'userProfile' => null,
                ],
                new Event(
                    event: EventType::ORDER_PURCHASE,
                    apikey: '',
                    version: '',
                    data: [],
                    userProfile: null,
                ),
            ],
            [
                [
                    'event' => EventType::ORDER_PURCHASE->value,
                    // Empty data is fine in the factory - validation is performed when object is used
                    'apikey' => '',
                    'version' => '',
                    'data' => [],
                    'userProfile' => [],
                ],
                new Event(
                    event: EventType::ORDER_PURCHASE,
                    apikey: '',
                    version: '',
                    data: [],
                    userProfile: new UserProfile(
                        ipAddress: null,
                        email: null,
                    ),
                ),
            ],
            [
                [
                    'event' => EventType::ORDER_PURCHASE->value,
                    // Empty data is fine in the factory - validation is performed when object is used
                    'apikey' => 'klevu-1234567890',
                    'version' => '1.0.0',
                    'data' => [
                        'items' => [
                            [
                                'order_id' => '1223434',
                                'order_line_id' => 'ABC-12343',
                                'item_name' => '45RU,800x1070,N-Type,D,NoS,CM,WH,EA',
                                'item_id' => '75882',
                                'item_group_id' => '75882',
                                'item_variant_id' => '75882',
                                'unit_price' => 6033.42,
                                'currency' => 'CAD',
                                'units' => 2,
                            ],
                        ],
                    ],
                    'userProfile' => [
                        'email' => 'enc_123456789',
                        'ipAddress' => '127.0.0.1',
                    ],
                ],
                new Event(
                    event: EventType::ORDER_PURCHASE,
                    apikey: 'klevu-1234567890',
                    version: '1.0.0',
                    data: [
                        'items' => [
                            [
                                'order_id' => '1223434',
                                'order_line_id' => 'ABC-12343',
                                'item_name' => '45RU,800x1070,N-Type,D,NoS,CM,WH,EA',
                                'item_id' => '75882',
                                'item_group_id' => '75882',
                                'item_variant_id' => '75882',
                                'unit_price' => 6033.42,
                                'currency' => 'CAD',
                                'units' => 2,
                            ],
                        ],
                    ],
                    userProfile: new UserProfile(
                        ipAddress: '127.0.0.1',
                        email: 'enc_123456789',
                    ),
                ),
            ],
            [
                [
                    'event' => EventType::ORDER_PURCHASE->value,
                    // Empty data is fine in the factory - validation is performed when object is used
                    'APIKEY' => 'klevu-1234567890',
                    'VERSION' => '1.0.0',
                    'DATA' => [
                        'items' => [
                            [
                                'order_id' => '1223434',
                                'order_line_id' => 'ABC-12343',
                                'item_name' => '45RU,800x1070,N-Type,D,NoS,CM,WH,EA',
                                'item_id' => '75882',
                                'item_group_id' => '75882',
                                'item_variant_id' => '75882',
                                'unit_price' => 6033.42,
                                'currency' => 'CAD',
                                'units' => 2,
                            ],
                        ],
                    ],
                    'USERPROFILE' => [
                        'email' => 'enc_123456789',
                        'ipAddress' => '127.0.0.1',
                    ],
                ],
                new Event(
                    event: EventType::ORDER_PURCHASE,
                    apikey: '',
                    version: '',
                    data: [],
                    userProfile: null,
                ),
            ],
            [
                [
                    'event' => EventType::ORDER_PURCHASE->value,
                    // Empty data is fine in the factory - validation is performed when object is used
                    'APIKEY' => 'klevu-1234567890',
                    'VERSION' => '1.0.0',
                    'DATA' => [
                        'items' => [
                            [
                                'order_id' => '1223434',
                                'order_line_id' => 'ABC-12343',
                                'item_name' => '45RU,800x1070,N-Type,D,NoS,CM,WH,EA',
                                'item_id' => '75882',
                                'item_group_id' => '75882',
                                'item_variant_id' => '75882',
                                'unit_price' => 6033.42,
                                'currency' => 'CAD',
                                'units' => 2,
                            ],
                        ],
                    ],
                    'userProfile' => [
                        'EMAIL' => 'enc_123456789',
                        'IPADDRESS' => '127.0.0.1',
                    ],
                ],
                new Event(
                    event: EventType::ORDER_PURCHASE,
                    apikey: '',
                    version: '',
                    data: [],
                    userProfile: new UserProfile(),
                ),
            ],
        ];
    }

    /**
     * @param mixed[] $data
     * @param Event $expectedResult
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testCreate_Valid')]
    public function testCreate_Valid(
        array $data,
        Event $expectedResult,
    ): void {
        $eventFactory = new EventFactory();

        $event = $eventFactory->create($data);
        $this->assertEquals(
            expected: $expectedResult,
            actual: $event,
        );
    }
}
