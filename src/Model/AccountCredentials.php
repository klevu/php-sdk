<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model;

/**
 * Immutable object used to provide access credentials when invoking API service classes
 *
 * @link https://help.klevu.com/support/solutions/articles/5000876417-how-to-retrieve-store-specific-api-keys--js-template-version-and-end-points-for-product-sync--querying-indexed-data-and-submitting-analytics-events-
 * @api
 * @since 1.0.0
 */
class AccountCredentials
{
    /**
     * @param string $jsApiKey The JS API Key, in the format klevu-xxxxxxxxxx
     * @param string $restAuthKey The REST AUTH Key; used for server-side communication
     */
    public function __construct(
        public readonly string $jsApiKey,
        public readonly string $restAuthKey,
    ) {
    }
}
