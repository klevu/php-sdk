<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Provider\Indexing\Batch\RequestPayloadProvider;

use Klevu\PhpSDK\Api\Model\Indexing\UpdateInterface;
use Klevu\PhpSDK\Model\Indexing\Update as UpdateModel;
use Klevu\PhpSDK\Model\Indexing\UpdateIterator;
use Klevu\PhpSDK\Model\Indexing\UpdateOperations;
use Klevu\PhpSDK\Model\IteratorInterface;
use Klevu\PhpSDK\Provider\Indexing\Batch\RequestPayloadProviderInterface;

class Update implements RequestPayloadProviderInterface
{
    /**
     * @param IteratorInterface $records
     *
     * @return string
     */
    public function get(IteratorInterface $records): string
    {
        if (!($records instanceof UpdateIterator)) {
            return '';
        }

        $requestBody = [];
        /** @var UpdateInterface $record */
        foreach ($records as $record) {
            $recordId = $record->getRecordId();
            $op = UpdateOperations::tryFrom($record->getOp());
            if (!$recordId || !$op) {
                continue;
            }

            $requestBody[$recordId] ??= [];

            $recordData = [
                UpdateModel::FIELD_OP => $op->value,
            ];
            if ($op->requiresPath()) {
                $recordData[UpdateModel::FIELD_PATH] = $record->getPath();
            }
            if ($op->requiresValue()) {
                $recordData[UpdateModel::FIELD_VALUE] = $record->getValue();
            }

            $requestBody[$recordId][] = $recordData;
        }

        return (string)json_encode($requestBody);
    }
}
