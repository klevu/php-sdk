<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

/**
 * @noinspection PhpObjectFieldsAreOnlyWrittenInspection
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Model;

use Klevu\PhpSDK\Model\ApiResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(ApiResponse::class)]
class ApiResponseTest extends TestCase
{
    #[Test]
    public function testConstruct_Valid(): void
    {
        $apiResponse = new ApiResponse(
            responseCode: 200,
            message: 'Test Message',
            jobId: '12345',
        );

        $this->assertSame(200, $apiResponse->responseCode);
        $this->assertSame('Test Message', $apiResponse->message);
        $this->assertSame('12345', $apiResponse->jobId);
    }

    #[Test]
    public function testConstruct_Valid_NoOptionalConstructorArgs(): void
    {
        $apiResponse = new ApiResponse(503);

        $this->assertSame(503, $apiResponse->responseCode);
        $this->assertSame('', $apiResponse->message);
        $this->assertNull($apiResponse->jobId);
    }

    #[Test]
    public function testReadonly_ResponseCode(): void
    {
        $apiResponse = new ApiResponse(503);

        $this->expectException(\Error::class);
        /** @noinspection PhpReadonlyPropertyWrittenOutsideDeclarationScopeInspection */
        $apiResponse->responseCode = 200;
    }

    #[Test]
    public function testReadonly_Message(): void
    {
        $apiResponse = new ApiResponse(
            responseCode: 200,
            message: 'Test Message',
        );

        $this->expectException(\Error::class);
        /** @noinspection PhpReadonlyPropertyWrittenOutsideDeclarationScopeInspection */
        $apiResponse->message = 'Changed';
    }

    #[Test]
    public function testReadonly_JobId(): void
    {
        $apiResponse = new ApiResponse(
            responseCode: 200,
            jobId: '12345',
        );

        $this->expectException(\Error::class);
        /** @noinspection PhpReadonlyPropertyWrittenOutsideDeclarationScopeInspection */
        $apiResponse->jobId = '67890';
    }

    /**
     * @param int $responseCode
     * @param string[]|null $errors
     * @param bool $expectedResult
     *
     * @return void
     */
    #[Test]
    #[TestWith([200, null, true])]
    #[TestWith([200, [], true])]
    #[TestWith([200, ['foo'], false])]
    #[TestWith([401, null, false])]
    #[TestWith([500, [], false])]
    public function testIsSuccess(
        int $responseCode,
        ?array $errors,
        bool $expectedResult,
    ): void {
        $apiResponse = new ApiResponse(
            responseCode: $responseCode,
            errors: $errors,
        );

        $this->assertSame(
            expected: $expectedResult,
            actual: $apiResponse->isSuccess(),
        );
    }

    #[Test]
    #[TestWith([200])]
    #[TestWith([302])]
    #[TestWith([499])]
    #[TestWith([500])]
    public function testGetResponseCode(
        int $responseCode,
    ): void {
        $apiResponse = new ApiResponse(
            responseCode: $responseCode,
        );

        $this->assertSame(
            expected: $responseCode,
            actual: $apiResponse->getResponseCode(),
        );
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testGetMessages(): array
    {
        return [
            [
                '',
                null,
                [],
            ],
            [
                '',
                [],
                [],
            ],
            [
                'Foo',
                null,
                ['Foo'],
            ],
            [
                'Foo',
                [],
                ['Foo'],
            ],
            [
                '',
                ['Foo'],
                ['Foo'],
            ],
            [
                'Foo',
                ['baz', 'Bar'],
                ['Bar', 'baz', 'Foo'],
            ],
        ];
    }

    /**
     * @param string $message
     * @param string[]|null $errors
     * @param string[] $expectedResult
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testGetMessages')]
    public function testGetMessages(
        string $message,
        ?array $errors,
        array $expectedResult,
    ): void {
        $apiResponse = new ApiResponse(
            responseCode: 200,
            message: $message,
            errors: $errors,
        );

        $this->assertSame(
            expected: $expectedResult,
            actual: $apiResponse->getMessages(),
        );
    }
}
