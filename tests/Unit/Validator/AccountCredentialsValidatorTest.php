<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Validator;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\AccountCredentials;
use Klevu\PhpSDK\Validator\AccountCredentialsValidator;
use Klevu\PhpSDK\Validator\ValidatorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(AccountCredentialsValidator::class)]
class AccountCredentialsValidatorTest extends TestCase
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
            [['foo', 'bar']],
            [static fn () => 'foo'],
            [(object)['foo' => 'bar']],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testExecute_InvalidType')]
    public function testExecute_InvalidType(mixed $data): void
    {
        $mockJsApiKeyValidator = $this->getMockJsApiKeyValidator();
        $mockJsApiKeyValidator->expects($this->never())->method('execute');

        $mockRestAuthKeyValidator = $this->getMockRestAuthKeyValidator();
        $mockRestAuthKeyValidator->expects($this->never())->method('execute');

        $accountCredentialsValidator = new AccountCredentialsValidator(
            jsApiKeyValidator: $mockJsApiKeyValidator,
            restAuthKeyValidator: $mockRestAuthKeyValidator,
        );

        $this->expectException(InvalidTypeValidationException::class);
        try {
            $accountCredentialsValidator->execute($data);
        } catch (ValidationException $e) {
            $this->assertCount(1, $e->getErrors());
            throw $e;
        }
    }

    #[Test]
    public function testExecute_InvalidCredentials(): void
    {
        $jsApiKeyValidationException = new InvalidDataValidationException([
            'Invalid JS API Key',
        ]);
        $mockJsApiKeyValidator = $this->getMockJsApiKeyValidator();
        $mockJsApiKeyValidator->expects($this->once())
            ->method('execute')
            ->willThrowException($jsApiKeyValidationException);

        $restAuthKeyValidationException = new InvalidDataValidationException([
            'Invalid REST AUTH Key',
        ]);
        $mockRestAuthKeyValidator = $this->getMockRestAuthKeyValidator();
        $mockRestAuthKeyValidator->expects($this->once())
            ->method('execute')
            ->willThrowException($restAuthKeyValidationException);

        $accountCredentialsValidator = new AccountCredentialsValidator(
            jsApiKeyValidator: $mockJsApiKeyValidator,
            restAuthKeyValidator: $mockRestAuthKeyValidator,
        );

        $expectedErrors = [
            'Invalid JS API Key',
            'Invalid REST AUTH Key',
        ];
        $this->expectException(InvalidDataValidationException::class);
        try {
            $accountCredentials = new AccountCredentials(
                jsApiKey: 'klevu-1234567890',
                restAuthKey: 'ABCDE1234567890',
            );
            $accountCredentialsValidator->execute($accountCredentials);
        } catch (ValidationException $e) {
            $this->assertSame($expectedErrors, $e->getErrors());
            throw $e;
        }
    }

    #[Test]
    public function testExecute_Valid(): void
    {
        $mockJsApiKeyValidator = $this->getMockJsApiKeyValidator();
        $mockJsApiKeyValidator->expects($this->once())
            ->method('execute');

        $mockRestAuthKeyValidator = $this->getMockRestAuthKeyValidator();
        $mockRestAuthKeyValidator->expects($this->once())
            ->method('execute');

        $accountCredentialsValidator = new AccountCredentialsValidator(
            jsApiKeyValidator: $mockJsApiKeyValidator,
            restAuthKeyValidator: $mockRestAuthKeyValidator,
        );

        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );
        $accountCredentialsValidator->execute($accountCredentials);
    }

    /**
     * @return MockObject&ValidatorInterface
     */
    private function getMockJsApiKeyValidator(): MockObject&ValidatorInterface
    {
        return $this->getMockBuilder(ValidatorInterface::class)
            ->getMock();
    }

    /**
     * @return MockObject&ValidatorInterface
     */
    private function getMockRestAuthKeyValidator(): MockObject&ValidatorInterface
    {
        return $this->getMockBuilder(ValidatorInterface::class)
            ->getMock();
    }
}
