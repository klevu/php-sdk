<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

/**
 * @noinspection HttpUrlsUsage
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Validator\Account;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\Platforms;
use Klevu\PhpSDK\Validator\Account\UpdateStoreFeedUrlPayloadValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(UpdateStoreFeedUrlPayloadValidator::class)]
class UpdateStoreFeedUrlPayloadValidatorTest extends TestCase
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
            [null],
            [true],
            [static fn () => 'foo'],
            [(object)['foo' => 'bar']],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidType')] // Data provider used as includes closure
    public function testExecute_InvalidType(mixed $data): void
    {
        $updateStoreFeedUrlPayloadValidator = new UpdateStoreFeedUrlPayloadValidator();

        $this->expectException(InvalidTypeValidationException::class);
        try {
            $updateStoreFeedUrlPayloadValidator->execute($data);
        } catch (ValidationException $e) {
            $this->assertCount(1, $e->getErrors());
            throw $e;
        }
    }

    /**
     * @return mixed[][][]
     */
    public static function dataProvider_testExecute_Valid(): array
    {
        return [
            [
                [
                    'indexingUrl' => 'https://www.klevu.com/my-feed.xml',
                    'storeType' => Platforms::SHOPIFY->value,
                    'storeUrl' => 'www.klevu.com',
                ],
            ],
            [
                [
                    'indexingUrl' => 'http://www.klevu.com/my-feed.xml',
                    'storeType' => Platforms::BIGCOMMERCE->value,
                    'storeUrl' => 'Klevu BigCommerce Test Store',
                ],
            ],
        ];
    }

    /**
     * @param mixed[] $data
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testExecute_Valid')]
    public function testExecute_Valid(array $data): void
    {
        $updateStoreFeedUrlPayloadValidator = new UpdateStoreFeedUrlPayloadValidator();

        $this->expectNotToPerformAssertions();
        $updateStoreFeedUrlPayloadValidator->execute($data);
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_Invalid_IndexingUrl(): array
    {
        return [
            [
                [
                    // Missing indexingUrl
                    'storeType' => Platforms::SHOPIFY->value,
                    'storeUrl' => 'www.klevu.com',
                ],
                'Payload must contain indexingUrl',
            ],
            [
                [
                    'indexingUrl' => 42, // Invalid type
                    'storeType' => Platforms::SHOPIFY->value,
                    'storeUrl' => 'www.klevu.com',
                ],
                'indexingUrl must be string',
            ],
            [
                [
                    'indexingUrl' => '', // Empty value
                    'storeType' => Platforms::SHOPIFY->value,
                    'storeUrl' => 'www.klevu.com',
                ],
                'indexingUrl must not be empty',
            ],
            [
                [
                    'indexingUrl' => '    ', // Empty value after trim
                    'storeType' => Platforms::SHOPIFY->value,
                    'storeUrl' => 'www.klevu.com',
                ],
                'indexingUrl must not be empty',
            ],
            [
                [
                    'indexingUrl' => 'foo', // Not a URL
                    'storeType' => Platforms::SHOPIFY->value,
                    'storeUrl' => 'www.klevu.com',
                ],
                'indexingUrl must be valid URL',
            ],
        ];
    }

    /**
     * @param mixed[] $data
     * @param string $expectedErrorMessage
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testExecute_Invalid_IndexingUrl')]
    public function testExecute_Invalid_IndexingUrl(
        array $data,
        string $expectedErrorMessage,
    ): void {
        $updateStoreFeedUrlPayloadValidator = new UpdateStoreFeedUrlPayloadValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $updateStoreFeedUrlPayloadValidator->execute($data);
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertCount(1, $errors);
            $this->assertSame($expectedErrorMessage, current($errors));
            throw $e;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_Invalid_StoreType(): array
    {
        return [
            [
                [
                    'indexingUrl' => 'https://www.klevu.com/my-feed.xml',
                    // Missing storeType
                    'storeUrl' => 'www.klevu.com',
                ],
                'Payload must contain storeType',
            ],
            [
                [
                    'indexingUrl' => 'https://www.klevu.com/my-feed.xml',
                    'storeType' => 42, // Invalid type
                    'storeUrl' => 'www.klevu.com',
                ],
                'storeType must be string',
            ],
            [
                [
                    'indexingUrl' => 'https://www.klevu.com/my-feed.xml',
                    'storeType' => 'foo', // Unrecognised platform
                    'storeUrl' => 'www.klevu.com',
                ],
                'Store of type "foo" cannot define custom feed URLs',
            ],
            [
                [
                    'indexingUrl' => 'https://www.klevu.com/my-feed.xml',
                    'storeType' => Platforms::MAGENTO->value, // Not applicable platform
                    'storeUrl' => 'www.klevu.com',
                ],
                'Store of type "magento" cannot define custom feed URLs',
            ],
        ];
    }

    /**
     * @param mixed[] $data
     * @param string $expectedErrorMessage
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testExecute_Invalid_StoreType')]
    public function testExecute_Invalid_StoreType(
        array $data,
        string $expectedErrorMessage,
    ): void {
        $updateStoreFeedUrlPayloadValidator = new UpdateStoreFeedUrlPayloadValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $updateStoreFeedUrlPayloadValidator->execute($data);
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertCount(1, $errors);
            $this->assertSame($expectedErrorMessage, current($errors));
            throw $e;
        }
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_Invalid_StoreUrl(): array
    {
        return [
            [
                [
                    'indexingUrl' => 'https://www.klevu.com/my-feed.xml',
                    'storeType' => Platforms::SHOPIFY->value,
                    // Missing storeUrl
                ],
                'Payload must contain storeUrl',
            ],
            [
                [
                    'indexingUrl' => 'https://www.klevu.com/my-feed.xml',
                    'storeType' => Platforms::SHOPIFY->value,
                    'storeUrl' => 42, // Invalid type
                ],
                'storeUrl must be string',
            ],
            [
                [
                    'indexingUrl' => 'https://www.klevu.com/my-feed.xml',
                    'storeType' => Platforms::SHOPIFY->value,
                    'storeUrl' => '', // Empty value
                ],
                'storeUrl must not be empty',
            ],
            [
                [
                    'indexingUrl' => 'https://www.klevu.com/my-feed.xml',
                    'storeType' => Platforms::SHOPIFY->value,
                    'storeUrl' => '       ', // Empty value
                ],
                'storeUrl must not be empty',
            ],
        ];
    }

    /**
     * @param mixed[] $data
     * @param string $expectedErrorMessage
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testExecute_Invalid_StoreUrl')]
    public function testExecute_Invalid_StoreUrl(
        array $data,
        string $expectedErrorMessage,
    ): void {
        $updateStoreFeedUrlPayloadValidator = new UpdateStoreFeedUrlPayloadValidator();

        $this->expectException(InvalidDataValidationException::class);
        try {
            $updateStoreFeedUrlPayloadValidator->execute($data);
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertCount(1, $errors);
            $this->assertSame($expectedErrorMessage, current($errors));
            throw $e;
        }
    }
}
