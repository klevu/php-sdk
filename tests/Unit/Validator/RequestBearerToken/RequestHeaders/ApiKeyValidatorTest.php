<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Validator\RequestBearerToken\RequestHeaders;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Validator\RequestBearerToken\RequestHeaders\ApiKeyValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(ApiKeyValidator::class)]
class ApiKeyValidatorTest extends TestCase
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
            [null],
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
        $apiKeyValidator = new ApiKeyValidator();

        $this->expectException(InvalidTypeValidationException::class);
        try {
            $apiKeyValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'API Key',
                haystack: $error,
            );
            throw $exception;
        }
    }

    /**
     * @param string|string[] $data
     *
     * @return void
     */
    #[Test]
    #[TestWith([''])]
    #[TestWith([' '])]
    #[TestWith(['1234567890'])]
    #[TestWith(['KLEVU-1234567890'])]
    #[TestWith([' klevu-1234567890 '])]
    #[TestWith(['klevu -1234567890'])]
    #[TestWith(['klevu-'])]
    #[TestWith(['klevu-12345678901234567890123456789012345678901234567890'])]
    #[TestWith([['']])]
    #[TestWith([[' ']])]
    #[TestWith([['1234567890']])]
    #[TestWith([['KLEVU-1234567890']])]
    #[TestWith([[' klevu-1234567890 ']])]
    #[TestWith([['klevu -1234567890']])]
    #[TestWith([['klevu-']])]
    #[TestWith([['klevu-12345678901234567890123456789012345678901234567890']])]
    #[TestWith([['klevu-1234567890', 'klevu-9876543210']])]
    #[TestWith([[null]])]
    #[TestWith([[[]]])]
    public function testExecute_InvalidData(string|array $data): void
    {
        $apiKeyValidator = new ApiKeyValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $apiKeyValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'API Key',
                haystack: $error,
            );
            throw $exception;
        }
    }

    /**
     * @param string|string[] $data
     *
     * @return void
     */
    #[Test]
    #[TestWith(['klevu-1'])]
    #[TestWith(['klevu-1234567890'])]
    #[TestWith(['klevu-9876543210123456789'])]
    #[TestWith([['klevu-1']])]
    #[TestWith([['klevu-1234567890', 'klevu-1234567890']])]
    public function testExecute_Valid(string|array $data): void
    {
        $apiKeyValidator = new ApiKeyValidator();

        $this->expectNotToPerformAssertions();
        $apiKeyValidator->execute($data);
    }

    #[Test]
    public function testExecute_Consecutive_Valid(): void
    {
        $apiKeyValidator = new ApiKeyValidator();

        $this->expectNotToPerformAssertions();

        $apiKeyValidator->execute(['klevu-1234567890']);
        $apiKeyValidator->execute('klevu-9876543210');
        $apiKeyValidator->execute(['klevu-1111111111']);
    }

    #[Test]
    public function testExecute_Consecutive_Invalid(): void
    {
        $apiKeyValidator = new ApiKeyValidator();

        $this->expectException(InvalidTypeValidationException::class);

        $apiKeyValidator->execute(['klevu-1234567890']);
        $apiKeyValidator->execute('klevu-9876543210');
        $apiKeyValidator->execute([['klevu-1111111111']]);
    }
}
