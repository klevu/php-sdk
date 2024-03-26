<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Api\Service\Analytics;

use Klevu\PhpSDK\Api\Model\ApiResponseInterface;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\Analytics\Collect\Event;
use Klevu\PhpSDK\Model\Analytics\Collect\EventIterator;
use Klevu\PhpSDK\Service\ApiServiceInterface;

/**
 * Contract for services responsible for sending analytics events to the /analytics/collect endpoint
 *
 * @link https://docs.klevu.com/apis/smart-search-analytics-events#Ai6mR
 * @see Event
 * @api
 * @since 1.0.0
 */
interface CollectServiceInterface extends ApiServiceInterface
{
    /**
     * Send a request to the Klevu analytics endpoint for one or more events
     *
     * @api
     *
     * @param EventIterator $events
     *
     * @return ApiResponseInterface
     * @throws BadRequestException Where the Klevu service rejects the request as invalid (4xx response code)
     * @throws BadResponseException Where the Klevu service does not return a valid response (timeouts, 5xx response)
     * @throws ValidationException Where one or more provided events contain invalid information and fail internal
     *      validation. API request is NOT sent
     */
    public function send(EventIterator $events): ApiResponseInterface;
}
