<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Service\Indexing;

/**
 * Enumeration of modes available to handle invalid records encountered in batches
 *
 * @see BatchService::send()
 * @since 1.0.0
 */
enum InvalidRecordMode
{
    /**
     * Invalid records will be removed from a batch. Processing will continue
     *  for any valid records remaining.
     */
    case SKIP;
    /**
     * Presence of an invalid record will cause the entire batch to be rejected
     *  prior to processing.
     */
    case FAIL;
}
