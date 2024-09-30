<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Exception\Api;

use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\Api\JsonExceptionFactory;
use Klevu\PhpSDK\Exception\ApiExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonExceptionFactory::class)]
class JsonExceptionFactoryTest extends TestCase
{
    /**
     * @return mixed[][]
     */
    public static function dataProvider_testCreateFromResponse_RequireValidJsonBody(): array
    {
        return [
            [
                200,
                null,
                null,
            ],
            [
                200,
                'Invalid Response Body',
                new BadResponseException(
                    message: 'Received invalid JSON response',
                    code: 200,
                    errors: [
                        'Syntax error',
                    ],
                ),
            ],
            [
                400,
                null,
                new BadRequestException(
                    message: 'API request rejected by Klevu API [400]',
                    code: 400,
                ),
            ],
            [
                401,
                null,
                new BadRequestException(
                    message: 'API request rejected by Klevu API [401]',
                    code: 401,
                ),
            ],
            [
                403,
                null,
                new BadRequestException(
                    message: 'API request rejected by Klevu API [403]',
                    code: 403,
                ),
            ],
            [
                404,
                null,
                new BadRequestException(
                    message: 'API request rejected by Klevu API [404]',
                    code: 404,
                ),
            ],
            [
                499,
                null,
                new BadResponseException(
                    message: 'Unexpected Response Code [499]',
                    code: 499,
                ),
            ],
            [
                500,
                null,
                new BadResponseException(
                    message: 'Unexpected Response Code [500]',
                    code: 500,
                ),
            ],
            [
                501,
                null,
                new BadResponseException(
                    message: 'Unexpected Response Code [501]',
                    code: 501,
                ),
            ],
            [
                502,
                null,
                new BadResponseException(
                    message: 'Unexpected Response Code [502]',
                    code: 502,
                ),
            ],
            [
                503,
                null,
                new BadResponseException(
                    message: 'Unexpected Response Code [503]',
                    code: 503,
                ),
            ],
            [
                400,
                '{"message": "Invalid Payload"}',
                new BadRequestException(
                    message: 'Invalid Payload',
                    code: 400,
                    errors: [
                        'Invalid Payload',
                    ],
                ),
            ],
            [
                400,
                '{"message": "Invalid Payload", "errors": ["Error message"]}',
                new BadRequestException(
                    message: 'Invalid Payload',
                    code: 400,
                    errors: [
                        'Invalid Payload',
                        'Error message',
                    ],
                ),
            ],
            [
                500,
                '{"message": "Invalid Payload"}',
                new BadResponseException(
                    message: 'Invalid Payload',
                    code: 500,
                    errors: [
                        'Invalid Payload',
                    ],
                ),
            ],
            [
                500,
                '{"message": "Invalid Payload", "errors": ["Error message"]}',
                new BadResponseException(
                    message: 'Invalid Payload',
                    code: 500,
                    errors: [
                        'Invalid Payload',
                        'Error message',
                    ],
                ),
            ],
            [
                400,
                '{"message": "Invalid Payload", "code": "INVALID_PAYLOAD", "path": "/collect", "debug": "Debug message"}', // phpcs:ignore Generic.Files.LineLength.TooLong
                new BadRequestException(
                    message: 'Invalid Payload',
                    code: 400,
                    errors: [
                        'Invalid Payload',
                    ],
                    apiCode: 'INVALID_PAYLOAD',
                    path: '/collect',
                    debug: [
                        'Debug message',
                    ],
                ),
            ],
            [
                500,
                '{"message": "Invalid Payload", "code": "INVALID_PAYLOAD", "path": "/collect", "debug": "Debug message"}', // phpcs:ignore Generic.Files.LineLength.TooLong
                new BadResponseException(
                    message: 'Invalid Payload',
                    code: 500,
                    errors: [
                        'Invalid Payload',
                    ],
                    apiCode: 'INVALID_PAYLOAD',
                    path: '/collect',
                    debug: [
                        'Debug message',
                    ],
                ),
            ],
            [
                400,
                '{"message": ["Invalid Payload", "message"], "code": ["INVALID_PAYLOAD", "ERR"], "path": ["/collect", "/path"], "debug": ["Debug message", "debug"]}', // phpcs:ignore Generic.Files.LineLength.TooLong
                new BadRequestException(
                    message: 'Invalid Payload, message',
                    code: 400,
                    errors: [
                        'Invalid Payload',
                        'message',
                    ],
                    apiCode: 'INVALID_PAYLOAD, ERR',
                    path: '/collect, /path',
                    debug: [
                        'Debug message',
                        'debug',
                    ],
                ),
            ],
            [
                500,
                '{"message": ["Invalid Payload", "message"], "code": ["INVALID_PAYLOAD", "ERR"], "path": ["/collect", "/path"], "debug": ["Debug message", "debug"]}', // phpcs:ignore Generic.Files.LineLength.TooLong
                new BadResponseException(
                    message: 'Invalid Payload, message',
                    code: 500,
                    errors: [
                        'Invalid Payload',
                        'message',
                    ],
                    apiCode: 'INVALID_PAYLOAD, ERR',
                    path: '/collect, /path',
                    debug: [
                        'Debug message',
                        'debug',
                    ],
                ),
            ],
            [
                400,
                '{"status":400,"code":"VALIDATION_ERROR","message":"The request was invalid. Please check the provided payload for errors.","debug":[{"message":"Attribute property: label must not be blank."}]}', // phpcs:ignore Generic.Files.LineLength.TooLong
                new BadRequestException(
                    message: 'The request was invalid. Please check the provided payload for errors.',
                    code: 400,
                    errors: [
                        'The request was invalid. Please check the provided payload for errors.',
                    ],
                    apiCode: 'VALIDATION_ERROR',
                    path: null,
                    debug: [
                        'Attribute property: label must not be blank.',
                    ],
                ),
            ],
            [
                500,
                '{"status":400,"code":"VALIDATION_ERROR","message":"The request was invalid. Please check the provided payload for errors.","debug":[{"message":"Attribute property: label must not be blank."}]}', // phpcs:ignore Generic.Files.LineLength.TooLong
                new BadResponseException(
                    message: 'The request was invalid. Please check the provided payload for errors.',
                    code: 500,
                    errors: [
                        'The request was invalid. Please check the provided payload for errors.',
                    ],
                    apiCode: 'VALIDATION_ERROR',
                    path: null,
                    debug: [
                        'Attribute property: label must not be blank.',
                    ],
                ),
            ],
            // KS-22130
            [
                500,
                '{"status":400,"code":"VALIDATION_ERROR","message":"The request was invalid. Please check the provided payload for errors.","debug":[{"message":["The provided record URL is not included in the list of allowed base URLs."]}]}', // phpcs:ignore Generic.Files.LineLength.TooLong
                new BadResponseException(
                    message: 'The request was invalid. Please check the provided payload for errors.',
                    code: 500,
                    errors: [
                        'The request was invalid. Please check the provided payload for errors.',
                    ],
                    apiCode: 'VALIDATION_ERROR',
                    path: null,
                    debug: [
                        'The provided record URL is not included in the list of allowed base URLs.',
                    ],
                ),
            ],
            [
                500,
                '{"status":400,"code":"VALIDATION_ERROR","message":"The request was invalid. Please check the provided payload for errors.","debug":[{"message":["Debug 1", "Debug 2"]}]}', // phpcs:ignore Generic.Files.LineLength.TooLong
                new BadResponseException(
                    message: 'The request was invalid. Please check the provided payload for errors.',
                    code: 500,
                    errors: [
                        'The request was invalid. Please check the provided payload for errors.',
                    ],
                    apiCode: 'VALIDATION_ERROR',
                    path: null,
                    debug: [
                        'Debug 1',
                        'Debug 2',
                    ],
                ),
            ],
            [
                500,
                '{"status":400,"code":"VALIDATION_ERROR","message":"The request was invalid. Please check the provided payload for errors.","debug":[{"message":["Debug 1a", "Debug 1b"]},{"message":"Debug 2"},{"message":["Debug 3a"]}]}', // phpcs:ignore Generic.Files.LineLength.TooLong
                new BadResponseException(
                    message: 'The request was invalid. Please check the provided payload for errors.',
                    code: 500,
                    errors: [
                        'The request was invalid. Please check the provided payload for errors.',
                    ],
                    apiCode: 'VALIDATION_ERROR',
                    path: null,
                    debug: [
                        'Debug 1a',
                        'Debug 1b',
                        'Debug 2',
                        'Debug 3a',
                    ],
                ),
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testCreateFromResponse_RequireValidJsonBody')]
    public function testCreateFromResponse_RequireValidJsonBody(
        int $responseCode,
        ?string $responseBody,
        ?ApiExceptionInterface $expectedException,
    ): void {
        $jsonExceptionFactory = new JsonExceptionFactory(
            requireValidJsonBody: true,
        );

        $this->assertEquals(
            expected: $expectedException,
            actual: $jsonExceptionFactory->createFromResponse(
                responseCode: $responseCode,
                responseBody: $responseBody,
            ),
        );
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testCreateFromResponse_NotRequireValidJsonBody(): array
    {
        return [
            [
                200,
                null,
                null,
            ],
            [
                200,
                'Invalid Response Body',
                null,
            ],
            [
                400,
                null,
                new BadRequestException(
                    message: 'API request rejected by Klevu API [400]',
                    code: 400,
                ),
            ],
            [
                401,
                null,
                new BadRequestException(
                    message: 'API request rejected by Klevu API [401]',
                    code: 401,
                ),
            ],
            [
                403,
                null,
                new BadRequestException(
                    message: 'API request rejected by Klevu API [403]',
                    code: 403,
                ),
            ],
            [
                404,
                null,
                new BadRequestException(
                    message: 'API request rejected by Klevu API [404]',
                    code: 404,
                ),
            ],
            [
                499,
                null,
                new BadResponseException(
                    message: 'Unexpected Response Code [499]',
                    code: 499,
                ),
            ],
            [
                500,
                null,
                new BadResponseException(
                    message: 'Unexpected Response Code [500]',
                    code: 500,
                ),
            ],
            [
                501,
                null,
                new BadResponseException(
                    message: 'Unexpected Response Code [501]',
                    code: 501,
                ),
            ],
            [
                502,
                null,
                new BadResponseException(
                    message: 'Unexpected Response Code [502]',
                    code: 502,
                ),
            ],
            [
                503,
                null,
                new BadResponseException(
                    message: 'Unexpected Response Code [503]',
                    code: 503,
                ),
            ],
            [
                400,
                '{"message": "Invalid Payload"}',
                new BadRequestException(
                    message: 'Invalid Payload',
                    code: 400,
                    errors: [
                        'Invalid Payload',
                    ],
                ),
            ],
            [
                400,
                '{"message": "Invalid Payload", "errors": ["Error message"]}',
                new BadRequestException(
                    message: 'Invalid Payload',
                    code: 400,
                    errors: [
                        'Invalid Payload',
                        'Error message',
                    ],
                ),
            ],
            [
                500,
                '{"message": "Invalid Payload"}',
                new BadResponseException(
                    message: 'Invalid Payload',
                    code: 500,
                    errors: [
                        'Invalid Payload',
                    ],
                ),
            ],
            [
                500,
                '{"message": "Invalid Payload", "errors": ["Error message"]}',
                new BadResponseException(
                    message: 'Invalid Payload',
                    code: 500,
                    errors: [
                        'Invalid Payload',
                        'Error message',
                    ],
                ),
            ],
            [
                400,
                '{"message": "Invalid Payload", "code": "INVALID_PAYLOAD", "path": "/collect", "debug": "Debug message"}', // phpcs:ignore Generic.Files.LineLength.TooLong
                new BadRequestException(
                    message: 'Invalid Payload',
                    code: 400,
                    errors: [
                        'Invalid Payload',
                    ],
                    apiCode: 'INVALID_PAYLOAD',
                    path: '/collect',
                    debug: [
                        'Debug message',
                    ],
                ),
            ],
            [
                500,
                '{"message": "Invalid Payload", "code": "INVALID_PAYLOAD", "path": "/collect", "debug": "Debug message"}', // phpcs:ignore Generic.Files.LineLength.TooLong
                new BadResponseException(
                    message: 'Invalid Payload',
                    code: 500,
                    errors: [
                        'Invalid Payload',
                    ],
                    apiCode: 'INVALID_PAYLOAD',
                    path: '/collect',
                    debug: [
                        'Debug message',
                    ],
                ),
            ],
            [
                400,
                '{"message": ["Invalid Payload", "message"], "code": ["INVALID_PAYLOAD", "ERR"], "path": ["/collect", "/path"], "debug": ["Debug message", "debug"]}', // phpcs:ignore Generic.Files.LineLength.TooLong
                new BadRequestException(
                    message: 'Invalid Payload, message',
                    code: 400,
                    errors: [
                        'Invalid Payload',
                        'message',
                    ],
                    apiCode: 'INVALID_PAYLOAD, ERR',
                    path: '/collect, /path',
                    debug: [
                        'Debug message',
                        'debug',
                    ],
                ),
            ],
            [
                500,
                '{"message": ["Invalid Payload", "message"], "code": ["INVALID_PAYLOAD", "ERR"], "path": ["/collect", "/path"], "debug": ["Debug message", "debug"]}', // phpcs:ignore Generic.Files.LineLength.TooLong
                new BadResponseException(
                    message: 'Invalid Payload, message',
                    code: 500,
                    errors: [
                        'Invalid Payload',
                        'message',
                    ],
                    apiCode: 'INVALID_PAYLOAD, ERR',
                    path: '/collect, /path',
                    debug: [
                        'Debug message',
                        'debug',
                    ],
                ),
            ],
            [
                400,
                '{"status":400,"code":"VALIDATION_ERROR","message":"The request was invalid. Please check the provided payload for errors.","debug":[{"message":"Attribute property: label must not be blank."}]}', // phpcs:ignore Generic.Files.LineLength.TooLong
                new BadRequestException(
                    message: 'The request was invalid. Please check the provided payload for errors.',
                    code: 400,
                    errors: [
                        'The request was invalid. Please check the provided payload for errors.',
                    ],
                    apiCode: 'VALIDATION_ERROR',
                    path: null,
                    debug: [
                        'Attribute property: label must not be blank.',
                    ],
                ),
            ],
            [
                500,
                '{"status":400,"code":"VALIDATION_ERROR","message":"The request was invalid. Please check the provided payload for errors.","debug":[{"message":"Attribute property: label must not be blank."}]}', // phpcs:ignore Generic.Files.LineLength.TooLong
                new BadResponseException(
                    message: 'The request was invalid. Please check the provided payload for errors.',
                    code: 500,
                    errors: [
                        'The request was invalid. Please check the provided payload for errors.',
                    ],
                    apiCode: 'VALIDATION_ERROR',
                    path: null,
                    debug: [
                        'Attribute property: label must not be blank.',
                    ],
                ),
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testCreateFromResponse_NotRequireValidJsonBody')]
    public function testCreateFromResponse_NotRequireValidJsonBody(
        int $responseCode,
        ?string $responseBody,
        ?ApiExceptionInterface $expectedException,
    ): void {
        $jsonExceptionFactory = new JsonExceptionFactory(
            requireValidJsonBody: false,
        );

        $this->assertEquals(
            expected: $expectedException,
            actual: $jsonExceptionFactory->createFromResponse(
                responseCode: $responseCode,
                responseBody: $responseBody,
            ),
        );
    }

    #[Test]
    #[DataProvider('dataProvider_testCreateFromResponse_RequireValidJsonBody')]
    public function testCreateFromResponse(
        int $responseCode,
        ?string $responseBody,
        ?ApiExceptionInterface $expectedException,
    ): void {
        $jsonExceptionFactory = new JsonExceptionFactory();

        $this->assertEquals(
            expected: $expectedException,
            actual: $jsonExceptionFactory->createFromResponse(
                responseCode: $responseCode,
                responseBody: $responseBody,
            ),
        );
    }
}
