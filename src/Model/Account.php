<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model;

use Klevu\PhpSDK\Api\Model\AccountInterface;
use Klevu\PhpSDK\Model\Account\AccountFeatures;
use Klevu\PhpSDK\Model\Account\AccountFeaturesFactory;
use Klevu\PhpSDK\Service\Account\AccountLookupService;

/**
 * Data model representation of a Klevu store-level account as identified by a unique JavaScript API Key
 *
 * @note Does not perform validation or persistence of data
 * @link https://help.klevu.com/support/solutions/articles/5000876629-integrating-klevu-on-a-new-store
 * @link https://help.klevu.com/support/solutions/articles/5000876417-how-to-retrieve-store-specific-api-keys--js-template-version-and-end-points-for-product-sync--querying-indexed-data-and-submitting-analytics-events-
 * @see AccountLookupService
 * @since 1.0.0
 */
class Account implements AccountInterface
{
    /**
     * Key used to reference jsApiKey property when converting to/from array
     *
     * @see Account::toArray()
     * @see AccountFactory::create()
     * @var string
     */
    final public const FIELD_JS_API_KEY = 'jsApiKey';
    /**
     * Key used to reference restAuthKey property when converting to/from array
     *
     * @see Account::toArray()
     * @see AccountFactory::create()
     * @var string
     */
    final public const FIELD_REST_AUTH_KEY = 'restAuthKey';
    /**
     * Key used to reference platform property when converting to/from array
     *
     * @see Account::toArray()
     * @see AccountFactory::create()
     * @var string
     */
    final public const FIELD_PLATFORM = 'platform';
    /**
     * Key used to reference active property when converting to/from array
     *
     * @see Account::toArray()
     * @see AccountFactory::create()
     * @var string
     */
    final public const FIELD_ACTIVE = 'active';
    /**
     * Key used to reference companyName property when converting to/from array
     *
     * @see Account::toArray()
     * @see AccountFactory::create()
     * @var string
     */
    final public const FIELD_COMPANY_NAME = 'companyName';
    /**
     * Key used to reference email property when converting to/from array
     *
     * @see Account::toArray()
     * @see AccountFactory::create()
     * @var string
     */
    final public const FIELD_EMAIL = 'email';
    /**
     * Key used to reference indexingUrl property when converting to/from array
     *
     * @see Account::toArray()
     * @see AccountFactory::create()
     * @var string
     */
    final public const FIELD_INDEXING_URL = 'indexingUrl';
    /**
     * Key used to reference searchUrl property when converting to/from array
     *
     * @see Account::toArray()
     * @see AccountFactory::create()
     * @var string
     */
    final public const FIELD_SEARCH_URL = 'searchUrl';
    /**
     * Key used to reference smartCategoryMerchandisingUrl property when converting to/from array
     *
     * @see Account::toArray()
     * @see AccountFactory::create()
     * @var string
     */
    final public const FIELD_SMART_CATEGORY_MERCHANDISING_URL = 'smartCategoryMerchandisingUrl';
    /**
     * Key used to reference analyticsUrl property when converting to/from array
     *
     * @see Account::toArray()
     * @see AccountFactory::create()
     * @var string
     */
    final public const FIELD_ANALYTICS_URL = 'analyticsUrl';
    /**
     * Key used to reference jsUrl property when converting to/from array
     *
     * @see Account::toArray()
     * @see AccountFactory::create()
     * @var string
     */
    final public const FIELD_JS_URL = 'jsUrl';
    /**
     * Key used to reference tiersUrl property when converting to/from array
     *
     * @see Account::toArray()
     * @see AccountFactory::create()
     * @var string
     */
    final public const FIELD_TIERS_URL = 'tiersUrl';

    /**
     * @var string|null
     */
    private ?string $jsApiKey = null;
    /**
     * @var string|null
     */
    private ?string $restAuthKey = null;
    /**
     * @var string|null
     */
    private ?string $platform = null;
    /**
     * @var bool
     */
    private bool $active = false;
    /**
     * @var string|null
     */
    private ?string $companyName = null;
    /**
     * @var string|null
     */
    private ?string $email = null;
    /**
     * @var string|null
     */
    private ?string $indexingUrl = null;
    /**
     * @var string|null
     */
    private ?string $searchUrl = null;
    /**
     * @var string|null
     */
    private ?string $smartCategoryMerchandisingUrl = null;
    /**
     * @var string|null
     */
    private ?string $analyticsUrl = null;
    /**
     * @var string|null
     */
    private ?string $jsUrl = null;
    /**
     * @var string|null
     */
    private ?string $tiersUrl = null;
    /**
     * @var AccountFeatures|null
     */
    private ?AccountFeatures $accountFeatures = null;

