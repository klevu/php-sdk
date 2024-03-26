<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Provider\Indexing\Batch;

use Klevu\PhpSDK\Api\Model\Indexing\RecordInterface;
use Klevu\PhpSDK\Model\Indexing\RecordIterator;

/**
 * Converts collection of Indexing Record objects into JSON format, suitable for sending to
 *  Klevu via API for batch ADD or UPDATE requests
 *
 * @link https://docs.klevu.com/indexing-apis/api-definition
 * @since 1.0.0
 */
class RequestPayloadProvider implements RequestPayloadProviderInterface
{
    /**
     * Converts collection of Record objects into JSON string for ADD / UPDATE operations
     *
     * @example [{"id":"ABC123", "type":"KLEVU_PRODUCT", "attributes":{"name":{"default":"Example Product"}}}]
     * @see RecordInterface
     *
     * @param RecordIterator $records
     *
     * @return string
     */
    public function get(RecordIterator $records): string
    {
        $requestBody = [];
        /** @var RecordInterface $record */
        foreach ($records as $record) {
            $recordData = [
                'id' => $record->getId(),
                'type' => $record->getType(),
            ];

            $relations = $record->getRelations();
            if (null !== $relations) {
                $recordData['relations'] = $relations;
            }

            $recordData['attributes'] = $record->getAttributes();

            $display = $record->getDisplay();
            if (null !== $display) {
                $recordData['display'] = $display;
            }

            $requestBody[] = $recordData;
        }

        return (string)json_encode($requestBody);
    }
}
