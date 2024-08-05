<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model\Indexing;

/**
 * Factory class to create new instance of Record object
 *
 * @see Record
 * @since 1.0.0
 */
class RecordFactory
{
    /**
     * Creates a new instance of Record, populated with passed data
     *
     * @param mixed[] $data Array of data with keys corresponding to Record FIELD_* constants
     *      For example, ['relations' => ['parentProduct' => [...]]
     *
     * @return Record
     * @throws \TypeError Where value provided for data key does not match required type.
     *      For example, ['relations' => 'product']
     */
    public function create(array $data): Record
    {
        // phpstan errors suppressed as we allow TypeError to bubble
        $record = new Record(
            id: $data[Record::FIELD_ID] ?? null,
            type: $data[Record::FIELD_TYPE] ?? '',
        );

        if (array_key_exists(Record::FIELD_RELATIONS, $data)) {
            $record->setRelations($data[Record::FIELD_RELATIONS]);
        }
        if (array_key_exists(Record::FIELD_ATTRIBUTES, $data)) {
            $record->setAttributes($data[Record::FIELD_ATTRIBUTES]);
        }
        if (array_key_exists(Record::FIELD_GROUPS, $data)) {
            $record->setGroups($data[Record::FIELD_GROUPS]);
        }
        if (array_key_exists(Record::FIELD_CHANNELS, $data)) {
            $record->setChannels($data[Record::FIELD_CHANNELS]);
        }

        return $record;
    }
}
