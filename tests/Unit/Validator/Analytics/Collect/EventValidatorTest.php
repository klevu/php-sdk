<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Validator\Analytics\Collect;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\Analytics\Collect\Event;
use Klevu\PhpSDK\Model\Analytics\Collect\EventType;
use Klevu\PhpSDK\Validator\Analytics\Collect\EventData\EventDataValidatorInterface;
use Klevu\PhpSDK\Validator\Analytics\Collect\EventValidator;
use Klevu\PhpSDK\Validator\ValidatorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(EventValidator::class)]
class EventValidatorTest extends TestCase
{
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
        $eventValidator = new EventValidator();

        $this->expectException(InvalidTypeValidationException::class);
        try {
            $eventValidator->execute($data);
        } catch (ValidationException $e) {
            $this->assertCount(1, $e->getErrors());
            throw $e;
        }
    }

    /**
     * @return Event[][]
     */
    public static function dataProvider_testExecute_Valid(): array
    {
        return [
            [
                new Event(
                    event: EventType::ORDER_PURCHASE,
                    apikey: 'klevu-1234567890',
                    version: '1.0.0',
                    data: [
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
                ),
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_Valid')]
    public function testExecute_Valid_DefaultEventDataValidators(mixed $data): void
    {
        $eventValidator = new EventValidator();

        $this->expectNotToPerformAssertions();
        $eventValidator->execute($data);
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_Valid')]
    public function testExecute_Valid_CustomEventDataValidators(mixed $data): void
    {
        $eventValidator = new EventValidator(
            eventDataValidators: [
                $this->getMockEventDataValidator(
                    appliesToEvent: true,
                    validates: false,
                    errors: [
                        'Test Error',
                    ],
                ),
            ],
        );

        $this->expectException(InvalidDataValidationException::class);
        try {
            $eventValidator->execute($data);
        } catch (ValidationException $e) {
            $this->assertSame(
                [
                    'Test Error',
                ],
                $e->getErrors(),
            );
            throw $e;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_Invalid_Apikey(): array
    {
        return [
            [
                new Event(
                    event: EventType::ORDER_PURCHASE,
                    apikey: '',
                    version: '1.0.0',
                    data: [], // Custom eventDataValidator mocks used
                ),
                'JS API Key must not be empty',
            ],
            [
                new Event(
                    event: EventType::ORDER_PURCHASE,
                    apikey: '       ',
                    version: '1.0.0',
                    data: [], // Custom eventDataValidator mocks used
                ),
                'JS API Key must not be empty',
            ],
            [
                new Event(
                    event: EventType::ORDER_PURCHASE,
                    apikey: 'foo',
                    version: '1.0.0',
                    data: [], // Custom eventDataValidator mocks used
                ),
                'JS API Key is not valid',
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_Invalid_Apikey')]
    public function testExecute_Invalid_Apikey_DefaultValidator(
        mixed $data,
        string $expectedErrorMessage,
    ): void {
        $eventValidator = new EventValidator(
            eventDataValidators: [
                $this->getMockEventDataValidator(
                    appliesToEvent: true,
                    validates: true,
                ),
            ],
        );

        $this->expectException(InvalidDataValidationException::class);
        try {
            $eventValidator->execute($data);
        } catch (ValidationException $e) {
            $this->assertSame(
                [
                    $expectedErrorMessage,
                ],
                $e->getErrors(),
            );
            throw $e;
        }
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_Invalid_Apikey')]
    public function testExecute_Invalid_Apikey_CustomValidator(
        mixed $data,
        string $expectedErrorMessage, // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    ): void {
        $eventValidator = new EventValidator(
            jsApiKeyValidator: $this->getMockValidator(
                validates: false,
                errors: ['Test validation message'],
            ),
            eventDataValidators: [
                $this->getMockEventDataValidator(
                    appliesToEvent: true,
                    validates: true,
                ),
            ],
        );

        $this->expectException(InvalidDataValidationException::class);
        try {
            $eventValidator->execute($data);
        } catch (ValidationException $e) {
            $this->assertSame(
                [
                    'Test validation message',
                ],
                $e->getErrors(),
            );
            throw $e;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_Invalid_Version(): array
    {
        return [
            [
                new Event(
                    event: EventType::ORDER_PURCHASE,
                    apikey: 'klevu-1234567890',
                    version: '',
                    data: [], // Custom eventDataValidator mocks used
                ),
                'Event version must not be empty',
            ],
            [
                new Event(
                    event: EventType::ORDER_PURCHASE,
                    apikey: 'klevu-1234567890',
                    version: '      ',
                    data: [], // Custom eventDataValidator mocks used
                ),
                'Event version must not be empty',
            ],
            [
                new Event(
                    event: EventType::ORDER_PURCHASE,
                    apikey: 'klevu-1234567890',
                    version: 'foo',
                    data: [], // Custom eventDataValidator mocks used
                ),
                'Event version must be a valid, stable semantic version',
            ],
            [
                new Event(
                    event: EventType::ORDER_PURCHASE,
                    apikey: 'klevu-1234567890',
                    version: '0.0.1',
                    data: [], // Custom eventDataValidator mocks used
                ),
                'Event version must be a valid, stable semantic version',
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_Invalid_Version')]
    public function testExecute_Invalid_Version(
        mixed $data,
        string $expectedErrorMessage,
    ): void {
        $eventValidator = new EventValidator(
            eventDataValidators: [
                $this->getMockEventDataValidator(
                    appliesToEvent: true,
                    validates: true,
                ),
            ],
        );

        $this->expectException(InvalidDataValidationException::class);
        try {
            $eventValidator->execute($data);
        } catch (ValidationException $e) {
            $this->assertSame(
                [
                    $expectedErrorMessage,
                ],
                $e->getErrors(),
            );
            throw $e;
        }
    }

    #[Test]
    public function testExecute_NoApplicableValidators(): void
    {
        $eventValidator = new EventValidator(
            eventDataValidators: [
                $this->getMockEventDataValidator(
                    appliesToEvent: false,
                    validates: true,
                ),
            ],
        );

        $this->expectException(InvalidDataValidationException::class);
        try {
            $eventValidator->execute(
                data: new Event(
                    event: EventType::ORDER_PURCHASE,
                    apikey: 'klevu-1234567890',
                    version: '1.0.0',
                    data: [], // Custom eventDataValidator mocks used
                ),
            );
        } catch (InvalidDataValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);
            $this->assertMatchesRegularExpression(
                pattern: '/Unsupported event version/',
                string: $errors[0] ?? '',
            );

            throw $exception;
        }
    }

    /**
     * @param bool $validates
     * @param string[] $errors
     *
     * @return ValidatorInterface
     */
    private function getMockValidator(
        bool $validates,
        array $errors = [],
    ): ValidatorInterface {
        $mockValidator = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        if (!$validates) {
            $mockValidator->method('execute')
                ->willThrowException(new ValidationException(
                    errors: $errors,
                    message: 'Mock validation exception',
                ));
        }

        return $mockValidator;
    }

    /**
     * @param bool $appliesToEvent
     * @param bool $validates
     * @param string[] $errors
     *
     * @return EventDataValidatorInterface
     */
    private function getMockEventDataValidator(
        bool $appliesToEvent,
        bool $validates,
        array $errors = [],
    ): EventDataValidatorInterface {
        $mockEventDataValidator = $this->getMockBuilder(EventDataValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockEventDataValidator->method('appliesToEvent')
            ->willReturn($appliesToEvent);

        if (!$validates) {
            $mockEventDataValidator->expects($this->atLeastOnce())
                ->method('execute')
                ->willThrowException(new ValidationException(
                    errors: $errors,
                    message: 'Mock validation exception',
                ));
        }

        return $mockEventDataValidator;
    }
}
