<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Api\Model;

/**
 * Representation of an HTTP response from an API call made by the SDK
 *
 * @api
 * @since 1.0.0
 */
interface ApiResponseInterface
{
    /**
     * Whether the API request executed successfully and returned a 200 OK response without errors
     *
     * @return bool
     */
    public function isSuccess(): bool;

    /**
     * The HTTP status code returned by the response
     *
     * @example 200
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
     *
     * @return int
     */
    public function getResponseCode(): int;

    /**
     * An array of messages returned by the response
     * Includes both success and errors
     *
     * @return array<int, string>
     */
    public function getMessages(): array;
}
