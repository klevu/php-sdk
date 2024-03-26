<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Api\Service\Indexing;

use Klevu\PhpSDK\Api\Model\ApiResponseInterface;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\AccountCredentials;

/**
 * Contract for services responsible for deleting records in Klevu's index
 *
 * @link https://docs.klevu.com/indexing-apis/deleting-an-item-from-the-catalog
 * @link https://docs.klevu.com/indexing-apis/api-definition
 * @api
 * @since 1.0.0
 */
interface BatchDeleteServiceInterface extends BatchServiceInterface
{
    /**
     * Sends a request to Klevu to delete the indexed records corresponding to the passed ids
     *      for the specified account
     *
     * @api
     *
     * @param string[] $recordIds
     * @param AccountCredentials $accountCredentials
     *
     * @return ApiResponseInterface
     * @throws BadRequestException Where the Klevu service rejects the request as invalid (4xx response code)
     * @throws BadResponseException Where the Klevu service does not return a valid response (timeouts, 5xx response)
     *
     * @throws ValidationException Where the account credentials or record ids contain invalid
     *          information and fail internal validation. API request is NOT sent
     */
    public function sendByIds(
        AccountCredentials $accountCredentials,
        array $recordIds,
    ): ApiResponseInterface;
}
