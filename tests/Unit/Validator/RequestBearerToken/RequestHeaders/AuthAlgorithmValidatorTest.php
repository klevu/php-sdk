<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Validator\RequestBearerToken\RequestHeaders;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\Indexing\AuthAlgorithms;
use Klevu\PhpSDK\Validator\RequestBearerToken\RequestHeaders\AuthAlgorithmValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(AuthAlgorithmValidator::class)]
class AuthAlgorithmValidatorTest extends TestCase
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
        $authAlgorithmValidator = new AuthAlgorithmValidator();

        $this->expectException(InvalidTypeValidationException::class);
        try {
            $authAlgorithmValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Auth Algorithm header value',
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
        $authAlgorithmValidator = new AuthAlgorithmValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $authAlgorithmValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Auth Algorithm header value',
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
    #[TestWith([['HmacSHA384', 'HmacSHA256']])]
    public function testExecute_ConflictingValues(array $data): void
    {
        $authAlgorithmValidator = new AuthAlgorithmValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $authAlgorithmValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Conflicting Auth Algorithm header value',
                haystack: $error,
            );
            throw $exception;
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function testExecute_UnsupportedAlgorithm(): void
    {
        $authAlgorithmValidator = new AuthAlgorithmValidator(
            supportedAlgorithms: [],
        );

        $this->expectException(InvalidDataValidationException::class);
        try {
            $authAlgorithmValidator->execute('md5');
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Auth Algorithm header value',
                haystack: $error,
            );
            throw $exception;
        }
    }

    /**
     * @param AuthAlgorithms[]|null $supportedAlgorithms
     * @param mixed $data
     *
     * @return void
     */
    #[Test]
    #[TestWith([null, 'HmacSHA384'])]
    #[TestWith([[AuthAlgorithms::HMAC_SHA384], 'HmacSHA384'])]
    public function testExecute_Valid(
        ?array $supportedAlgorithms,
        mixed $data,
    ): void {
        if (null === $supportedAlgorithms) {
            $authAlgorithmValidator = new AuthAlgorithmValidator();
        } else {
            $authAlgorithmValidator = new AuthAlgorithmValidator(
                supportedAlgorithms: $supportedAlgorithms,
            );
        }

        $this->expectNotToPerformAssertions();
        $authAlgorithmValidator->execute($data);
    }

    #[Test]
    public function testExecute_Consecutive_Valid(): void
    {
        $authAlgorithmValidator = new AuthAlgorithmValidator();

        $this->expectNotToPerformAssertions();

        $authAlgorithmValidator->execute(['HmacSHA384']);
        $authAlgorithmValidator->execute('HmacSHA384');
        $authAlgorithmValidator->execute(['HmacSHA384']);
    }

    #[Test]
    public function testExecute_Consecutive_Invalid(): void
    {
        $authAlgorithmValidator = new AuthAlgorithmValidator();

        $this->expectException(InvalidTypeValidationException::class);

        $authAlgorithmValidator->execute(['HmacSHA384']);
        $authAlgorithmValidator->execute('HmacSHA384');
        $authAlgorithmValidator->execute([['HmacSHA384']]);
    }
}
