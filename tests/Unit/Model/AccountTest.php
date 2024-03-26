<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Model;

use Klevu\PhpSDK\Api\Model\AccountInterface;
use Klevu\PhpSDK\Model\Account;
use Klevu\PhpSDK\Model\Account\AccountFeatures;
use Klevu\PhpSDK\Model\Platforms;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Account::class)]
class AccountTest extends TestCase
{
    #[Test]
    public function testImplementsInterface(): void
    {
        $account = new Account();

        $this->assertInstanceOf(AccountInterface::class, $account);
    }

    #[Test]
    public function testGetSet_JsApiKey(): void
    {
        $account = new Account();

        $this->assertNull($account->getJsApiKey());

        $account->setJsApiKey('klevu-1234567890');
        $this->assertSame('klevu-1234567890', $account->getJsApiKey());

        $account->setJsApiKey(null);
        $this->assertNull($account->getJsApiKey());
    }

    #[Test]
    public function testGetSet_RestAuthKey(): void
    {
        $account = new Account();

        $this->assertNull($account->getRestAuthKey());

        $account->setRestAuthKey('ABCDE1234567890');
        $this->assertSame('ABCDE1234567890', $account->getRestAuthKey());

        $account->setRestAuthKey(null);
        $this->assertNull($account->getRestAuthKey());
    }

    #[Test]
    public function testGetSet_Platform(): void
    {
        $account = new Account();

        $this->assertNull($account->getPlatform());

        $account->setPlatform(Platforms::CUSTOM->value);
        $this->assertSame('custom', $account->getPlatform());

        $account->setPlatform(null);
        $this->assertNull($account->getPlatform());
    }

    #[Test]
    public function testGetSet_IsActive(): void
    {
        $account = new Account();

        $this->assertFalse($account->isActive());

        $account->setActive(true);
        $this->assertTrue($account->isActive());
    }

    #[Test]
    public function testGetSet_CompanyName(): void
    {
        $account = new Account();

        $this->assertNull($account->getCompanyName());

        $account->setCompanyName('Klevu');
        $this->assertSame('Klevu', $account->getCompanyName());

        $account->setCompanyName(null);
        $this->assertNull($account->getCompanyName());
    }

    #[Test]
    public function testGetSet_Email(): void
    {
        $account = new Account();

        $this->assertNull($account->getEmail());

        $account->setEmail('contact@klevu.com');
        $this->assertSame('contact@klevu.com', $account->getEmail());

        $account->setEmail(null);
        $this->assertNull($account->getEmail());
    }

    #[Test]
    public function testGetSet_IndexingUrl(): void
    {
        $account = new Account();

        $this->assertNull($account->getIndexingUrl());

        $account->setIndexingUrl('indexing.ksearchnet.com');
        $this->assertSame('indexing.ksearchnet.com', $account->getIndexingUrl());

        $account->setIndexingUrl(null);
        $this->assertNull($account->getIndexingUrl());
    }

    #[Test]
    public function testGetSet_SearchUrl(): void
    {
        $account = new Account();

        $this->assertNull($account->getSearchUrl());

        $account->setSearchUrl('cs.ksearchnet.com');
        $this->assertSame('cs.ksearchnet.com', $account->getSearchUrl());

        $account->setSearchUrl(null);
        $this->assertNull($account->getSearchUrl());
    }

    #[Test]
    public function testGetSet_SmartCategoryMerchandisingUrl(): void
    {
        $account = new Account();

        $this->assertNull($account->getSmartCategoryMerchandisingUrl());

        $account->setSmartCategoryMerchandisingUrl('cn.ksearchnet.com');
        $this->assertSame('cn.ksearchnet.com', $account->getSmartCategoryMerchandisingUrl());

        $account->setSmartCategoryMerchandisingUrl(null);
        $this->assertNull($account->getSmartCategoryMerchandisingUrl());
    }

    #[Test]
    public function testGetSet_AnalyticsUrl(): void
    {
        $account = new Account();

        $this->assertNull($account->getAnalyticsUrl());

        $account->setAnalyticsUrl('stats.ksearchnet.com');
        $this->assertSame('stats.ksearchnet.com', $account->getAnalyticsUrl());

        $account->setAnalyticsUrl(null);
        $this->assertNull($account->getAnalyticsUrl());
    }

    #[Test]
    public function testGetSet_JsUrl(): void
    {
        $account = new Account();

        $this->assertNull($account->getJsUrl());

        $account->setJsUrl('js.klevu.com');
        $this->assertSame('js.klevu.com', $account->getJsUrl());

        $account->setJsUrl(null);
        $this->assertNull($account->getJsUrl());
    }

    #[Test]
    public function testGetSet_TiersUrl(): void
    {
        $account = new Account();

        $this->assertNull($account->getTiersUrl());

        $account->setTiersUrl('tiers.klevu.com');
        $this->assertSame('tiers.klevu.com', $account->getTiersUrl());

        $account->setTiersUrl(null);
        $this->assertNull($account->getTiersUrl());
    }

    #[Test]
    public function testGetSet_AccountFeatures(): void
    {
        $account = new Account();

        $accountFeaturesInit = $account->getAccountFeatures();
        $this->assertInstanceOf(AccountFeatures::class, $accountFeaturesInit);
        $this->assertFalse($accountFeaturesInit->smartCategoryMerchandising, 'Init: Category Merchandising');
        $this->assertFalse($accountFeaturesInit->smartRecommendations, 'Init: Personalisation');

        $accountFeatures = new AccountFeatures(
            smartCategoryMerchandising: true,
            smartRecommendations: false,
        );
        $account->setAccountFeatures($accountFeatures);
        $this->assertSame($accountFeatures, $account->getAccountFeatures());
    }

    #[Test]
    public function testToArray(): void
    {
        $account = new Account();

        $account->setJsApiKey('klevu-1234567890');
        $account->setRestAuthKey('ABCDE1234567890');
        $account->setPlatform(Platforms::CUSTOM->value);
        $account->setActive(true);
        $account->setCompanyName('Klevu');
        $account->setEmail('contact@klevu.com');
        $account->setIndexingUrl('indexing.ksearchnet.com');
        $account->setSearchUrl('cs.ksearchnet.com');
        $account->setSmartCategoryMerchandisingUrl('cn.ksearchnet.com');
        $account->setAnalyticsUrl('stats.ksearchnet.com');
        $account->setJsUrl('js.klevu.com');
        $account->setTiersUrl('tiers.klevu.com');

        $account->setAccountFeatures(
            new AccountFeatures(
                smartCategoryMerchandising: true,
                smartRecommendations: false,
            ),
        );

        $expectedResult = [
            'jsApiKey' => 'klevu-1234567890',
            'restAuthKey' => 'ABCDE1234567890',
            'platform' => Platforms::CUSTOM->value,
            'active' => true,
            'companyName' => 'Klevu',
            'email' => 'contact@klevu.com',
            'indexingUrl' => 'indexing.ksearchnet.com',
            'searchUrl' => 'cs.ksearchnet.com',
            'smartCategoryMerchandisingUrl' => 'cn.ksearchnet.com',
            'analyticsUrl' => 'stats.ksearchnet.com',
            'jsUrl' => 'js.klevu.com',
            'tiersUrl' => 'tiers.klevu.com',
        ];

        $this->assertEquals(
            expected: $expectedResult,
            actual: $account->toArray(),
        );
    }
}
