<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Api\Service\Indexing;

use Klevu\PhpSDK\Api\Model\ApiResponseInterface;
use Klevu\PhpSDK\Api\Model\Indexing\AttributeInterface;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\AccountCredentials;
use Klevu\PhpSDK\Model\Indexing\AttributeIterator;
use Klevu\PhpSDK\Service\ApiServiceInterface;
use Klevu\PhpSDK\Validator\Indexing\AttributeValidator;

/**
 * Contract for services responsible for managing attributes registered with the Klevu indexing service
 *
 * @link https://docs.klevu.com/indexing-apis/adding-additionalcustom-attributes-to-a-product
 * @link https://docs.klevu.com/indexing-apis/api-definition
 * @api
 * @since 1.0.0
 */
interface AttributesServiceInterface extends ApiServiceInterface
{
    /**
     * Looks up and returns a single indexing attribute registered to the specified account,
     *  as identified by the attribute name
     *
     * @note Attributes are identified by the attribute name property. Note that this is case-insensitive,
     *   so if attribute FOO exists and the passed attribute name is foo, you will receive a response containing
     *   the FOO object
     *
     * @api
     *
     * @param string $attributeName Alphanumeric string, optionally containing underscore
     * @param AccountCredentials $accountCredentials
     *
     * @return AttributeInterface|null Returns null on a valid request where the attribute does not exist
     * @throws BadRequestException Where the Klevu service rejects the request as invalid (4xx response code)
     * @throws BadResponseException Where the Klevu service does not return a valid response (timeouts, 5xx response)
     * @throws ValidationException Where the account credentials or attribute name arguments contain invalid
     *      information and fail internal validation. API request is NOT sent
     */
    public function getByName(
        AccountCredentials $accountCredentials,
        string $attributeName,
    ): ?AttributeInterface;

    /**
     * Retrieves and returns all indexing attributes registered to the specified account,
     *  including default attributes created by Klevu
     *
     * @api
     *
     * @param AccountCredentials $accountCredentials
     *
     * @return AttributeIterator
     * @throws BadRequestException Where the Klevu service rejects the request as invalid (4xx response code)
     * @throws BadResponseException Where the Klevu service does not return a valid response (timeouts, 5xx response)
     * @throws ValidationException Where the account credentials contain invalid information and fail internal
     *      validation. API request is NOT sent
     */
    public function get(
        AccountCredentials $accountCredentials,
    ): AttributeIterator;

    /**
     * Creates or updates an indexing attribute for the specified account
     *
     * @note Attributes are identified by the attribute name property. Note that this is case-insensitive,
     *  so if attribute FOO exists and the passed attribute name is foo, a new attribute will NOT be created
     *
     * @see AttributeValidator
     * @api
     *
     * @param AccountCredentials $accountCredentials
     * @param AttributeInterface $attribute
     *
     * @return ApiResponseInterface
     * @throws BadResponseException Where the Klevu service does not return a valid response (timeouts, 5xx response)
     * @throws ValidationException Where the account credentials or attribute arguments contain invalid
     *       information and fail internal validation. API request is NOT sent
     * @throws BadRequestException Where the Klevu service rejects the request as invalid (4xx response code)
     */
    public function put(
        AccountCredentials $accountCredentials,
        AttributeInterface $attribute,
    ): ApiResponseInterface;

    /**
     * Deletes an indexing attribute for the specified account
     *
     * @note Attributes are identified by the attribute name property. Note that this is case-insensitive,
     *   so if attribute FOO exists and the passed attribute's name is foo, FOO will be deleted
     *
     * @api
     *
     * @param AttributeInterface $attribute
     * @param AccountCredentials $accountCredentials
     *
     * @return ApiResponseInterface
     * @throws BadRequestException Where the Klevu service rejects the request as invalid (4xx response code)
     * @throws BadResponseException Where the Klevu service does not return a valid response (timeouts, 5xx response)
     * @throws ValidationException Where the account credentials or attribute arguments contain invalid
     *        information and fail internal validation. API request is NOT sent
     */
    public function delete( // phpcs:ignore Security.BadFunctions.FilesystemFunctions.WarnFilesystem
        AccountCredentials $accountCredentials,
        AttributeInterface $attribute,
    ): ApiResponseInterface;

    /**
     * Deletes an indexing attribute for the specified account by name
     *
     * @note Attributes are identified by the attribute name property. Note that this is case-insensitive,
     * so if attribute FOO exists and the passed attribute name is foo, FOO will be deleted
     *
     * @api
     *
     * @param string $attributeName Alphanumeric string, optionally containing underscore
     * @param AccountCredentials $accountCredentials
     *
     * @return ApiResponseInterface
     * @throws BadRequestException Where the Klevu service rejects the request as invalid (4xx response code)
     * @throws BadResponseException Where the Klevu service does not return a valid response (timeouts, 5xx response)
     * @throws ValidationException Where the account credentials or attribute name arguments contain invalid
     *       information and fail internal validation. API request is NOT sent
     */
    public function deleteByName(
        AccountCredentials $accountCredentials,
        string $attributeName,
    ): ApiResponseInterface;
}
