<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Api\Model\Indexing;

/**
 * Data model contract for a Klevu Indexing update operation
 *
 * @api
 * @since 1.0.0
 * @link https://docs.klevu.com/indexing-apis/api-schema-swaggeropenapi-specification
 */
interface UpdateInterface
{
    /**
     * Returns record identifier on which update will be performed
     *
     * @return string
     */
    public function getRecordId(): string;

    /**
     * Returns operation to be performed
     *
     * @return string
     */
    public function getOp(): string;

    /**
     * Returns path of item to be acted upon, in JSON pointer format
     *
     * @return string|null
     */
    public function getPath(): ?string;

    /**
     * Returns value to be added or replaced for the item being acted upon
     *
     * @return mixed
     */
    public function getValue(): mixed;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
