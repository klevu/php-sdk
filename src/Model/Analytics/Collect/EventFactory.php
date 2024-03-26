<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model\Analytics\Collect;

/**
 * Factory class to create new instance of Event object
 *
 * @see Event
 * @since 1.0.0
 */
class EventFactory
{
    /**
     * @var UserProfileFactory
     */
    private readonly UserProfileFactory $userProfileFactory;

    /**
     * @param UserProfileFactory|null $userProfileFactory
     */
    public function __construct(
        ?UserProfileFactory $userProfileFactory = null,
    ) {
        $this->userProfileFactory = $userProfileFactory ?? new UserProfileFactory();
    }

    /**
     * @param mixed[] $data
     *
     * @return Event
     * @throws \TypeError Where data provided for an Event constructor argument is an invalid type
     * @throws \ValueError Where event is passed as string, but does not exist as a valid EventType value
     */
    public function create(array $data): Event
    {
        // phpstan errors suppressed as we allow TypeError to bubble
        $eventType = $data['event'] ?? null;
        if (!($eventType instanceof EventType)) {
            $eventType = EventType::from($eventType);
        }

        $userProfile = $data['userProfile'] ?? null;
        if (is_array($userProfile)) {
            $userProfile = $this->userProfileFactory->create($userProfile);
        }

        return new Event(
            event: $eventType,
            apikey: $data['apikey'] ?? '',
            version: $data['version'] ?? '',
            data: $data['data'] ?? [],
            userProfile: $userProfile,
        );
    }
}
