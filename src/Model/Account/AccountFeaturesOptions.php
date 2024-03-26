<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model\Account;

/**
 * Enumeration of flag names used when checking features on a Klevu account
 *
 * @since 1.0.0
 */
enum AccountFeaturesOptions: string
{
    /**
     * Whether Smart Category Merchandising has been enabled
     *
     * @link https://www.klevu.com/solutions/category-merchandising/
     */
    case smartCategoryMerchandising = 's.enablecategorynavigation';
    /**
     * Whether Smart Recommendations has been enabled
     *
     * @link https://www.klevu.com/solutions/product-recommendations/
     */
    case smartRecommendations = 'allow.personalizedrecommendations';
    /**
     * Whether Preserve Layout has been enabled
     *
     * @note This is an informational flag used to indicate intent; no
     *  functionality is disabled when this flag is false
     */
    case preserveLayout = 's.preservedlayout';
}