    /**
     * @return string|null
     */
    public function getJsApiKey(): ?string
    {
        return $this->jsApiKey;
    }

    /**
     * @param string|null $jsApiKey
     *
     * @return void
     */
    public function setJsApiKey(?string $jsApiKey): void
    {
        $this->jsApiKey = $jsApiKey;
    }

    /**
     * @return string|null
     */
    public function getRestAuthKey(): ?string
    {
        return $this->restAuthKey;
    }

    /**
     * @param string|null $restAuthKey
     *
     * @return void
     */
    public function setRestAuthKey(?string $restAuthKey): void
    {
        $this->restAuthKey = $restAuthKey;
    }

    /**
     * @return string|null
     */
    public function getPlatform(): ?string
    {
        return $this->platform;
    }

    /**
     * @param string|null $platform
     *
     * @return void
     */
    public function setPlatform(?string $platform): void
    {
        $this->platform = $platform;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     *
     * @return void
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @return string|null
     */
    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    /**
     * @param string|null $companyName
     *
     * @return void
     */
    public function setCompanyName(?string $companyName): void
    {
        $this->companyName = $companyName;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     *
     * @return void
     */
    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string|null
     */
    public function getIndexingUrl(): ?string
    {
        return $this->indexingUrl;
    }

    /**
     * @param string|null $indexingUrl
     *
     * @return void
     */
    public function setIndexingUrl(?string $indexingUrl): void
    {
        $this->indexingUrl = $indexingUrl;
    }

    /**
     * @return string|null
     */
    public function getSearchUrl(): ?string
    {
        return $this->searchUrl;
    }

    /**
     * @param string|null $searchUrl
     *
     * @return void
     */
    public function setSearchUrl(?string $searchUrl): void
    {
        $this->searchUrl = $searchUrl;
    }

    /**
     * @return string|null
     */
    public function getSmartCategoryMerchandisingUrl(): ?string
    {
        return $this->smartCategoryMerchandisingUrl;
    }

    /**
     * @param string|null $smartCategoryMerchandisingUrl
     *
     * @return void
     */
    public function setSmartCategoryMerchandisingUrl(?string $smartCategoryMerchandisingUrl): void
    {
        $this->smartCategoryMerchandisingUrl = $smartCategoryMerchandisingUrl;
    }

    /**
     * @return string|null
     */
    public function getAnalyticsUrl(): ?string
    {
        return $this->analyticsUrl;
    }

    /**
     * @param string|null $analyticsUrl
     *
     * @return void
     */
    public function setAnalyticsUrl(?string $analyticsUrl): void
    {
        $this->analyticsUrl = $analyticsUrl;
    }

    /**
     * @return string|null
     */
    public function getJsUrl(): ?string
    {
        return $this->jsUrl;
    }

    /**
     * @param string|null $jsUrl
     *
     * @return void
     */
    public function setJsUrl(?string $jsUrl): void
    {
        $this->jsUrl = $jsUrl;
    }

    /**
     * @return string|null
     */
    public function getTiersUrl(): ?string
    {
        return $this->tiersUrl;
    }

    /**
     * @param string|null $tiersUrl
     *
     * @return void
     */
    public function setTiersUrl(?string $tiersUrl): void
    {
        $this->tiersUrl = $tiersUrl;
    }

    /**
     * @return AccountFeatures
     */
    public function getAccountFeatures(): AccountFeatures
    {
        return $this->accountFeatures ??= (new AccountFeaturesFactory())->create([]);
    }

    /**
     * @param AccountFeatures $accountFeatures
     *
     * @return void
     */
    public function setAccountFeatures(AccountFeatures $accountFeatures): void
    {
        $this->accountFeatures = $accountFeatures;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            self::FIELD_JS_API_KEY => $this->getJsApiKey(),
            self::FIELD_REST_AUTH_KEY => $this->getRestAuthKey(),
            self::FIELD_PLATFORM => $this->getPlatform(),
            self::FIELD_ACTIVE => $this->isActive(),
            self::FIELD_COMPANY_NAME => $this->getCompanyName(),
            self::FIELD_EMAIL => $this->getEmail(),
            self::FIELD_INDEXING_URL => $this->getIndexingUrl(),
            self::FIELD_SEARCH_URL => $this->getSearchUrl(),
            self::FIELD_SMART_CATEGORY_MERCHANDISING_URL => $this->getSmartCategoryMerchandisingUrl(),
            self::FIELD_ANALYTICS_URL => $this->getAnalyticsUrl(),
            self::FIELD_JS_URL => $this->getJsUrl(),
            self::FIELD_TIERS_URL => $this->getTiersUrl(),
        ];
    }
}
