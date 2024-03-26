<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Validator\RequestBearerToken\RequestHeaders;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Validator\RequestBearerToken\RequestHeaders\ContentTypeValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContentTypeValidator::class)]
class ContentTypeValidatorTest extends TestCase
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
        $contentTypeValidator = new ContentTypeValidator();

        $this->expectException(InvalidTypeValidationException::class);
        try {
            $contentTypeValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Content Type header value',
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
        $contentTypeValidator = new ContentTypeValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $contentTypeValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Content Type header value',
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
    #[TestWith([['application/json', 'application/xml']])]
    public function testExecute_ConflictingValues(array $data): void
    {
        $contentTypeValidator = new ContentTypeValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $contentTypeValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Conflicting Content Type header value',
                haystack: $error,
            );
            throw $exception;
        }
    }

    /**
     * @param string[]|null $supportedContentTypes
     * @param mixed $data
     *
     * @return void
     */
    #[Test]
    #[TestWith([null, 'foo'])]
    #[TestWith([null, 'APPLICATION/JSON'])]
    #[TestWith([null, 'application/xml'])]
    #[TestWith([[], 'application/json'])]
    #[TestWith([null, ['foo']])]
    #[TestWith([null, ['APPLICATION/JSON']])]
    #[TestWith([null, ['application/xml']])]
    #[TestWith([[], ['application/json']])]
    public function testExecute_UnsupportedContentType(
        ?array $supportedContentTypes,
        mixed $data,
    ): void {
        if (null === $supportedContentTypes) {
            $contentTypeValidator = new ContentTypeValidator();
        } else {
            $contentTypeValidator = new ContentTypeValidator(
                supportedContentTypes: $supportedContentTypes,
            );
        }

        $this->expectException(InvalidDataValidationException::class);
        try {
            $contentTypeValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Content Type header value',
                haystack: $error,
            );
            throw $exception;
        }
    }

    /**
     * @param string[]|null $supportedContentTypes
     * @param mixed $data
     *
     * @return void
     */
    #[Test]
    #[TestWith([null, 'application/json'])]
    #[TestWith([['application/json', 'application/xml'], 'application/xml'])]
    #[TestWith([null, ['application/json']])]
    #[TestWith([null, ['application/json', 'application/json']])]
    #[TestWith([['application/json', 'application/xml'], ['application/xml']])]
    public function testExecute_Valid(
        ?array $supportedContentTypes,
        mixed $data,
    ): void {
        if (null === $supportedContentTypes) {
            $contentTypeValidator = new ContentTypeValidator();
        } else {
            $contentTypeValidator = new ContentTypeValidator(
                supportedContentTypes: $supportedContentTypes,
            );
        }

        $this->expectNotToPerformAssertions();
        $contentTypeValidator->execute($data);
    }

    #[Test]
    public function testExecute_Consecutive_Valid(): void
    {
        $contentTypeValidator = new ContentTypeValidator();

        $this->expectNotToPerformAssertions();

        $contentTypeValidator->execute(['application/json']);
        $contentTypeValidator->execute('application/json');
        $contentTypeValidator->execute(['application/json']);
    }

    #[Test]
    public function testExecute_Consecutive_Invalid(): void
    {
        $contentTypeValidator = new ContentTypeValidator();

        $this->expectException(InvalidTypeValidationException::class);

        $contentTypeValidator->execute(['application/json']);
        $contentTypeValidator->execute('application/json');
        $contentTypeValidator->execute([['application/json']]);
    }
}
