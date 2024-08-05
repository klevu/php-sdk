<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model;

/**
 * Factory class to create new instance of Account object
 *
 * @see Account
 * @since 1.0.0
 */
class AccountFactory
{
    /**
     * Creates a new instance of Account, populated with passed data
     *
     * @param array<string, mixed> $data Array of data, with keys corresponding to the Account FIELD_* constants
     *      For example, ['jsApiKey' => 'klevu-1234567890']
     *
     * @return Account
     * @throws \TypeError Where value provided for data key does not match required type.
     *      For example, ['jsApiKey' => [false]]
     */
    public function create(array $data): Account
    {
        $account = new Account();

        // phpcs:disable Generic.Files.LineLength.TooLong
        if ($data[Account::FIELD_JS_API_KEY] ?? null) {
            $account->setJsApiKey($data[Account::FIELD_JS_API_KEY]); // @phpstan-ignore-line We are bubbling the TypeError
        }
        if ($data[Account::FIELD_REST_AUTH_KEY] ?? null) {
            $account->setRestAuthKey($data[Account::FIELD_REST_AUTH_KEY]); // @phpstan-ignore-line We are bubbling the TypeError
        }
        if ($data[Account::FIELD_PLATFORM] ?? null) {
            $account->setPlatform($data[Account::FIELD_PLATFORM]); // @phpstan-ignore-line We are bubbling the TypeError
        }
        if (array_key_exists(Account::FIELD_ACTIVE, $data) && null !== $data[Account::FIELD_ACTIVE]) {
            $account->setActive((bool)$data[Account::FIELD_ACTIVE]);
        }
        if ($data[Account::FIELD_COMPANY_NAME] ?? null) {
            $account->setCompanyName($data[Account::FIELD_COMPANY_NAME]); // @phpstan-ignore-line We are bubbling the TypeError
        }
        if ($data[Account::FIELD_EMAIL] ?? null) {
            $account->setEmail($data[Account::FIELD_EMAIL]); // @phpstan-ignore-line We are bubbling the TypeError
        }
        if ($data[Account::FIELD_INDEXING_URL] ?? null) {
            $account->setIndexingUrl($data[Account::FIELD_INDEXING_URL]); // @phpstan-ignore-line We are bubbling the TypeError
        }
        if ($data[Account::FIELD_SEARCH_URL] ?? null) {
            $account->setSearchUrl($data[Account::FIELD_SEARCH_URL]); // @phpstan-ignore-line We are bubbling the TypeError
        }
        if ($data[Account::FIELD_SMART_CATEGORY_MERCHANDISING_URL] ?? null) {
            $account->setSmartCategoryMerchandisingUrl($data[Account::FIELD_SMART_CATEGORY_MERCHANDISING_URL]); // @phpstan-ignore-line We are bubbling the TypeError
        }
        if ($data[Account::FIELD_ANALYTICS_URL] ?? null) {
            $account->setAnalyticsUrl($data[Account::FIELD_ANALYTICS_URL]); // @phpstan-ignore-line We are bubbling the TypeError
        }
        if ($data[Account::FIELD_JS_URL] ?? null) {
            $account->setJsUrl($data[Account::FIELD_JS_URL]); // @phpstan-ignore-line We are bubbling the TypeError
        }
        if ($data[Account::FIELD_TIERS_URL] ?? null) {
            $account->setTiersUrl($data[Account::FIELD_TIERS_URL]); // @phpstan-ignore-line We are bubbling the TypeError
        }
        if ($data[Account::FIELD_INDEXING_VERSION] ?? null) {
            $account->setIndexingVersion($data[Account::FIELD_INDEXING_VERSION]); // @phpstan-ignore-line We are bubbling the TypeError
        }
        if ($data[Account::FIELD_DEFAULT_CURRENCY] ?? null) {
            $account->setDefaultCurrency($data[Account::FIELD_DEFAULT_CURRENCY]); // @phpstan-ignore-line We are bubbling the TypeError
        }
        // phpcs:enable Generic.Files.LineLength.TooLong

        return $account;
    }
}
