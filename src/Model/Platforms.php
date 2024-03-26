<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model;

/**
 * Enumeration of platform identifiers used by Klevu services
 *
 * @since 1.0.0
 */
enum Platforms: string
{
    case MAGENTO = 'magento';
    case SHOPIFY = 'shopify';
    case SHOPIFY_PLUS = 'shopify-plus';
    case BIGCOMMERCE = 'bigcommerce';
    case CUSTOM = 'custom';

    /**
     * Determines whether selected case refers to the Magento platform
     *
     * @return bool
     */
    public function isMagento(): bool
    {
        return match ($this) {
            Platforms::MAGENTO => true,
            Platforms::SHOPIFY, Platforms::SHOPIFY_PLUS, Platforms::BIGCOMMERCE, Platforms::CUSTOM => false,
        };
    }

    /**
     * Determines whether selected case refers to the Shopify platform
     *
     * @return bool
     */
    public function isShopify(): bool
    {
        return match ($this) {
            Platforms::SHOPIFY, Platforms::SHOPIFY_PLUS => true,
            Platforms::MAGENTO, Platforms::BIGCOMMERCE, Platforms::CUSTOM => false,
        };
    }

    /**
     * Determines whether selected case refers to the Bigcommerce platform
     *
     * @return bool
     */
    public function isBigCommerce(): bool
    {
        return match ($this) {
            Platforms::BIGCOMMERCE => true,
            Platforms::MAGENTO, Platforms::SHOPIFY, Platforms::SHOPIFY_PLUS, Platforms::CUSTOM => false,
        };
    }

    /**
     * Determines whether selected case refers to an unlisted platform, including custom implementations
     *
     * @return bool
     */
    public function isCustom(): bool
    {
        return match ($this) {
            Platforms::CUSTOM => true,
            Platforms::MAGENTO, Platforms::SHOPIFY, Platforms::SHOPIFY_PLUS, Platforms::BIGCOMMERCE => false,
        };
    }
}
