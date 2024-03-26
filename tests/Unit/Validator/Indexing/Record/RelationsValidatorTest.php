<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Validator\Indexing\Record;

use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Validator\Indexing\Record\RelationsValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RelationsValidator::class)]
class RelationsValidatorTest extends TestCase
{
    /**
     * @return mixed[][]
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public static function dataProvider_testExecute_InvalidType(): array
    {
        return [
            ['foo'],
            [42],
            [3.14],
            [true],
            [static fn () => 'foo'],
            [(object)['foo' => 'bar']],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidType')]
    public function testExecute_InvalidType(mixed $data): void
    {
        $relationsValidator = new RelationsValidator();

        $this->expectException(InvalidTypeValidationException::class);
        try {
            $relationsValidator->execute($data);
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertCount(1, $errors);

            $error = (string)current($errors);
            $this->assertStringContainsString(
                needle: 'Relations',
                haystack: $error,
            );
            throw $exception;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_Valid(): array
    {
        return [
            [
                null,
            ],
            [
                [],
            ],
            [
                [
                    'categories' => [
                        'type' => 'KLEVU_PRODUCT',
                        'values' => [
                            'foo',
                        ],
                    ],
                    'channels' => [
                        'additionalProp1' => [
                            'type' => 'KLEVU_PRODUCT',
                            'values' => [
                                'foo',
                            ],
                        ],
                    ],
                    'grouping' => [
                        'type' => 'KLEVU_PRODUCT',
                        'values' => [
                            'foo',
                        ],
                        'channels' => [
                            'additionalProp1' => [
                                'type' => 'KLEVU_PRODUCT',
                                'values' => [
                                    'foo',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param mixed[]|null $data
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testExecute_Valid')]
    public function testExecute_Valid(?array $data): void
    {
        $relationsValidator = new RelationsValidator();

        $this->expectNotToPerformAssertions();
        $relationsValidator->execute($data);
    }
}
