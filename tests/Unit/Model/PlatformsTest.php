<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Model;

use Klevu\PhpSDK\Model\Platforms;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Platforms::class)]
class PlatformsTest extends TestCase
{
    #[Test]
    public function testIsMagento(): void
    {
        $this->assertTrue(Platforms::MAGENTO->isMagento());
        $this->assertFalse(Platforms::SHOPIFY->isMagento());
        $this->assertFalse(Platforms::SHOPIFY_PLUS->isMagento());
        $this->assertFalse(Platforms::BIGCOMMERCE->isMagento());
        $this->assertFalse(Platforms::CUSTOM->isMagento());
    }

    #[Test]
    public function testIsShopify(): void
    {
        $this->assertFalse(Platforms::MAGENTO->isShopify());
        $this->assertTrue(Platforms::SHOPIFY->isShopify());
        $this->assertTrue(Platforms::SHOPIFY_PLUS->isShopify());
        $this->assertFalse(Platforms::BIGCOMMERCE->isShopify());
        $this->assertFalse(Platforms::CUSTOM->isShopify());
    }

    #[Test]
    public function testIsBigCommerce(): void
    {
        $this->assertFalse(Platforms::MAGENTO->isBigCommerce());
        $this->assertFalse(Platforms::SHOPIFY->isBigCommerce());
        $this->assertFalse(Platforms::SHOPIFY_PLUS->isBigCommerce());
        $this->assertTrue(Platforms::BIGCOMMERCE->isBigCommerce());
        $this->assertFalse(Platforms::CUSTOM->isBigCommerce());
    }

    #[Test]
    public function testIsCustom(): void
    {
        $this->assertFalse(Platforms::MAGENTO->isCustom());
        $this->assertFalse(Platforms::SHOPIFY->isCustom());
        $this->assertFalse(Platforms::SHOPIFY_PLUS->isCustom());
        $this->assertFalse(Platforms::BIGCOMMERCE->isCustom());
        $this->assertTrue(Platforms::CUSTOM->isCustom());
    }
}
