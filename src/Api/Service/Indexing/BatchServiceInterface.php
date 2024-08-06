<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Api\Service\Indexing;

use Klevu\PhpSDK\Api\Model\ApiResponseInterface;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ApiExceptionInterface;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\AccountCredentials;
use Klevu\PhpSDK\Model\HttpMethods;
use Klevu\PhpSDK\Model\Indexing\RecordIterator;
use Klevu\PhpSDK\Model\Indexing\UpdateIterator;
use Klevu\PhpSDK\Model\IteratorInterface;
use Klevu\PhpSDK\Service\ApiServiceInterface;

/**
 * Service responsible for adding and updating records in Klevu's index
 *
 * @link https://docs.klevu.com/indexing-apis/how-to-do-with-examples
 * @link https://docs.klevu.com/indexing-apis/api-schema-swaggeropenapi-specification
 * @api
 * @since 1.0.0
 */
interface BatchServiceInterface extends ApiServiceInterface
{
    /**
     * Sends a request to Klevu's batch indexing endpoint containing records to be actioned
     *
     * @api
     *
     * @param AccountCredentials $accountCredentials
     * @param IteratorInterface $records
     * @param string|HttpMethods $method
     *
     * @return ApiResponseInterface
     * @throws \ValueError On invalid HTTP Method
     * @throws ApiExceptionInterface
     * @throws BadRequestException Where the Klevu service rejects the request as invalid (4xx response code)
     * @throws BadResponseException Where the Klevu service does not return a valid response (timeouts, 5xx response)
     * @throws ValidationException Where the account credentials or record arguments contain invalid
     *          information and fail internal validation. API request is NOT sent
     */
    public function send(
        AccountCredentials $accountCredentials,
        IteratorInterface $records,
        string|HttpMethods $method = HttpMethods::PUT,
    ): ApiResponseInterface;

    /**
     * Sends a PUT request to Klevu's batch indexing endpoint containing records to be actioned
     *
     * @api
     *
     * @param AccountCredentials $accountCredentials
     * @param RecordIterator $records
     *
     * @return ApiResponseInterface
     * @throws ApiExceptionInterface
     * @throws BadRequestException Where the Klevu service rejects the request as invalid (4xx response code)
     * @throws BadResponseException Where the Klevu service does not return a valid response (timeouts, 5xx response)
     * @throws ValidationException Where the account credentials or record arguments contain invalid
     *          information and fail internal validation. API request is NOT sent
     */
    public function put(
        AccountCredentials $accountCredentials,
        RecordIterator $records,
    ): ApiResponseInterface;

    /**
     * Sends a PATCH request to Klevu's batch indexing endpoint containing updates to be actioned
     *
     * @api
     *
     * @param AccountCredentials $accountCredentials
     * @param UpdateIterator $updates
     *
     * @return ApiResponseInterface
     * @throws ApiExceptionInterface
     * @throws BadRequestException Where the Klevu service rejects the request as invalid (4xx response code)
     * @throws BadResponseException Where the Klevu service does not return a valid response (timeouts, 5xx response)
     * @throws ValidationException Where the account credentials or record arguments contain invalid
     *           information and fail internal validation. API request is NOT sent
     */
    public function patch(
        AccountCredentials $accountCredentials,
        UpdateIterator $updates,
    ): ApiResponseInterface;
}
