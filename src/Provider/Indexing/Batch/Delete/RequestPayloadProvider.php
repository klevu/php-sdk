<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Provider\Indexing\Batch\Delete;

use Klevu\PhpSDK\Api\Model\Indexing\RecordInterface;
use Klevu\PhpSDK\Model\Indexing\RecordIterator;
use Klevu\PhpSDK\Provider\Indexing\Batch\RequestPayloadProviderInterface;

/**
 * Converts collection of Indexing Record objects into JSON format, suitable for sending to
 *  Klevu via API for batch DELETE requests
 *
 * @link https://docs.klevu.com/indexing-apis/api-definition
 * @since 1.0.0
 */
class RequestPayloadProvider implements RequestPayloadProviderInterface
{
    /**
     * Converts collection of Record objects into JSON string for DELETE operations
     *
     * @example {"ids":["ABC123"]}
     * @see RecordInterface
     *
     * @param RecordIterator $records
     *
     * @return string
     */
    public function get(RecordIterator $records): string
    {
        $requestBody = [
            'ids' => array_map(
                static fn (RecordInterface $record): string => $record->getId(),
                $records->toArray(),
            ),
        ];

        return (string)json_encode($requestBody);
    }
}
