<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model\Indexing;

/**
 * Enumeration of JSON PATCH operations used by Klevu services
 *
 * @since 1.0.0
 */
enum UpdateOperations: string
{
    case ADD = 'add';
    case REMOVE = 'remove';
    case REPLACE = 'replace';

    /**
     * @return bool
     */
    public function requiresPath(): bool
    {
        return match ($this) {
            UpdateOperations::ADD, UpdateOperations::REMOVE, UpdateOperations::REPLACE => true,
        };
    }

    /**
     * @return bool
     */
    public function requiresValue(): bool
    {
        return match ($this) {
            UpdateOperations::ADD, UpdateOperations::REPLACE => true,
            UpdateOperations::REMOVE => false,
        };
    }
}
