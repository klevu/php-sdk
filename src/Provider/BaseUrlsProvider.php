<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Provider;

use Klevu\PhpSDK\Api\Model\AccountInterface;
use Klevu\PhpSDK\Provider\Indexing\IndexingVersions;

/**
 * Provides base URLs used by service classes for connecting to different Klevu APIs
 *
 * @since 1.0.0
 */
class BaseUrlsProvider implements BaseUrlsProviderInterface
{
    /**
     * @var string
     */
    private string $apiUrl = 'api.ksearchnet.com';
    /**
     * @var string
     */
    private string $analyticsUrl = 'stats.ksearchnet.com';
    /**
     * @var string|null
     */
    private ?string $smartCategoryMerchandisingUrl = null;
    /**
     * @var string
     */
    private string $merchantCenterUrl = 'box.klevu.com';
    /**
     * @var string
     */
    private string $indexingUrl = 'indexing.ksearchnet.com';
    /**
     * @var string
     */
    private string $jsUrl = 'js.klevu.com';
    /**
     * @var string|null
     */
    private ?string $searchUrl = null;
    /**
     * @var string
     */
    private string $tiersUrl = 'tiers.klevu.com';

    /**
     * @param string|null $apiUrl
     * @param string|null $merchantCenterUrl
     * @param AccountInterface|null $account If provided, will call {@see BaseUrlsProvider::updateFromAccount()}
     */
    public function __construct(
        ?string $apiUrl = null,
        ?string $merchantCenterUrl = null,
        ?AccountInterface $account = null,
    ) {
        $this->apiUrl = $this->prepareUrl(
            url: $apiUrl,
            default: $this->apiUrl,
        );
        $this->merchantCenterUrl = $this->prepareUrl(
            url: $merchantCenterUrl,
            default: $this->merchantCenterUrl,
        );

        if ($account) {
            $this->updateFromAccount($account);
        }
    }

    /**
     * @example api.ksearchnet.com
     * @return string
     */
    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    /**
     * @example stats.ksearchnet.com
     * @return string
     */
    public function getAnalyticsUrl(): string
    {
        return $this->analyticsUrl;
    }

    /**
     * @example cn31.ksearchnet.com
     * @return string|null
     */
    public function getSmartCategoryMerchandisingUrl(): ?string
    {
        return $this->smartCategoryMerchandisingUrl;
    }

    /**
     * @example box.klevu.com
     * @return string
     */
    public function getMerchantCenterUrl(): string
    {
        return $this->merchantCenterUrl;
    }

    /**
     * @example indexing.ksearchnet.com/v2
     * @uses IndexingVersions::getUrlRoutePrefix()
     *
     * @param IndexingVersions $version
     *
     * @return string
     * @note Contains route prefix where applicable based upon version value passed
     */
    public function getIndexingUrl(IndexingVersions $version = IndexingVersions::XML): string
    {
        $indexingUrl = $this->indexingUrl;

        $urlRoutePrefix = $version->getUrlRoutePrefix();
        if (!str_ends_with($indexingUrl, $urlRoutePrefix)) {
            $indexingUrl .= $urlRoutePrefix;
        }

        return $indexingUrl;
    }

    /**
     * @example js.klevu.com
     * @return string
     */
    public function getJsUrl(): string
    {
        return $this->jsUrl;
    }

    /**
     * @example eucs31v2.ksearchnet.com
     * @return string|null
     */
    public function getSearchUrl(): ?string
    {
        return $this->searchUrl;
    }

    /**
     * @example tiers.klevu.com
     * @return string
     */
    public function getTiersUrl(): string
    {
        return $this->tiersUrl;
    }

    /**
     * Updates properties based on information found in Account model
     *
     * Rather than instantiating a new provider after retrieving an AccountInterface object
     *  in order to use the applicable URLS, the existing instance can be updated with a passed
     *  account model. This provides better support for container / DI patterns reusing the same
     *  provider throughout a request.
     *
     * @param AccountInterface $account
     *
     * @return void
     */
    public function updateFromAccount(AccountInterface $account): void
    {
        $this->analyticsUrl = $this->prepareUrl(
            url: $account->getAnalyticsUrl(),
            default: $this->analyticsUrl,
        );
        $this->smartCategoryMerchandisingUrl = $this->prepareUrl(
            url: $account->getSmartCategoryMerchandisingUrl(),
        ) ?: $this->smartCategoryMerchandisingUrl;
        $this->indexingUrl = $this->prepareUrl(
            url: $account->getIndexingUrl(),
            default: $this->indexingUrl,
        );
        $this->jsUrl = $this->prepareUrl(
            url: $account->getJsUrl(),
            default: $this->jsUrl,
        );
        $this->searchUrl = $this->prepareUrl(
            url: $account->getSearchUrl(),
        ) ?: $this->searchUrl;
        $this->tiersUrl = $this->prepareUrl(
            url: $account->getTiersUrl(),
            default: $this->tiersUrl,
        );
    }

    /**
     * @param string|null $url
     * @param string $default
     *
     * @return string
     */
    private function prepareUrl(
        ?string $url,
        string $default = '',
    ): string {
        $preparedUrl = trim(
            string: (string)$url,
            characters: " \n\r\t\v\x00/",
        );

        if (!$preparedUrl) {
            return $default;
        }

        $urlParts = parse_url($preparedUrl);
        if (!empty($urlParts['scheme'])) {
            $preparedUrl = str_replace(
                search: $urlParts['scheme'] . '://',
                replace: '',
                subject: $preparedUrl,
            );
        }

        return $preparedUrl ?: $default;
    }
}
