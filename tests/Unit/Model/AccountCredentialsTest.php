<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Model;

use Klevu\PhpSDK\Model\AccountCredentials;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AccountCredentials::class)]
class AccountCredentialsTest extends TestCase
{
    #[Test]
    public function testConstruct_Valid(): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->assertSame('klevu-1234567890', $accountCredentials->jsApiKey);
        $this->assertSame('ABCDE1234567890', $accountCredentials->restAuthKey);
    }

    #[Test]
    public function testReadonly_JsApiKey(): void
    {
        /** @noinspection PhpNamedArgumentsWithChangedOrderInspection */
        $accountCredentials = new AccountCredentials(
            restAuthKey: 'ABCDE1234567890',
            jsApiKey: 'klevu-1234567890',
        );

        $this->expectException(\Error::class);
        /** @noinspection PhpReadonlyPropertyWrittenOutsideDeclarationScopeInspection */
        $accountCredentials->jsApiKey = 'klevu-9876543210';
    }

    #[Test]
    public function testReadonly_RestApiKey(): void
    {
        $accountCredentials = new AccountCredentials(
            jsApiKey: 'klevu-1234567890',
            restAuthKey: 'ABCDE1234567890',
        );

        $this->expectException(\Error::class);
        /** @noinspection PhpReadonlyPropertyWrittenOutsideDeclarationScopeInspection */
        $accountCredentials->restAuthKey = 'EDDCBA1234567890';
    }
}
