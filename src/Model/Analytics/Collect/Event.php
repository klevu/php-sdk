<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model\Analytics\Collect;

use Klevu\PhpSDK\Service\Analytics\CollectService;

/**
 * Immutable object representing Event sent to Analytics Collect API endpoint
 *
 * @link https://docs.klevu.com/apis/smart-search-analytics-events#Ai6mR
 * @see CollectService::send()
 * @since 1.0.0
 */
class Event
{
    /**
     * @param EventType $event The event name (restricted to EventOptions options) for which to track event
     * @param string $apikey The JS API Key, in the format klevu-xxxxxxxxxx
     * @param string $version Semantic version; see Klevu developer docs for latest version
     * @param mixed[] $data Array containing event data payload
     * @param UserProfile|null $userProfile Details of customer triggering the event
     */
    public function __construct(
        public readonly EventType $event,
        public readonly string $apikey,
        public readonly string $version,
        public readonly array $data,
        public readonly ?UserProfile $userProfile = null,
    ) {
    }
}
