<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

/**
 * @noinspection PhpObjectFieldsAreOnlyWrittenInspection
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Model\Account;

use Klevu\PhpSDK\Model\Account\AccountFeatures;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AccountFeatures::class)]
class AccountFeaturesTest extends TestCase
{
    #[Test]
    public function testConstruct_Valid(): void
    {
        $accountFeatures = new AccountFeatures(
            smartCategoryMerchandising: true,
            smartRecommendations: false,
            preserveLayout: true,
        );

        $this->assertTrue($accountFeatures->smartCategoryMerchandising);
        $this->assertFalse($accountFeatures->smartRecommendations);
        $this->assertTrue($accountFeatures->preserveLayout);
    }

    public function testConstruct_Valid_NoConstructorArgs(): void
    {
        $accountFeatures = new AccountFeatures();

        $this->assertFalse($accountFeatures->smartCategoryMerchandising);
        $this->assertFalse($accountFeatures->smartRecommendations);
        $this->assertFalse($accountFeatures->preserveLayout);
    }

    #[Test]
    public function testReadonly_CategoryNavigation(): void
    {
        $accountFeatures = new AccountFeatures(
            smartCategoryMerchandising: true,
            smartRecommendations: false,
            preserveLayout: true,
        );

        $this->expectException(\Error::class);
        /** @noinspection PhpReadonlyPropertyWrittenOutsideDeclarationScopeInspection */
        $accountFeatures->smartCategoryMerchandising = false;
    }

    #[Test]
    public function testReadonly_PersonalizedRecommendations(): void
    {
        $accountFeatures = new AccountFeatures(
            smartCategoryMerchandising: true,
            smartRecommendations: false,
            preserveLayout: true,
        );

        $this->expectException(\Error::class);
        /** @noinspection PhpReadonlyPropertyWrittenOutsideDeclarationScopeInspection */
        $accountFeatures->smartRecommendations = true;
    }

    #[Test]
    public function testReadonly_PreserveLayout(): void
    {
        $accountFeatures = new AccountFeatures(
            smartCategoryMerchandising: true,
            smartRecommendations: false,
            preserveLayout: true,
        );

        $this->expectException(\Error::class);
        /** @noinspection PhpReadonlyPropertyWrittenOutsideDeclarationScopeInspection */
        $accountFeatures->preserveLayout = false;
    }
}
