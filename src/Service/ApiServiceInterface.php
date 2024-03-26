<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Service;

use Klevu\PhpSDK\Provider\UserAgentProviderInterface;

/**
 * Service contract for classes which directly interact with Klevu's APIs
 *
 * Each service class should interact with a single endpoint, though may incorporate multiple
 *  request types (GET, PUT, etc). As such, the method getEndpoint() is required however no
 *  execution methods are enforced.
 *
 * @since 1.0.0
 */
interface ApiServiceInterface
{
    /**
     * Header key used to provide current timestamp in requests to Klevu's APIs
     *
     * @var string
     */
    final public const API_HEADER_KEY_TIMESTAMP = 'X-KLEVU-TIMESTAMP';
    /**
     * Header key used to provide the JavaScript API Key (public key) in requests to Klevu's APIs
     *
     * @var string
     */
    final public const API_HEADER_KEY_APIKEY = 'X-KLEVU-APIKEY';
    /**
     * Header key used to provide the authorization algorithm used in requests to Klevu's APIs
     *
     * @var string
     */
    final public const API_HEADER_KEY_AUTH_ALGO = 'X-KLEVU-AUTH-ALGO';

    /**
     * Returns the full endpoint for requests made by this service class
     *
     * @example https://indexing.ksearchnet.com/attributes
     *
     * @return string
     * @throws \LogicException On internal errors encountered by the application, such as incorrectly
     *      configured base URLs information
     */
    public function getEndpoint(): string;

    /**
     * Returns the object responsible for handling User-Agent provision for this service
     *
     * Method provided to allow entry point for injecting and modifying user agent strings
     *
     * @return UserAgentProviderInterface|null
     */
    public function getUserAgentProvider(): ?UserAgentProviderInterface;
}
