<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Validator\RequestBearerToken\RequestHeaders;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Validator\RequestBearerToken\RequestHeaders\TimestampValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimestampValidator::class)]
class TimestampValidatorTest extends TestCase
{
    /**
     * @return mixed[][]
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public static function dataProvider_testExecute_InvalidType(): array
    {
        return [
            [42],
            [3.14],
            [true],
            [static fn () => 'foo'],
            [(object)['foo' => 'bar']],

            [[42]],
            [[3.14]],
            [[['foo']]],
            [[true]],
            [[static fn () => 'foo']],
            [[(object)['foo' => 'bar']]],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidType')]
    public function testExecute_InvalidType(mixed $data): void
    {
        $timestampValidator = new TimestampValidator();

        $this->expectException(InvalidTypeValidationException::class);
        try {
            $timestampValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Timestamp header value',
                haystack: $error,
            );
            throw $exception;
        }
    }

    #[Test]
    #[TestWith([null])]
    #[TestWith([''])]
    #[TestWith([' '])]
    #[TestWith([[]])]
    #[TestWith([[null]])]
    #[TestWith([['']])]
    #[TestWith([[' ']])]
    public function testExecute_Empty(mixed $data): void
    {
        $timestampValidator = new TimestampValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $timestampValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Timestamp header value',
                haystack: $error,
            );
            throw $exception;
        }
    }

    /**
     * @param string[] $data
     *
     * @return void
     */
    #[Test]
    #[TestWith([['2023-01-01-T00:00:00+00:00', '2023-02-01-T00:00:00+00:00']])]
    public function testExecute_ConflictingValues(array $data): void
    {
        $timestampValidator = new TimestampValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $timestampValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Conflicting Timestamp header value',
                haystack: $error,
            );
            throw $exception;
        }
    }

    #[Test]
    #[TestWith(['foo'])]
    #[TestWith(['12th November'])]
    #[TestWith(['2023-12-31'])]
    #[TestWith(['2023-00-00T00:00:00'])]
    #[TestWith(['2023-02-31T00:00:00.000Z'])]
    #[TestWith([['foo']])]
    #[TestWith([['12th November']])]
    #[TestWith([['2023-12-31']])]
    #[TestWith([['2023-00-00T00:00:00']])]
    #[TestWith([['2023-02-31T00:00:00.000Z']])]
    public function testExecute_InvalidFormat(mixed $data): void
    {
        $timestampValidator = new TimestampValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $timestampValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Timestamp header value must be valid ISO-8601 date time string',
                haystack: $error,
            );

            throw $exception;
        }
    }

    /**
     * @return string[][]|string[][][]
     */
    public static function dataProvider_testExecute_OutsideTimeWindow_Past(): array
    {
        return [
            [
                date(DATE_ATOM, time() - 605),
            ],
            [
                [
                    date(DATE_ATOM, time() - 605),
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_OutsideTimeWindow_Past')]
    public function testExecute_OutsideTimeWindow_Past(mixed $data): void
    {
        $timestampValidator = new TimestampValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $timestampValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Timestamp header value must not be more than 10 minutes in the past',
                haystack: $error,
            );

            throw $exception;
        }
    }

    /**
     * @return string[][]|string[][][]
     */
    public static function dataProvider_testExecute_OutsideTimeWindow_Future(): array
    {
        return [
            [
                date(DATE_ATOM, time() + 120),
            ],
            [
                [
                    date(DATE_ATOM, time() + 120),
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_OutsideTimeWindow_Future')]
    public function testExecute_OutsideTimeWindow_Future(mixed $data): void
    {
        $timestampValidator = new TimestampValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $timestampValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Timestamp header value must not be in the future',
                haystack: $error,
            );

            throw $exception;
        }
    }

    /**
     * @return string[][]|string[][][]
     */
    public static function dataProvider_testExecute_Valid(): array
    {
        return [
            [
                date(DATE_ATOM, time() - 500),
            ],
            [
                date(DATE_ATOM, time()),
            ],
            [
                str_replace(
                    search: '+00:00',
                    replace: '.000Z',
                    subject: date(DATE_ATOM, time()),
                ),
            ],
            [
                date(DATE_ATOM, time() + 50),
            ],

            [
                [
                    date(DATE_ATOM, time() - 500),
                ],
            ],
            [
                [
                    date(DATE_ATOM, time()),
                ],
            ],
            [
                [
                    str_replace(
                        search: '+00:00',
                        replace: '.000Z',
                        subject: date(DATE_ATOM, time()),
                    ),
                ],
            ],
            [
                [
                    date(DATE_ATOM, time() + 50),
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_Valid')]
    public function testExecute_Valid(mixed $data): void
    {
        $timestampValidator = new TimestampValidator();

        $this->expectNotToPerformAssertions();
        $timestampValidator->execute($data);
    }

    #[Test]
    public function testExecute_Consecutive_Valid(): void
    {
        $timestampValidator = new TimestampValidator();

        $this->expectNotToPerformAssertions();

        $timestamp = date(DATE_ATOM, time());
        $timestampValidator->execute([$timestamp]);
        $timestampValidator->execute($timestamp);
        $timestampValidator->execute([$timestamp]);
    }

    #[Test]
    public function testExecute_Consecutive_Invalid(): void
    {
        $timestampValidator = new TimestampValidator();

        $this->expectException(InvalidTypeValidationException::class);

        $timestamp = date(DATE_ATOM, time());
        $timestampValidator->execute([$timestamp]);
        $timestampValidator->execute($timestamp);
        $timestampValidator->execute([[$timestamp]]);
    }
}
