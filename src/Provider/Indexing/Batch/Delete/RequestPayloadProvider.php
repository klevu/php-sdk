<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Provider\Indexing\Batch\Delete;

use Klevu\PhpSDK\Api\Model\Indexing\RecordInterface;
use Klevu\PhpSDK\Model\Indexing\RecordIterator;
use Klevu\PhpSDK\Model\IteratorInterface;
use Klevu\PhpSDK\Provider\Indexing\Batch\RequestPayloadProviderInterface;

/**
 * Converts collection of Indexing Record objects into JSON format, suitable for sending to
 *  Klevu via API for batch DELETE requests
 *
 * @link https://docs.klevu.com/indexing-apis/api-schema-swaggeropenapi-specification
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
     * @param IteratorInterface $records
     *
     * @return string
     */
    public function get(IteratorInterface $records): string
    {
        if (!($records instanceof RecordIterator)) {
            return '';
        }

        $requestBody = [
            'ids' => array_map(
                callback: static fn (RecordInterface $record): string => $record->getId(),
                array: $records->toArray(),
            ),
        ];

        return (string)json_encode($requestBody);
    }
}
