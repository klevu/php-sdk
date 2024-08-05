<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model\Indexing;

class UpdateFactory
{
    /**
     * @param mixed[] $data
     *
     * @return Update
     */
    public function create(array $data): Update
    {
        $update = new Update();

        if (array_key_exists(Update::FIELD_RECORD_ID, $data)) {
            $update->setRecordId($data[Update::FIELD_RECORD_ID]);
        }
        if (array_key_exists(Update::FIELD_OP, $data)) {
            if ($data[Update::FIELD_OP] instanceof UpdateOperations) {
                $data[Update::FIELD_OP] = $data[Update::FIELD_OP]->value;
            }
            $update->setOp($data[Update::FIELD_OP]);
        }
        if (array_key_exists(Update::FIELD_PATH, $data)) {
            $update->setPath($data[Update::FIELD_PATH]);
        }
        if (array_key_exists(Update::FIELD_VALUE, $data)) {
            $update->setValue($data[Update::FIELD_VALUE]);
        }

        return $update;
    }
}
