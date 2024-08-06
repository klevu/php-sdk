<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Api\Service\Account;

use Klevu\PhpSDK\Api\Model\AccountInterface;
use Klevu\PhpSDK\Exception\AccountNotFoundException;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ApiExceptionInterface;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\AccountCredentials;
use Klevu\PhpSDK\Service\ApiServiceInterface;

/**
 * Contract for service handling look-ups of Klevu account information via API
 *
 * @see AccountInterface
 * @api
 * @since 1.0.0
 */
interface AccountLookupServiceInterface extends ApiServiceInterface
{
    /**
     * Send a request to Klevu to retrieve details for the account specified in $accountCredentials
     *
     * @api
     *
     * @param AccountCredentials $accountCredentials
     *
     * @return AccountInterface
     * @throws ApiExceptionInterface
     * @throws BadRequestException Where the Klevu service rejects the request as invalid (4xx response code)
     * @throws BadResponseException Where the Klevu service does not return a valid response (timeouts, 5xx response)
     * @throws AccountNotFoundException Where no account is found for the provided credentials
     * @throws ValidationException Where provided credentials fail internal validation. API request is NOT sent
     */
    public function execute(AccountCredentials $accountCredentials): AccountInterface;
}
