<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Traits;

use Klevu\PhpSDK\Service\Account\AccountFeaturesService;
use Klevu\PhpSDK\Service\Account\AccountLookupService;
use Klevu\PhpSDK\Service\Account\UpdateStoreFeedUrlService;

/**
 * Masks values of sensitive data, such as passwords, which should not be written to logs
 *
 * @internal
 */
trait MaskSensitiveDataTrait
{
    /**
     * @param string[]|string[][] $headers
     *
     * @return string[]|string[][]
     */
    private function maskHttpHeaders(array $headers): array
    {
        $headersToMask = array_unique([
            'X-KLEVU-RESTAPIKEY',
            'restApiKey',
            AccountLookupService::API_HEADER_KEY_RESTAPIKEY,
            AccountFeaturesService::API_HEADER_KEY_RESTAPIKEY,
            UpdateStoreFeedUrlService::API_HEADER_KEY_RESTAPIKEY,
        ]);

        $return = $headers;
        array_walk(
            $return,
            // phpcs:disable SlevomatCodingStandard.PHP.DisallowReference.DisallowedPassingByReference
            static function (&$headerValue, string $headerKey) use ($headersToMask): void {
                if (!in_array($headerKey, $headersToMask, true)) {
                    return;
                }

                $headerValue = match (true) {
                    is_array($headerValue) => array_fill(0, count($headerValue), '********'),
                    is_scalar($headerValue) => '********',
                };
            },
        );

        return $return;
    }
}
