<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Provider\Indexing\Batch\RequestPayloadProvider;

use Klevu\PhpSDK\Api\Model\Indexing\RecordInterface;
use Klevu\PhpSDK\Model\Indexing\Record as RecordModel;
use Klevu\PhpSDK\Model\Indexing\RecordIterator;
use Klevu\PhpSDK\Model\IteratorInterface;
use Klevu\PhpSDK\Provider\Indexing\Batch\RequestPayloadProviderInterface;

class Record implements RequestPayloadProviderInterface
{
    /**
     * Converts collection of Record objects into JSON string for ADD / UPDATE operations
     *
     * @example [{"id":"ABC123", "type":"KLEVU_PRODUCT", "attributes":{"name":{"default":"Example Product"}}}]
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

        $requestBody = [];
        /** @var RecordInterface $record */
        foreach ($records as $record) {
            $recordData = [
                RecordModel::FIELD_ID => $record->getId(),
                RecordModel::FIELD_TYPE => $record->getType(),
            ];

            $relations = $record->getRelations();
            if (null !== $relations) {
                $recordData[RecordModel::FIELD_RELATIONS] = $relations;
            }

            $recordData[RecordModel::FIELD_ATTRIBUTES] = $record->getAttributes();

            $groups = $record->getGroups();
            if (null !== $groups) {
                $recordData[RecordModel::FIELD_GROUPS] = $groups;
            }

            $channels = $record->getChannels();
            if (null !== $channels) {
                $recordData[RecordModel::FIELD_CHANNELS] = $channels;
            }

            $requestBody[] = $recordData;
        }

        return (string)json_encode($requestBody);
    }
}
