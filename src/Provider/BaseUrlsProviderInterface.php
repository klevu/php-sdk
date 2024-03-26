<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Provider;

use Klevu\PhpSDK\Provider\Indexing\IndexingVersions;

/**
 * Contract for URLs provider implementation
 *
 * @since 1.0.0
 */
interface BaseUrlsProviderInterface
{
    /**
     * Returns base URL used for communicating with account and features services
     *
     * @example api.ksearchnet.com
     * @return string
     */
    public function getApiUrl(): string;

    /**
     * Returns base URL used for sending analytics events
     *
     * @example stats.ksearchnet.com
     * @return string
     */
    public function getAnalyticsUrl(): string;

    /**
     * Returns base URL used for querying search results for Smart Category Merchandising
     *
     * @example cn31.ksearchnet.com
     * @return string|null
     */
    public function getSmartCategoryMerchandisingUrl(): ?string;

    /**
     * Returns base URL used to access Klevu Merchant Center
     *
     * @example box.klevu.com
     * @return string
     */
    public function getMerchantCenterUrl(): string;

    /**
     * Returns base URL used to send product and other data for indexing
     *
     * @note Contains route prefix where applicable based upon version value passed
     *
     * @example indexing.ksearchnet.com/v2
     *
     * @param IndexingVersions $version
     *
     * @return string
     */
    public function getIndexingUrl(IndexingVersions $version = IndexingVersions::XML): string;

    /**
     * Returns base URL used to serve frontend JavaScript files
     *
     * @example js.klevu.com
     * @return string
     */
    public function getJsUrl(): string;

    /**
     * Returns base URL used for querying search results for SRLP and quick search
     *
     * @example eucs31v2.ksearchnet.com
     * @return string|null
     */
    public function getSearchUrl(): ?string;

    /**
     * Returns base URL used to retrieve account features information
     *
     * @example tiers.klevu.com
     * @return string
     */
    public function getTiersUrl(): string;
}
