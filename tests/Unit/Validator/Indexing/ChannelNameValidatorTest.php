<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Validator\Indexing;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Validator\Indexing\ChannelNameValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(ChannelNameValidator::class)]
class ChannelNameValidatorTest extends TestCase
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
            [[]],
            [['foo']],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidType')]
    public function testExecute_InvalidType(mixed $data): void
    {
        $channelNameValidator = new ChannelNameValidator();

        $this->expectException(InvalidTypeValidationException::class);
        try {
            $channelNameValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Channel Name',
                haystack: $error,
            );
            throw $exception;
        }
    }

    #[Test]
    #[TestWith([null])]
    #[TestWith([''])]
    #[TestWith([' '])]
    public function testExecute_Empty(mixed $data): void
    {
        $channelNameValidator = new ChannelNameValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $channelNameValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Channel Name is required',
                haystack: $error,
            );
            throw $exception;
        }
    }

    #[Test]
    #[TestWith(['product-name'])]
    #[TestWith(['foo!bar'])]
    public function testExecute_InvalidPattern(mixed $data): void
    {
        $channelNameValidator = new ChannelNameValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $channelNameValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Channel Name',
                haystack: $error,
            );
            throw $exception;
        }
    }

    #[Test]
    #[TestWith(['new_attribute'])]
    #[TestWith(['Attribute12345'])]
    #[TestWith(['aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'])]
    #[TestWith(['0'])]
    #[TestWith(['_foo'])]
    #[TestWith(['foo_'])]
    #[TestWith(['_foo_'])]
    public function testExecute_Valid(mixed $data): void
    {
        $channelNameValidator = new ChannelNameValidator();

        $this->expectNotToPerformAssertions();
        $channelNameValidator->execute($data);
    }
}
