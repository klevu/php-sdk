<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Exception\Api;

use Klevu\PhpSDK\Exception\Api\BadResponseException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(BadResponseException::class)]
class BadResponseExceptionTest extends TestCase
{
    /**
     * @return mixed[][]
     */
    public static function dataProvider_testConstructor_WithArgs(): array
    {
        return [
            [
                'Error Message',
                418,
                ['Error 1', 'Error 2'],
                null,
                null,
                null,
            ],
            [
                'Error Message',
                418,
                [123],
                null,
                null,
                null,
            ],
            [
                'Error Message',
                418,
                [123],
                'BAD_REQUEST',
                '/collect',
                [
                    'Debug message',
                    'Another debug message',
                ],
            ],
        ];
    }

    /**
     * @param string $message
     * @param int $code
     * @param string[] $errors
     * @param string|null $apiCode
     * @param string|null $path
     * @param string[]|null $debug
     *
     * @return void
     * @throws Exception
     */
    #[Test]
    #[DataProvider('dataProvider_testConstructor_WithArgs')]
    public function testConstructor_WithArgs(
        string $message,
        int $code,
        array $errors,
        ?string $apiCode,
        ?string $path,
        ?array $debug,
    ): void {
        $previousException = $this->createMock(\Throwable::class);

        if (
            null === $apiCode
            && null === $path
            && null === $debug
        ) {
            $exception = new BadResponseException(
                message: $message,
                code: $code,
                errors: $errors,
                previous: $previousException,
            );
        } else {
            $exception = new BadResponseException(
                message: $message,
                code: $code,
                errors: $errors,
                apiCode: $apiCode,
                path: $path,
                debug: $debug,
                previous: $previousException,
            );
        }

        $this->assertSame(array_map('strval', $errors), $exception->getErrors());
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($apiCode, $exception->getApiCode());
        $this->assertSame($path, $exception->getPath());
        $this->assertSame($debug, $exception->getDebug());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    #[Test]
    public function testConstructor_WithoutArgs(): void
    {
        $exception = new BadResponseException(
            message: '',
            code: 0,
        );

        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame([], $exception->getErrors());
        $this->assertNull($exception->getApiCode());
        $this->assertNull($exception->getPath());
        $this->assertNull($exception->getDebug());
        $this->assertNull($exception->getPrevious());
    }
}
