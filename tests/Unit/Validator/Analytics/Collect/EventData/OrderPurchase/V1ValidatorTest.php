<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Validator\Analytics\Collect\EventData\OrderPurchase;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\Analytics\Collect\Event;
use Klevu\PhpSDK\Model\Analytics\Collect\EventType;
use Klevu\PhpSDK\Validator\Analytics\Collect\EventData\OrderPurchase\V1Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(V1Validator::class)]
class V1ValidatorTest extends TestCase
{
    /**
     * @return mixed[][]
     */
    public static function dataProvider_AppliesToEvent(): array
    {
        return [
            [
                new Event(
                    event: EventType::ORDER_PURCHASE,
                    apikey: 'klevu-1234567890',
                    version: '1.0.0',
                    data: [],
                ),
                true,
            ],
            [
                new Event(
                    event: EventType::ORDER_PURCHASE,
                    apikey: 'klevu-1234567890',
                    version: '0.9',
                    data: [],
                ),
                false,
            ],
            [
                new Event(
                    event: EventType::ORDER_PURCHASE,
                    apikey: 'klevu-1234567890',
                    version: '1.0.1',
                    data: [],
                ),
                false,
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_AppliesToEvent')]
    public function testAppliesToEvent(Event $event, bool $expectedResult): void
    {
        $orderPurchaseValidator = new V1Validator();

        $this->assertSame(
            $expectedResult,
            $orderPurchaseValidator->appliesToEvent($event),
        );
    }

    /**
     * @return mixed[][]
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public static function dataProvider_testExecute_InvalidType(): array
    {
        return [
            ['foo'],
            [42],
            [3.14],
            [null],
            [true],
            [static fn () => 'foo'],
            [(object)['foo' => 'bar']],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidType')]
    public function testExecute_InvalidType(mixed $data): void
    {
        $orderPurchaseValidator = new V1Validator();

        $this->expectException(InvalidTypeValidationException::class);
        try {
            $orderPurchaseValidator->execute($data);
        } catch (ValidationException $e) {
            $this->assertCount(1, $e->getErrors());
            throw $e;
        }
    }

    /**
     * @return mixed[][]
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public static function dataProvider_testExecute_InvalidData(): array
    {
        return [
            [
                [],
            ],
            [
                [
                    'items' => 'foo',
                ],
            ],
            [
                [
                    'items' => [],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidData')]
    public function testExecute_InvalidData(mixed $data): void
    {
        $orderPurchaseValidator = new V1Validator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $orderPurchaseValidator->execute($data);
        } catch (ValidationException $e) {
            $this->assertCount(1, $e->getErrors());
            throw $e;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_Valid(): array
    {
        return [
            [
                [
                    // With all keys
                    'items' => [
                        [
                            'order_id' => '12345',
                            'order_line_id' => '67890',
                            'item_name' => 'Test Product',
                            'item_id' => '123-456',
                            'item_group_id' => '123',
                            'item_variant_id' => '456',
                            'unit_price' => '3.14',
                            'currency' => 'GBP',
                            'units' => 42,
                        ],
                    ],
                ],
            ],
            [
                [
                    // With only required keys
                    'items' => [
                        [
                            'item_name' => 'Test Product',
                            'item_id' => '123-456',
                            'item_group_id' => '123',
                            'item_variant_id' => '456',
                            'unit_price' => 3.14,
                            'currency' => 'GBP',
                        ],
                    ],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_Valid')]
    public function testExecute_Valid(mixed $data): void
    {
        $orderPurchaseValidator = new V1Validator();

        $this->expectNotToPerformAssertions();
        $orderPurchaseValidator->execute($data);
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_Invalid_RequiredKeys(): array
    {
        return [
            [
                [
                    'items' => [],
                ],
            ],
            [
                [
                    'items' => [
                        [
                            'order_id' => '12345',
                            'order_line_id' => '67890',
                            // item_name
                            'item_id' => '123-456',
                            'item_group_id' => '123',
                            'item_variant_id' => '456',
                            'unit_price' => '3.14',
                            'currency' => 'GBP',
                        ],
                    ],
                ],
            ],
            [
                [
                    'items' => [
                        [
                            'order_id' => '12345',
                            'order_line_id' => '67890',
                            'item_name' => 'Test Product',
                            // item_id
                            'item_group_id' => '123',
                            'item_variant_id' => '456',
                            'unit_price' => '3.14',
                            'currency' => 'GBP',
                        ],
                    ],
                ],
            ],
            [
                [
                    'items' => [
                        [
                            'order_id' => '12345',
                            'order_line_id' => '67890',
                            'item_name' => 'Test Product',
                            'item_id' => '123-456',
                            // item_group_id
                            'item_variant_id' => '456',
                            'unit_price' => '3.14',
                            'currency' => 'GBP',
                        ],
                    ],
                ],
            ],
            [
                [
                    'items' => [
                        [
                            'order_id' => '12345',
                            'order_line_id' => '67890',
                            'item_name' => 'Test Product',
                            'item_id' => '123-456',
                            'item_group_id' => '123',
                            // item_variant_id
                            'unit_price' => '3.14',
                            'currency' => 'GBP',
                        ],
                    ],
                ],
            ],
            [
                [
                    'items' => [
                        [
                            'order_id' => '12345',
                            'order_line_id' => '67890',
                            'item_name' => 'Test Product',
                            'item_id' => '123-456',
                            'item_group_id' => '123',
                            'item_variant_id' => '456',
                            // unit_price
                            'currency' => 'GBP',
                        ],
                    ],
                ],
            ],
            [
                [
                    'items' => [
                        [
                            'order_id' => '12345',
                            'order_line_id' => '67890',
                            'item_name' => 'Test Product',
                            'item_id' => '123-456',
                            'item_group_id' => '123',
                            'item_variant_id' => '456',
                            'unit_price' => '3.14',
                            // currency
                        ],
                    ],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_Invalid_RequiredKeys')]
    public function testExecute_Invalid_RequiredKeys(mixed $data): void
    {
        $orderPurchaseValidator = new V1Validator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $orderPurchaseValidator->execute($data);
        } catch (ValidationException $e) {
            $this->assertCount(1, $e->getErrors());
            throw $e;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_Invalid_StringKeys(): array
    {
        return [
            [
                [
                    'items' => [
                        [
                            'order_id' => 12345,
                            'order_line_id' => '67890',
                            'item_name' => 'Test Product',
                            'item_id' => '123-456',
                            'item_group_id' => '123',
                            'item_variant_id' => '456',
                            'unit_price' => '3.14',
                            'currency' => 'GBP',
                            'units' => 42,
                        ],
                        [
                            'order_id' => '12345',
                            'order_line_id' => 67890,
                            'item_name' => 'Test Product',
                            'item_id' => '123-456',
                            'item_group_id' => '123',
                            'item_variant_id' => '456',
                            'unit_price' => '3.14',
                            'currency' => 'GBP',
                            'units' => 42,
                        ],
                        [
                            'order_id' => '12345',
                            'order_line_id' => '67890',
                            'item_name' => 12345,
                            'item_id' => '123-456',
                            'item_group_id' => '123',
                            'item_variant_id' => '456',
                            'unit_price' => '3.14',
                            'currency' => 'GBP',
                            'units' => 42,
                        ],
                        [
                            'order_id' => '12345',
                            'order_line_id' => '67890',
                            'item_name' => 'Test Product',
                            'item_id' => 123,
                            'item_group_id' => '123',
                            'item_variant_id' => '456',
                            'unit_price' => '3.14',
                            'currency' => 'GBP',
                            'units' => 42,
                        ],
                        [
                            'order_id' => '12345',
                            'order_line_id' => '67890',
                            'item_name' => 'Test Product',
                            'item_id' => '123-456',
                            'item_group_id' => 123,
                            'item_variant_id' => '456',
                            'unit_price' => '3.14',
                            'currency' => 'GBP',
                            'units' => 42,
                        ],
                        [
                            'order_id' => '12345',
                            'order_line_id' => '67890',
                            'item_name' => 'Test Product',
                            'item_id' => '123-456',
                            'item_group_id' => '123',
                            'item_variant_id' => 456,
                            'unit_price' => '3.14',
                            'currency' => 'GBP',
                            'units' => 42,
                        ],
                        [
                            'order_id' => '12345',
                            'order_line_id' => '67890',
                            'item_name' => 'Test Product',
                            'item_id' => '123-456',
                            'item_group_id' => '123',
                            'item_variant_id' => '456',
                            'unit_price' => '3.14',
                            'currency' => null,
                            'units' => 42,
                        ],
                    ],
                ],
                [
                    'Item #0: The following keys are of an invalid type. Expected string. order_id',
                    'Item #1: The following keys are of an invalid type. Expected string. order_line_id',
                    'Item #2: The following keys are of an invalid type. Expected string. item_name',
                    'Item #3: The following keys are of an invalid type. Expected string. item_id',
                    'Item #4: The following keys are of an invalid type. Expected string. item_group_id',
                    'Item #5: The following keys are of an invalid type. Expected string. item_variant_id',
                    'Item #6: The following keys are of an invalid type. Expected string. currency',
                ],
            ],
        ];
    }

    /**
     * @param mixed $data
     * @param string[] $expectedErrorMessages
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testExecute_Invalid_StringKeys')]
    public function testExecute_Invalid_StringKeys(
        mixed $data,
        array $expectedErrorMessages,
    ): void {
        $orderPurchaseValidator = new V1Validator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $orderPurchaseValidator->execute($data);
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertSame(
                $expectedErrorMessages,
                array_intersect($expectedErrorMessages, $errors),
            );
            throw $e;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_Invalid_UnitPrice(): array
    {

        return [
            [
                [
                    'items' => [
                        [
                            'order_id' => '12345',
                            'order_line_id' => '67890',
                            'item_name' => 'Test Product',
                            'item_id' => '123-456',
                            'item_group_id' => '123',
                            'item_variant_id' => '456',
                            'unit_price' => 'abc',
                            'currency' => 'GBP',
                            'units' => 42,
                        ],
                    ],
                ],
            ],
            [
                [
                    'items' => [
                        [
                            'order_id' => '12345',
                            'order_line_id' => '67890',
                            'item_name' => 'Test Product',
                            'item_id' => '123-456',
                            'item_group_id' => '123',
                            'item_variant_id' => '456',
                            'unit_price' => -1.23,
                            'currency' => 'GBP',
                            'units' => 42,
                        ],
                    ],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_Invalid_UnitPrice')]
    public function testExecute_Invalid_UnitPrice(mixed $data): void
    {
        $orderPurchaseValidator = new V1Validator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $orderPurchaseValidator->execute($data);
        } catch (ValidationException $e) {
            $this->assertCount(1, $e->getErrors());
            throw $e;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_Invalid_Currency(): array
    {
        return [
            [
                [
                    'items' => [
                        [
                            'order_id' => '12345',
                            'order_line_id' => '67890',
                            'item_name' => 'Test Product',
                            'item_id' => '123-456',
                            'item_group_id' => '123',
                            'item_variant_id' => '456',
                            'unit_price' => '3.14',
                            'currency' => 'gbp',
                            'units' => 42,
                        ],
                    ],
                ],
            ],
            [
                [
                    'items' => [
                        [
                            'order_id' => '12345',
                            'order_line_id' => '67890',
                            'item_name' => 'Test Product',
                            'item_id' => '123-456',
                            'item_group_id' => '123',
                            'item_variant_id' => '456',
                            'unit_price' => '3.14',
                            'currency' => '123',
                            'units' => 42,
                        ],
                    ],
                ],
            ],
            [
                [
                    'items' => [
                        [
                            'order_id' => '12345',
                            'order_line_id' => '67890',
                            'item_name' => 'Test Product',
                            'item_id' => '123-456',
                            'item_group_id' => '123',
                            'item_variant_id' => '456',
                            'unit_price' => '3.14',
                            'currency' => '$',
                            'units' => 42,
                        ],
                    ],
                ],
            ],
            [
                [
                    'items' => [
                        [
                            'order_id' => '12345',
                            'order_line_id' => '67890',
                            'item_name' => 'Test Product',
                            'item_id' => '123-456',
                            'item_group_id' => '123',
                            'item_variant_id' => '456',
                            'unit_price' => '3.14',
                            'currency' => 'Sterling',
                            'units' => 42,
                        ],
                    ],
                ],
            ],
            [
                [
                    'items' => [
                        [
                            'order_id' => '12345',
                            'order_line_id' => '67890',
                            'item_name' => 'Test Product',
                            'item_id' => '123-456',
                            'item_group_id' => '123',
                            'item_variant_id' => '456',
                            'unit_price' => '3.14',
                            'currency' => 'GB',
                            'units' => 42,
                        ],
                    ],
                ],
            ],
            [
                [
                    'items' => [
                        [
                            'order_id' => '12345',
                            'order_line_id' => '67890',
                            'item_name' => 'Test Product',
                            'item_id' => '123-456',
                            'item_group_id' => '123',
                            'item_variant_id' => '456',
                            'unit_price' => '3.14',
                            'currency' => 'GBPP',
                            'units' => 42,
                        ],
                    ],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_Invalid_Currency')]
    public function testExecute_Invalid_Currency(mixed $data): void
    {
        $orderPurchaseValidator = new V1Validator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $orderPurchaseValidator->execute($data);
        } catch (ValidationException $e) {
            $this->assertCount(1, $e->getErrors());
            throw $e;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_Invalid_Units(): array
    {
        return [
            [
                [
                    'items' => [
                        [
                            'order_id' => '12345',
                            'order_line_id' => '67890',
                            'item_name' => 'Test Product',
                            'item_id' => '123-456',
                            'item_group_id' => '123',
                            'item_variant_id' => '456',
                            'unit_price' => '3.14',
                            'currency' => 'GBP',
                            'units' => '42',
                        ],
                    ],
                ],
            ],
            [
                [
                    'items' => [
                        [
                            'order_id' => '12345',
                            'order_line_id' => '67890',
                            'item_name' => 'Test Product',
                            'item_id' => '123-456',
                            'item_group_id' => '123',
                            'item_variant_id' => '456',
                            'unit_price' => '3.14',
                            'currency' => 'GBP',
                            'units' => -123,
                        ],
                    ],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_Invalid_Units')]
    public function testExecute_Invalid_Units(mixed $data): void
    {
        $orderPurchaseValidator = new V1Validator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $orderPurchaseValidator->execute($data);
        } catch (ValidationException $e) {
            $this->assertCount(1, $e->getErrors());
            throw $e;
        }
    }
}
