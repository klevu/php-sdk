<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Api\Service\Account;

use Klevu\PhpSDK\Api\Model\ApiResponseInterface;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ApiExceptionInterface;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\AccountCredentials;
use Klevu\PhpSDK\Service\ApiServiceInterface;

/**
 * Contract for service used to update the XML data feed endpoint for accounts using the Klevu Feed Monitor service
 *
 * @api
 * @since 1.0.0
 */
interface UpdateStoreFeedUrlServiceInterface extends ApiServiceInterface
{
    /**
     * Key used in payload to send new indexing URL
     *
     * @var string
     */
    final public const PAYLOAD_KEY_INDEXING_URL = 'indexingUrl';
    /**
     * Key used in payload to send store type
     *
     * @var string
     */
    final public const PAYLOAD_KEY_STORE_TYPE = 'storeType';
    /**
     * Key used in payload to send store URL
     *
     * @var string
     */
    final public const PAYLOAD_KEY_STORE_URL = 'storeUrl';

    /**
     * Send a request to Klevu to update the XML data feed endpoint for the account specified
     *  in $accountCredentials
     *
     * @note Requests can only be made for Shopify or BigCommerce stores, as designated by $storeType
     *
     * @api
     *
     * @param string $indexingUrl The new URL from which XML data feeds should be collected
     * @param string $storeType The store type. See {@see \Klevu\PhpSDK\Model\Platforms} for reference
     * @param string $storeUrl The URL, name, or other identifier for the store as configured in the Klevu account
     * @param AccountCredentials $accountCredentials
     *
     * @return ApiResponseInterface
     * @throws ApiExceptionInterface
     * @throws BadRequestException Where the Klevu service rejects the request as invalid (4xx response code)
     * @throws BadResponseException Where the Klevu service does not return a valid response (timeouts, 5xx response)
     * @throws ValidationException Where one or more provided arguments contain invalid information and fail
     *      internal validation. API request is NOT sent
     */
    public function execute(
        AccountCredentials $accountCredentials,
        string $indexingUrl,
        string $storeType,
        string $storeUrl,
    ): ApiResponseInterface;
}
