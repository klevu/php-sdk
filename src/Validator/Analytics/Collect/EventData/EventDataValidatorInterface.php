<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Validator\Analytics\Collect\EventData;

use Klevu\PhpSDK\Model\Analytics\Collect\Event;
use Klevu\PhpSDK\Validator\ValidatorInterface;

/**
 * Service contract for classes used to validate /analytics/collect Event data
 *
 * @since 1.0.0
 */
interface EventDataValidatorInterface extends ValidatorInterface
{
    /**
     * Checks whether the validator instance applies to the passed Event
     *
     * Analytics Events contain a semantic version in the payload which we can use to register validators
     *  which target only specific versions of the data
     *
     * @param Event $event
     *
     * @return bool
     */
    public function appliesToEvent(Event $event): bool;
}
