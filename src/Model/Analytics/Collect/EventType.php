<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model\Analytics\Collect;

/**
 * Enumeration of type values which are supported when sending events via Analytics Collect
 *
 * @see Event::$event
 * @since 1.0.0
 */
enum EventType: string
{
    case ORDER_PURCHASE = 'order_purchase';
}
