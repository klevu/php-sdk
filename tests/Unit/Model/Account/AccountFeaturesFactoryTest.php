<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Model\Account;

use Klevu\PhpSDK\Model\Account\AccountFeatures;
use Klevu\PhpSDK\Model\Account\AccountFeaturesFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AccountFeaturesFactory::class)]
class AccountFeaturesFactoryTest extends TestCase
{
    /**
     * @return mixed[][]
     */
    public static function dataProvider_testCreate(): array
    {
        return [
            [
                // Input Data
                [
                    'smartCategoryMerchandising' => true,
                    'smartRecommendations' => true,
                ],
                // Expected Flags
                [
                    true, // smartCategoryMerchandising
                    true, // smartRecommendations
                ],
            ],
            [
                // Input Data
                [
                    'smartCategoryMerchandising' => false,
                    'smartRecommendations' => false,
                ],
                // Expected Flags
                [
                    false, // smartCategoryMerchandising
                    false, // smartRecommendations
                ],
            ],
            [
                // Input Data
                [],
                // Expected Flags
                [
                    false, // smartCategoryMerchandising
                    false, // smartRecommendations
                ],
            ],
        ];
    }

    /**
     * @param mixed[] $inputData
     * @param mixed[] $expectedFlags
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testCreate')]
    public function testCreate(array $inputData, array $expectedFlags): void
    {
        $accountFeaturesFactory = new AccountFeaturesFactory();
        $accountFeatures = $accountFeaturesFactory->create($inputData);

        $this->assertInstanceOf(AccountFeatures::class, $accountFeatures);
        $this->assertSame(
            $expectedFlags[0],
            $accountFeatures->smartCategoryMerchandising,
            'Smart Category Merchandising',
        );
        $this->assertSame(
            $expectedFlags[1],
            $accountFeatures->smartRecommendations,
            'Smart Recommendations',
        );
    }
}
