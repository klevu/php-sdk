<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Validator\RequestBearerToken;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Provider\RequestBearerTokenProviderInterface;
use Klevu\PhpSDK\Validator\RequestBearerToken\RequestHeadersValidator;
use Klevu\PhpSDK\Validator\ValidatorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(RequestHeadersValidator::class)]
class RequestHeadersValidatorTest extends TestCase
{
    #[Test]
    public function testConstruct_InjectedValidators(): void
    {
        $mockHeaderValidators = [
            RequestBearerTokenProviderInterface::API_HEADER_KEY_TIMESTAMP =>
                $this->getMockBuilder(ValidatorInterface::class)->getMock(),
            RequestBearerTokenProviderInterface::API_HEADER_KEY_APIKEY =>
                $this->getMockBuilder(ValidatorInterface::class)->getMock(),
            RequestBearerTokenProviderInterface::API_HEADER_KEY_AUTH_ALGO =>
                $this->getMockBuilder(ValidatorInterface::class)->getMock(),
            RequestBearerTokenProviderInterface::API_HEADER_KEY_CONTENT_TYPE =>
                $this->getMockBuilder(ValidatorInterface::class)->getMock(),
        ];
        array_walk(
            $mockHeaderValidators,
            function (MockObject&ValidatorInterface $validator): void {
                $validator->expects($this->atLeast(1))
                    ->method('execute');
            },
        );

        $headers = [
            RequestBearerTokenProviderInterface::API_HEADER_KEY_CONTENT_TYPE => [
                'application/json',
            ],
            RequestBearerTokenProviderInterface::API_HEADER_KEY_APIKEY => [
                'klevu-1234567890',
            ],
            RequestBearerTokenProviderInterface::API_HEADER_KEY_TIMESTAMP => [
                date('c'),
            ],
            RequestBearerTokenProviderInterface::API_HEADER_KEY_AUTH_ALGO => [
                'HmacSHA384',
            ],
        ];

        $requestHeadersValidator = new RequestHeadersValidator(
            headerValidators: $mockHeaderValidators,
        );
        $requestHeadersValidator->execute($headers);
    }

    #[Test]
    public function testConstruct_InjectedValidators_Empty(): void
    {
        $headers = [
            RequestBearerTokenProviderInterface::API_HEADER_KEY_CONTENT_TYPE => [
                'application/xml',
            ],
            RequestBearerTokenProviderInterface::API_HEADER_KEY_APIKEY => [
                'foo',
            ],
            RequestBearerTokenProviderInterface::API_HEADER_KEY_TIMESTAMP => [
                '1970-01-01',
            ],
            RequestBearerTokenProviderInterface::API_HEADER_KEY_AUTH_ALGO => [
                'md5',
            ],
        ];

        $requestHeadersValidator = new RequestHeadersValidator(
            headerValidators: [],
        );

        $this->expectNotToPerformAssertions();
        $requestHeadersValidator->execute($headers);
    }

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
            [null],
            [static fn () => 'foo'],
            [(object)['foo' => 'bar']],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidType')]
    public function testExecute_InvalidType(mixed $data): void
    {
        $requestHeadersValidator = new RequestHeadersValidator();

        $this->expectException(InvalidTypeValidationException::class);
        try {
            $requestHeadersValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            throw $exception;
        }
    }

    #[Test]
    public function testExecute_InvalidData(): void
    {
        $headers = [
            RequestBearerTokenProviderInterface::API_HEADER_KEY_CONTENT_TYPE => [
                'application/xml',
            ],
            RequestBearerTokenProviderInterface::API_HEADER_KEY_APIKEY => [
                'foo',
            ],
            RequestBearerTokenProviderInterface::API_HEADER_KEY_TIMESTAMP => [
                '1970-01-01',
            ],
            RequestBearerTokenProviderInterface::API_HEADER_KEY_AUTH_ALGO => [
                'md5',
            ],
        ];

        $requestHeadersValidator = new RequestHeadersValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $requestHeadersValidator->execute($headers);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(4, $errors);

            $this->assertCount(
                expectedCount: 1,
                haystack: array_filter($errors, static fn (string $error): bool => str_contains(
                    haystack: $error,
                    needle: RequestBearerTokenProviderInterface::API_HEADER_KEY_CONTENT_TYPE,
                )),
            );
            $this->assertCount(
                expectedCount: 1,
                haystack: array_filter($errors, static fn (string $error): bool => str_contains(
                    haystack: $error,
                    needle: RequestBearerTokenProviderInterface::API_HEADER_KEY_APIKEY,
                )),
            );
            $this->assertCount(
                expectedCount: 1,
                haystack: array_filter($errors, static fn (string $error): bool => str_contains(
                    haystack: $error,
                    needle: RequestBearerTokenProviderInterface::API_HEADER_KEY_AUTH_ALGO,
                )),
            );
            $this->assertCount(
                expectedCount: 1,
                haystack: array_filter($errors, static fn (string $error): bool => str_contains(
                    haystack: $error,
                    needle: RequestBearerTokenProviderInterface::API_HEADER_KEY_TIMESTAMP,
                )),
            );

            throw $exception;
        }
    }

    #[Test]
    public function testExecute_Valid(): void
    {
        $headers = [
            RequestBearerTokenProviderInterface::API_HEADER_KEY_CONTENT_TYPE => [
                'application/json',
            ],
            RequestBearerTokenProviderInterface::API_HEADER_KEY_APIKEY => [
                'klevu-1234567890',
            ],
            RequestBearerTokenProviderInterface::API_HEADER_KEY_TIMESTAMP => [
                date('c'),
            ],
            RequestBearerTokenProviderInterface::API_HEADER_KEY_AUTH_ALGO => [
                'HmacSHA384',
            ],
        ];

        $requestHeadersValidator = new RequestHeadersValidator();

        $this->expectNotToPerformAssertions();
        $requestHeadersValidator->execute($headers);
    }
}
