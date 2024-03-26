<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model\Account;

/**
 * Factory class to create a new instance of AccountFeatures immutable
 *
 * @see AccountFeatures
 * @since 1.0.0
 */
class AccountFeaturesFactory
{
    /**
     * @param array<string, bool> $data
     *
     * @return AccountFeatures
     * @throws \TypeError Where value provided for data key does not match required type.
     *       For example, ['smartCategoryMerchandising' => ['foo']]
     */
    public function create(array $data): AccountFeatures
    {
        return new AccountFeatures(
            smartCategoryMerchandising: $data[AccountFeaturesOptions::smartCategoryMerchandising->name] ?? false,
            smartRecommendations: $data[AccountFeaturesOptions::smartRecommendations->name] ?? false,
            preserveLayout: $data[AccountFeaturesOptions::preserveLayout->name] ?? false,
        );
    }
}
