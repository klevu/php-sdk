<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model\Account;

use Klevu\PhpSDK\Model\Account;
use Klevu\PhpSDK\Service\Account\AccountFeaturesService;

/**
 * Immutable object storing flags for enabled features on a Klevu Account
 *
 * @see Account::getAccountFeatures()
 * @see AccountFeaturesService::execute()
 * @see AccountFeaturesOptions
 * @since 1.0.0
 */
class AccountFeatures
{
    /**
     * @param bool $smartCategoryMerchandising
     * @param bool $smartRecommendations
     * @param bool $preserveLayout
     */
    public function __construct(
        public readonly bool $smartCategoryMerchandising = false,
        public readonly bool $smartRecommendations = false,
        public readonly bool $preserveLayout = false,
    ) {
    }
}
