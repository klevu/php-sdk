<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Provider\Indexing\Batch;

use Klevu\PhpSDK\Model\Indexing\RecordIterator;

/**
 * Service contract for classes responsible for converting a set of Indexing Record objects
 *  into a format suitable for sending to Klevu via API
 *
 * @link https://docs.klevu.com/indexing-apis/api-definition
 * @since 1.0.0
 */
interface RequestPayloadProviderInterface
{
    /**
     * Converts collection of {@see RecordInterface} objects into string representation
     *
     * @param RecordIterator $records
     *
     * @return string
     */
    public function get(RecordIterator $records): string;
}
