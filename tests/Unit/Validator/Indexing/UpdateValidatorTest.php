<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Validator\Indexing;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\Indexing\UpdateFactory;
use Klevu\PhpSDK\Validator\Indexing\UpdateValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(UpdateValidator::class)]
class UpdateValidatorTest extends TestCase
{
    /**
     * @return mixed[]
     */
    public static function dataProvider_testExecute_InvalidType(): array
    {
        return [
            ['foo'],
            [42],
            [3.14],
            [true],
            [null],
            [static fn () => 'foo'],
            [(object)['foo' => 'bar']],
            [[]],
            [['foo']],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidType')]
    public function testExecute_InvalidType(mixed $data): void
    {
        $updateValidator = new UpdateValidator();

        $this->expectException(InvalidTypeValidationException::class);
        try {
            $updateValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Update',
                haystack: $error,
            );
            throw $exception;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_InvalidRecordId(): array
    {
        $updateFactory = new UpdateFactory();

        return [
            [
                $updateFactory->create([
                    'record_id' => '',
                    'op' => 'add',
                    'path' => '',
                ]),
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidRecordId')]
    public function testExecute_InvalidRecordId(mixed $data): void
    {
        $updateValidator = new UpdateValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $updateValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Record Id',
                haystack: $error,
            );
            throw $exception;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_InvalidOp(): array
    {
        $updateFactory = new UpdateFactory();

        return [
            [
                $updateFactory->create([
                    'record_id' => 'PRODUCT001',
                    'op' => '',
                    'path' => '',
                ]),
            ],
            [
                $updateFactory->create([
                    'record_id' => 'PRODUCT001',
                    'op' => 'ADD',
                    'path' => '',
                ]),
            ],
            [
                $updateFactory->create([
                    'record_id' => 'PRODUCT001',
                    'op' => 'foo',
                    'path' => '',
                ]),
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidOp')]
    public function testExecute_InvalidOp(mixed $data): void
    {
        $updateValidator = new UpdateValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $updateValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: '[op]',
                haystack: $error,
            );
            throw $exception;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_InvalidPath(): array
    {
        $updateFactory = new UpdateFactory();

        return [
            [
                $updateFactory->create([
                    'record_id' => 'PRODUCT001',
                    'op' => 'add',
                    'path' => null,
                ]),
            ],
            [
                $updateFactory->create([
                    'record_id' => 'PRODUCT001',
                    'op' => 'add',
                    'path' => '  foo  ',
                ]),
            ],
            [
                $updateFactory->create([
                    'record_id' => 'PRODUCT001',
                    'op' => 'add',
                    'path' => '#1234',
                ]),
            ],
            [
                $updateFactory->create([
                    'record_id' => 'PRODUCT001',
                    'op' => 'add',
                    'path' => '/foo\/bar',
                ]),
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidPath')]
    public function testExecute_InvalidPath(mixed $data): void
    {
        $updateValidator = new UpdateValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $updateValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Path',
                haystack: $error,
            );
            throw $exception;
        }
    }

    #[Test]
    public function testExecute_MultipleFailures(): void
    {
        $updateFactory = new UpdateFactory();
        $update = $updateFactory->create([
            'record_id' => '',
            'op' => 'foo',
            'path' => null,
        ]);
        $updateValidator = new UpdateValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $updateValidator->execute($update);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            sort($errors);

            $this->assertSame(
                expected: [
                    'Path must be set',
                    'Record Id is required',
                    'Unrecognised update operation [op] "foo", must be one of add, remove, replace',
                ],
                actual: $errors,
            );

            throw $exception;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_Valid(): array
    {
        $updateFactory = new UpdateFactory();

        return [
            [
                $updateFactory->create([
                    'record_id' => 'PRODUCT001',
                    'op' => 'add',
                    'path' => '/attributes/prices',
                    'value' => [
                        'specialPrice' => 1234.0,
                        'defaultPrice' => 9999.99,
                    ],
                ]),
            ],
            [
                $updateFactory->create([
                    'record_id' => 'PRODUCT001',
                    'op' => 'add',
                    'path' => '@1425',
                    'value' => null,
                ]),
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_Valid')]
    public function testExecute_Valid(mixed $data): void
    {
        $updateValidator = new UpdateValidator();

        $this->expectNotToPerformAssertions();
        $updateValidator->execute($data);
    }
}
