<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Provider;

use Klevu\PhpSDK\Model\Account;
use Klevu\PhpSDK\Provider\BaseUrlsProvider;
use Klevu\PhpSDK\Provider\BaseUrlsProviderInterface;
use Klevu\PhpSDK\Provider\Indexing\IndexingVersions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(BaseUrlsProvider::class)]
class BaseUrlsProviderTest extends TestCase
{
    #[Test]
    public function testImplementsInterface(): void
    {
        $baseUrlsProvider = new BaseUrlsProvider();

        $this->assertInstanceOf(
            expected: BaseUrlsProviderInterface::class,
            actual: $baseUrlsProvider,
        );
    }

    #[Test]
    public function testGetApiUrl(): void
    {
        $baseUrlsProvider = new BaseUrlsProvider();

        $this->assertSame(
            expected: 'api.ksearchnet.com',
            actual: $baseUrlsProvider->getApiUrl(),
        );
    }

    #[Test]
    public function testGetAnalyticsUrl(): void
    {
        $baseUrlsProvider = new BaseUrlsProvider();

        $this->assertSame(
            expected: 'stats.ksearchnet.com',
            actual: $baseUrlsProvider->getAnalyticsUrl(),
        );
    }

    #[Test]
    public function testGetSmartCategoryMerchandisingUrl(): void
    {
        $baseUrlsProvider = new BaseUrlsProvider();

        $this->assertSame(
            expected: null,
            actual: $baseUrlsProvider->getSmartCategoryMerchandisingUrl(),
        );
    }

    #[Test]
    public function testGetMerchantCenterUrl(): void
    {
        $baseUrlsProvider = new BaseUrlsProvider();

        $this->assertSame(
            expected: 'box.klevu.com',
            actual: $baseUrlsProvider->getMerchantCenterUrl(),
        );
    }

    #[Test]
    public function testGetIndexingUrl(): void
    {
        $baseUrlsProvider = new BaseUrlsProvider();

        $this->assertSame(
            expected: 'indexing.ksearchnet.com',
            actual: $baseUrlsProvider->getIndexingUrl(),
        );
    }

    #[Test]
    public function testGetV2IndexingUrl(): void
    {
        $baseUrlsProvider = new BaseUrlsProvider();

        $this->assertSame(
            expected: 'indexing.ksearchnet.com/v2',
            actual: $baseUrlsProvider->getIndexingUrl(IndexingVersions::JSON),
        );
    }

    #[Test]
    public function tetGetJsUrl(): void
    {
        $baseUrlsProvider = new BaseUrlsProvider();

        $this->assertSame(
            expected: 'js.klevu.com',
            actual: $baseUrlsProvider->getJsUrl(),
        );
    }

    #[Test]
    public function testGetCloudSearchUrl(): void
    {
        $baseUrlsProvider = new BaseUrlsProvider();

        $this->assertSame(
            expected: null,
            actual: $baseUrlsProvider->getSearchUrl(),
        );
    }

    #[Test]
    public function testGetTiersUrl(): void
    {
        $baseUrlsProvider = new BaseUrlsProvider();

        $this->assertSame(
            expected: 'tiers.klevu.com',
            actual: $baseUrlsProvider->getTiersUrl(),
        );
    }

    #[Test]
    #[TestWith(["custom-api.klevu.com", "custom-api.klevu.com"])]
    #[TestWith(["http://custom-api.klevu.com", "custom-api.klevu.com"])]
    #[TestWith(["custom-api.klevu.com/", "custom-api.klevu.com"])]
    #[TestWith(["localhost:8080", "localhost:8080"])]
    #[TestWith(["api.klevu.com/custom/", "api.klevu.com/custom"])]
    #[TestWith([" ", "api.ksearchnet.com"])]
    public function testConstruct_ApiUrl(
        ?string $apiUrl,
        string $expectedResult,
    ): void {
        $baseUrlsProvider = new BaseUrlsProvider(
            apiUrl: $apiUrl,
        );

        $this->assertSame(
            expected: $expectedResult,
            actual: $baseUrlsProvider->getApiUrl(),
        );
    }

    #[Test]
    #[TestWith(["custom-box.klevu.com", "custom-box.klevu.com"])]
    #[TestWith(["http://custom-box.klevu.com", "custom-box.klevu.com"])]
    #[TestWith(["custom-box.klevu.com/", "custom-box.klevu.com"])]
    #[TestWith(["localhost:8080", "localhost:8080"])]
    #[TestWith(["box.klevu.com/custom/", "box.klevu.com/custom"])]
    #[TestWith([" ", "box.klevu.com"])]
    public function testConstruct_MerchantCenterUrl(
        ?string $merchantCenterUrl,
        string $expectedResult,
    ): void {
        $baseUrlsProvider = new BaseUrlsProvider(
            merchantCenterUrl: $merchantCenterUrl,
        );

        $this->assertSame(
            expected: $expectedResult,
            actual: $baseUrlsProvider->getMerchantCenterUrl(),
        );
    }

