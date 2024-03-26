<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Model\Analytics\Collect;

use Klevu\PhpSDK\Model\Analytics\Collect\UserProfile;
use Klevu\PhpSDK\Model\Analytics\Collect\UserProfileFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserProfileFactory::class)]
class UserProfileFactoryTest extends TestCase
{
    /**
     * @return mixed[][]
     */
    public static function dataProvider_testCreate_Invalid(): array
    {
        return [
            [
                [
                    'ipAddress' => 123,
                    'email' => 'foo',
                ],
                \TypeError::class,
            ],
            [
                [
                    'ipAddress' => '127.0.0.1',
                    'email' => 3.14,
                ],
                \TypeError::class,
            ],
        ];
    }

    /**
     * @param mixed[] $data
     * @param class-string<\Throwable> $expectedExceptionFqcn
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testCreate_Invalid')]
    public function testCreate_Invalid(
        array $data,
        string $expectedExceptionFqcn,
    ): void {
        $userProfileFactory = new UserProfileFactory();

        $this->expectException($expectedExceptionFqcn);
        $userProfileFactory->create($data);
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testCreate_Valid(): array
    {
        return [
            [
                [],
                new UserProfile(
                    ipAddress: null,
                    email: null,
                ),
            ],
            [
                [
                    'ipAddress' => null,
                    'email' => null,
                ],
                new UserProfile(
                    ipAddress: null,
                    email: null,
                ),
            ],
            [
                [
                    'ipAddress' => '',
                    'email' => '',
                ],
                new UserProfile(
                    ipAddress: '',
                    email: '',
                ),
            ],
            [
                [
                    'ipAddress' => ' 127.0.0.1 ',
                    'email' => ' contact @ klevu.com ',
                ],
                new UserProfile(
                    ipAddress: ' 127.0.0.1 ', // No trim
                    email: ' contact @ klevu.com ',
                ),
            ],
        ];
    }

    /**
     * @param mixed[] $data
     * @param UserProfile $expectedResult
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_testCreate_Valid')]
    public function testCreate_Valid(
        array $data,
        UserProfile $expectedResult,
    ): void {
        $userProfileFactory = new UserProfileFactory();

        $userProfile = $userProfileFactory->create($data);
        $this->assertEquals(
            expected: $expectedResult,
            actual: $userProfile,
        );
    }
}
