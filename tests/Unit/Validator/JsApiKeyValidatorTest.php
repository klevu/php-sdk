<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Validator;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Validator\JsApiKeyValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsApiKeyValidator::class)]
class JsApiKeyValidatorTest extends TestCase
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
            [['foo', 'bar']],
            [static fn () => 'foo'],
            [(object)['foo' => 'bar']],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidType')]
    public function testExecute_InvalidType(mixed $data): void
    {
        $jsApiKeyValidator = new JsApiKeyValidator();

        $this->expectException(InvalidTypeValidationException::class);
        try {
            $jsApiKeyValidator->execute($data);
        } catch (ValidationException $e) {
            $this->assertCount(1, $e->getErrors());
            throw $e;
        }
    }

    #[Test]
    #[TestWith([''])]
    #[TestWith([' '])]
    #[TestWith(['1234567890'])]
    #[TestWith(['KLEVU-1234567890'])]
    #[TestWith([' klevu-1234567890 '])]
    #[TestWith(['klevu -1234567890'])]
    #[TestWith(['klevu-'])]
    #[TestWith(['klevu-12345678901234567890123456789012345678901234567890'])]
    public function testExecute_InvalidData(string $data): void
    {
        $jsApiKeyValidator = new JsApiKeyValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $jsApiKeyValidator->execute($data);
        } catch (ValidationException $e) {
            $this->assertCount(1, $e->getErrors());
            throw $e;
        }
    }

    #[Test]
    #[TestWith(['klevu-1'])]
    #[TestWith(['klevu-1234567890'])]
    #[TestWith(['klevu-9876543210123456789'])]
    public function testExecute_Valid(string $data): void
    {
        $jsApiKeyValidator = new JsApiKeyValidator();

        $this->expectNotToPerformAssertions();
        $jsApiKeyValidator->execute($data);
    }
}
