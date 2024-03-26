<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Api\Model;

use Klevu\PhpSDK\Model\Account\AccountFeatures;
use Klevu\PhpSDK\Model\Platforms;

/**
 * Data model contract for a Klevu store-level account as identified by a unique JavaScript API Key
 *
 * @link https://help.klevu.com/support/solutions/articles/5000876629-integrating-klevu-on-a-new-store
 * @link https://help.klevu.com/support/solutions/articles/5000876417-how-to-retrieve-store-specific-api-keys--js-template-version-and-end-points-for-product-sync--querying-indexed-data-and-submitting-analytics-events-
 * @api
 * @since 1.0.0
 */
interface AccountInterface
{
    /**
     * Returns the Klevu JavaScript API Key (public key) for this account
     *
     * @example klevu-1234567890
     *
     * @return string|null
     */
    public function getJsApiKey(): ?string;

    /**
     * Sets the Klevu JavaScript API Key (public key) for this account
     *
     * @example klevu-1234567890
     *
     * @param string|null $jsApiKey
     *
     * @return void
     */
    public function setJsApiKey(?string $jsApiKey): void;

    /**
     * Returns the REST AUTH Key (secret key) for this account
     *
     * @return string|null
     */
    public function getRestAuthKey(): ?string;

    /**
     * Sets the REST AUTH Key (secret key) for this account
     *
     * @param string|null $restAuthKey
     *
     * @return void
     */
    public function setRestAuthKey(?string $restAuthKey): void;

    /**
     * Returns the platform identifier for which this account was registered with Klevu
     *
     * @example magento
     * @see Platforms
     *
     * @return string|null
     */
    public function getPlatform(): ?string;

    /**
     * Sets the platform identifier for which this account was registered with Klevu
     *
     * @example magento
     * @see Platforms
     *
     * @param string|null $platform
     *
     * @return void
     */
    public function setPlatform(?string $platform): void;

    /**
     * Whether this is an active Klevu account
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Sets whether this is an active Klevu account
     *
     * @param bool $isActive
     *
     * @return void
     */
    public function setActive(bool $isActive): void;

    /**
     * Returns the company name used to register this account with Klevu
     *
     * @return string|null
     */
    public function getCompanyName(): ?string;

    /**
     * Sets the company name used to register this account with Klevu
     *
     * @param string|null $companyName
     *
     * @return void
     */
    public function setCompanyName(?string $companyName): void;

    /**
     * Returns the email address used to register this account with Klevu
     *
     * @return string|null
     */
    public function getEmail(): ?string;

    /**
     * Sets the email address used to register this account with Klevu
     *
     * @param string|null $email
     *
     * @return void
     */
    public function setEmail(?string $email): void;

    /**
     * Returns the base indexing (product synchronisation URL) used to send and receive data from Klevu via API
     *
     * Does not contain URL protocol
     *
     * @example indexing.ksearchnet.com
     *
     * @return string|null
     */
    public function getIndexingUrl(): ?string;

    /**
     * Sets the base indexing (product synchronisation URL) used to send and receive data from Klevu via API
     *
     * Should not contain URL protocol
     *
     * @example indexing.ksearchnet.com
     *
     * @param string|null $indexingUrl
     *
     * @return void
     */
    public function setIndexingUrl(?string $indexingUrl): void;

    /**
     * Returns the base APIv2 cloud search URL used to retrieve search results for this account
     *
     * Does not contain URL protocol
     *
     * @example eucs27v2.ksearchnet.com
     *
     * @return string|null
     */
    public function getSearchUrl(): ?string;

    /**
     * Sets the base APIv2 cloud search URL used to retrieve search results for this account
     *
     * Should not contain URL protocol
     *
     * @example eucs27v2.ksearchnet.com
     *
     * @param string|null $searchUrl
     *
     * @return void
     */
    public function setSearchUrl(?string $searchUrl): void;

    /**
     * Returns the base APIv2 cloud search URL used to retrieve smart category merchandising results for this account
     *
     * Does not contain URL protocol
     *
     * @example cn27v2.ksearchnet.com
     *
     * @return string|null
     */
    public function getSmartCategoryMerchandisingUrl(): ?string;

    /**
     * Sets the base APIv2 cloud search URL used to retrieve smart category merchandising results for this account
     * Should not contain URL protocol
     *
     * @example cn27v2.ksearchnet.com
     *
     * @param string|null $smartCategoryMerchandisingUrl
     *
     * @return void
     */
    public function setSmartCategoryMerchandisingUrl(?string $smartCategoryMerchandisingUrl): void;

    /**
     * Returns the base URL to which analytics events should be sent in order to be tracked and reported by Klevu
     * Does not contain URL protocol
     *
     * @example stats.ksearchnet.com
     * @return string|null
     */
    public function getAnalyticsUrl(): ?string;

    /**
     * Sets the base URL to which analytics events should be sent in order to be tracked and reported by Klevu
     * Should not contain URL protocol
     *
     * @example stats.ksearchnet.com
     *
     * @param string|null $analyticsUrl
     *
     * @return void
     */
    public function setAnalyticsUrl(?string $analyticsUrl): void;

    /**
     * Returns the base URL used to embed Klevu-hosted JavaScript assets to implement frontend integrations
     * Does not contain URL protocol
     *
     * @example js.klevu.com
     * @return string|null
     */
    public function getJsUrl(): ?string;

    /**
     * Sets the base URL used to embed Klevu-hosted JavaScript assets to implement frontend integrations
     * Should not contain URL protocol
     *
     * @example js.klevu.com
     *
     * @param string|null $jsUrl
     *
     * @return void
     */
    public function setJsUrl(?string $jsUrl): void;

    /**
     * Returns the base URL from which features information can be retrieved for this account
     * Should not contain URL protocol
     *
     * @example tiers.klevu.com
     * @return string|null
     */
    public function getTiersUrl(): ?string;

    /**
     * Sets the base URL from which features information can be retrieved for this account
     * Does not contain URL protocol
     *
     * @example tiers.klevu.com
     *
     * @param string|null $tiersUrl
     *
     * @return void
     */
    public function setTiersUrl(?string $tiersUrl): void;

    /**
     * Returns information about which features are available for this account, based on subscription
     *  and other configuration for the Klevu account
     *
     * @return AccountFeatures
     */
    public function getAccountFeatures(): AccountFeatures;

    /**
     * Sets the AccountFeatures module containing information about features available for this account
     *
     * @param AccountFeatures $accountFeatures
     *
     * @return void
     */
    public function setAccountFeatures(AccountFeatures $accountFeatures): void;
}