    #[Test]
    public function testConstruct_Account_WithUrls(): void
    {
        $account = new Account();
        $account->setAnalyticsUrl('https://custom-analytics.klevu.com/');
        $account->setSmartCategoryMerchandisingUrl('cn.klevu.com');
        $account->setJsUrl('localhost:8080');
        $account->setSearchUrl('');
        $account->setIndexingUrl('new-indexing.klevu.com');

        $baseUrlsProvider = new BaseUrlsProvider(
            account: $account,
        );

        $this->assertSame(
            expected: 'custom-analytics.klevu.com',
            actual: $baseUrlsProvider->getAnalyticsUrl(),
        );
        $this->assertSame(
            expected: 'cn.klevu.com',
            actual: $baseUrlsProvider->getSmartCategoryMerchandisingUrl(),
        );
        $this->assertSame(
            expected: 'new-indexing.klevu.com',
            actual: $baseUrlsProvider->getIndexingUrl(),
        );
        $this->assertSame(
            expected: 'new-indexing.klevu.com/v2',
            actual: $baseUrlsProvider->getIndexingUrl(IndexingVersions::JSON),
        );
        $this->assertSame(
            expected: 'localhost:8080',
            actual: $baseUrlsProvider->getJsUrl(),
        );
        $this->assertSame(
            expected: null,
            actual: $baseUrlsProvider->getSearchUrl(),
        );
        $this->assertSame(
            expected: 'tiers.klevu.com',
            actual: $baseUrlsProvider->getTiersUrl(),
        );
    }

    #[Test]
    public function testConstruct_Account_WithoutUrls(): void
    {
        $account = new Account();

        $baseUrlsProvider = new BaseUrlsProvider(
            account: $account,
        );

        $this->assertSame(
            expected: 'stats.ksearchnet.com',
            actual: $baseUrlsProvider->getAnalyticsUrl(),
        );
        $this->assertSame(
            expected: null,
            actual: $baseUrlsProvider->getSmartCategoryMerchandisingUrl(),
        );
        $this->assertSame(
            expected: 'indexing.ksearchnet.com',
            actual: $baseUrlsProvider->getIndexingUrl(),
        );
        $this->assertSame(
            expected: 'indexing.ksearchnet.com/v2',
            actual: $baseUrlsProvider->getIndexingUrl(IndexingVersions::JSON),
        );
        $this->assertSame(
            expected: 'js.klevu.com',
            actual: $baseUrlsProvider->getJsUrl(),
        );
        $this->assertSame(
            expected: null,
            actual: $baseUrlsProvider->getSearchUrl(),
        );
        $this->assertSame(
            expected: 'tiers.klevu.com',
            actual: $baseUrlsProvider->getTiersUrl(),
        );
    }

    #[Test]
    public function testUpdateFromAccount(): void
    {
        $baseUrlsProvider = new BaseUrlsProvider();

        $this->assertSame(
            expected: 'stats.ksearchnet.com',
            actual: $baseUrlsProvider->getAnalyticsUrl(),
        );
        $this->assertSame(
            expected: null,
            actual: $baseUrlsProvider->getSmartCategoryMerchandisingUrl(),
        );
        $this->assertSame(
            expected: 'indexing.ksearchnet.com',
            actual: $baseUrlsProvider->getIndexingUrl(),
        );
        $this->assertSame(
            expected: 'indexing.ksearchnet.com/v2',
            actual: $baseUrlsProvider->getIndexingUrl(IndexingVersions::JSON),
        );
        $this->assertSame(
            expected: 'js.klevu.com',
            actual: $baseUrlsProvider->getJsUrl(),
        );
        $this->assertSame(
            expected: null,
            actual: $baseUrlsProvider->getSearchUrl(),
        );
        $this->assertSame(
            expected: 'tiers.klevu.com',
            actual: $baseUrlsProvider->getTiersUrl(),
        );

        $account = new Account();
        $account->setAnalyticsUrl('https://custom-analytics.klevu.com/');
        $account->setSmartCategoryMerchandisingUrl('cn.klevu.com');
        $account->setJsUrl('localhost:8080');
        $account->setSearchUrl('');
        $account->setIndexingUrl('new-indexing.klevu.com');

        $baseUrlsProvider->updateFromAccount($account);

        $this->assertSame(
            expected: 'custom-analytics.klevu.com',
            actual: $baseUrlsProvider->getAnalyticsUrl(),
        );
        $this->assertSame(
            expected: 'cn.klevu.com',
            actual: $baseUrlsProvider->getSmartCategoryMerchandisingUrl(),
        );
        $this->assertSame(
            expected: 'new-indexing.klevu.com',
            actual: $baseUrlsProvider->getIndexingUrl(),
        );
        $this->assertSame(
            expected: 'new-indexing.klevu.com/v2',
            actual: $baseUrlsProvider->getIndexingUrl(IndexingVersions::JSON),
        );
        $this->assertSame(
            expected: 'localhost:8080',
            actual: $baseUrlsProvider->getJsUrl(),
        );
        $this->assertSame(
            expected: null,
            actual: $baseUrlsProvider->getSearchUrl(),
        );
        $this->assertSame(
            expected: 'tiers.klevu.com',
            actual: $baseUrlsProvider->getTiersUrl(),
        );
    }
}
