<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

/**
 * @noinspection DuplicatedCode
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Model;

use Klevu\PhpSDK\Model\AccountFactory;
use Klevu\PhpSDK\Model\Platforms;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AccountFactory::class)]
class AccountFactoryTest extends TestCase
{
    #[Test]
    public function testCreate(): void
    {
        $accountFactory = new AccountFactory();
        $account = $accountFactory->create([
            'jsApiKey' => 'klevu-1234567890',
            'restAuthKey' => 'ABCDE1234567890',
            'platform' => Platforms::SHOPIFY->value,
            'active' => true,
            'companyName' => 'Klevu',
            'email' => 'contact@klevu.com',
            'indexingUrl' => 'custom-indexing.klevu.com',
            'searchUrl' => 'eucs123.ksearchnet.com',
            'smartCategoryMerchandisingUrl' => 'cn123.ksearchnet.com',
            'analyticsUrl' => 'custom-stats.klevu.com',
            'jsUrl' => 'custom-js.klevu.com',
            'tiersUrl' => 'custom-tiers.klevu.com',
        ]);

        $this->assertSame('klevu-1234567890', $account->getJsApiKey());
        $this->assertSame('ABCDE1234567890', $account->getRestAuthKey());
        $this->assertSame('shopify', $account->getPlatform());
        $this->assertTrue($account->isActive());
        $this->assertSame('Klevu', $account->getCompanyName());
        $this->assertSame('contact@klevu.com', $account->getEmail());
        $this->assertSame('custom-indexing.klevu.com', $account->getIndexingUrl());
        $this->assertSame('eucs123.ksearchnet.com', $account->getSearchUrl());
        $this->assertSame('cn123.ksearchnet.com', $account->getSmartCategoryMerchandisingUrl());
        $this->assertSame('custom-stats.klevu.com', $account->getAnalyticsUrl());
        $this->assertSame('custom-js.klevu.com', $account->getJsUrl());
        $this->assertSame('custom-tiers.klevu.com', $account->getTiersUrl());
        $this->assertFalse($account->getAccountFeatures()->smartCategoryMerchandising);
        $this->assertFalse($account->getAccountFeatures()->smartRecommendations);
    }

    #[Test]
    public function testCreate_MissingData(): void
    {
        $accountFactory = new AccountFactory();
        $account = $accountFactory->create([
            'jsApiKey' => 'klevu-1234567890',
            'companyName' => 'Klevu',
            'email' => 'contact@klevu.com',
            'indexingUrl' => 'custom-indexing.klevu.com',
            'searchUrl' => 'eucs123.ksearchnet.com',
            'analyticsUrl' => 'custom-stats.klevu.com',
            'jsUrl' => 'custom-js.klevu.com',
        ]);

        $this->assertSame('klevu-1234567890', $account->getJsApiKey());
        $this->assertNull($account->getRestAuthKey());
        $this->assertNull($account->getPlatform());
        $this->assertFalse($account->isActive());
        $this->assertSame('Klevu', $account->getCompanyName());
        $this->assertSame('contact@klevu.com', $account->getEmail());
        $this->assertSame('custom-indexing.klevu.com', $account->getIndexingUrl());
        $this->assertSame('eucs123.ksearchnet.com', $account->getSearchUrl());
        $this->assertNull($account->getSmartCategoryMerchandisingUrl());
        $this->assertSame('custom-stats.klevu.com', $account->getAnalyticsUrl());
        $this->assertSame('custom-js.klevu.com', $account->getJsUrl());
        $this->assertNull($account->getTiersUrl());
        $this->assertFalse($account->getAccountFeatures()->smartCategoryMerchandising);
        $this->assertFalse($account->getAccountFeatures()->smartRecommendations);
    }

    #[Test]
    public function testCreate_AdditionalData(): void
    {
        $accountFactory = new AccountFactory();
        $account = $accountFactory->create([
            'jsApiKey' => 'klevu-1234567890',
            'restApiKey' => 'ABCDE1234567890', // Invalid key
            'companyName' => 'Klevu',
            'EMAIL' => 'contact@klevu.com', // Incorrect key case
            'indexingUrl' => 'custom-indexing.klevu.com',
            'searchUrl' => 'eucs123.ksearchnet.com',
            'catNavUrl' => 'cn123.ksearchnet.com', // Invalid key
            'analyticsUrl' => 'custom-stats.klevu.com',
            'jsUrl' => 'custom-js.klevu.com',
            'accountFeatures.smartCategoryMerchandising' => true, // This is not supported
            'foo' => 'bar', // Unrecognised key
        ]);

        $this->assertSame('klevu-1234567890', $account->getJsApiKey());
        $this->assertNull($account->getRestAuthKey());
        $this->assertNull($account->getPlatform());
        $this->assertFalse($account->isActive());
        $this->assertSame('Klevu', $account->getCompanyName());
        $this->assertNull($account->getEmail());
        $this->assertSame('custom-indexing.klevu.com', $account->getIndexingUrl());
        $this->assertSame('eucs123.ksearchnet.com', $account->getSearchUrl());
        $this->assertNull($account->getSmartCategoryMerchandisingUrl());
        $this->assertSame('custom-stats.klevu.com', $account->getAnalyticsUrl());
        $this->assertSame('custom-js.klevu.com', $account->getJsUrl());
        $this->assertNull($account->getTiersUrl());
        $this->assertFalse($account->getAccountFeatures()->smartCategoryMerchandising);
        $this->assertFalse($account->getAccountFeatures()->smartRecommendations);
    }
}
