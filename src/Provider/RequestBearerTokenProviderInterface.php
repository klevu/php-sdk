<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Provider;

use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\AccountCredentials;
use Klevu\PhpSDK\Service\ApiServiceInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Service contract for classes responsible for generating bearer tokens used to authenticate
 *  requests to Klevu's JSON indexing APIs
 *
 * @link https://docs.klevu.com/indexing-apis/authentication
 * @since 1.0.0
 */
interface RequestBearerTokenProviderInterface
{
    /**
     * Header key used to reference request timestamp when generating token
     *
     * @see ApiServiceInterface::API_HEADER_KEY_TIMESTAMP
     * @var string
     */
    final public const API_HEADER_KEY_TIMESTAMP = ApiServiceInterface::API_HEADER_KEY_TIMESTAMP;
    /**
     * Header key used to reference the JavaScript API Key (public key) when generating token
     *
     * @see ApiServiceInterface::API_HEADER_KEY_APIKEY
     * @var string
     */
    final public const API_HEADER_KEY_APIKEY = ApiServiceInterface::API_HEADER_KEY_APIKEY;
    /**
     * Header key used to reference the authorization algorithm used when generating token
     *
     * @see ApiServiceInterface::API_HEADER_KEY_AUTH_ALGO
     * @var string
     */
    final public const API_HEADER_KEY_AUTH_ALGO = ApiServiceInterface::API_HEADER_KEY_AUTH_ALGO;
    /**
     * Header key used to reference the request content type used when generating token
     *
     * @var string
     */
    final public const API_HEADER_KEY_CONTENT_TYPE = 'Content-Type';

    /**
     * Generates and returns a bearer token used for API authentication
     *
     * @param AccountCredentials $accountCredentials
     * @param RequestInterface $request
     *
     * @return string
     * @throws ValidationException When account credentials or request headers are found invalid
     */
    public function getForRequest(
        AccountCredentials $accountCredentials,
        RequestInterface $request,
    ): string;
}
