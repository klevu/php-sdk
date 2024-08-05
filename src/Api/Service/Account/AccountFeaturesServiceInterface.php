<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Api\Service\Account;

use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ApiExceptionInterface;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\Account\AccountFeatures;
use Klevu\PhpSDK\Model\AccountCredentials;
use Klevu\PhpSDK\Service\ApiServiceInterface;

/**
 * Contract for services handling interactions with the Klevu Account Features endpoint
 *
 * @see AccountFeatures
 * @api
 * @since 1.0.0
 */
interface AccountFeaturesServiceInterface extends ApiServiceInterface
{
    /**
     * Send a request to Klevu to retrieve enabled features for the account specified in $accountCredentials
     *
     * @api
     *
     * @param string[]|null $features List of feature flag strings to query values for
     *      If null, checks all features in \Klevu\PhpSDK\Model\Account\AccountFeaturesOptions
     * @param AccountCredentials $accountCredentials
     *
     * @return AccountFeatures
     * @throws ApiExceptionInterface
     * @throws BadRequestException Where the Klevu service rejects the request as invalid (4xx response code)
     * @throws BadResponseException Where the Klevu service does not return a valid response (timeouts, 5xx response)
     * @throws ValidationException Where provided credentials and/or feature list contains invalid information and
     *      fails internal validation. API request is NOT sent
     */
    public function execute(AccountCredentials $accountCredentials, ?array $features = null): AccountFeatures;
}
