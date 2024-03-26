<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Api\Model\Indexing;

/**
 * Data model contract for a Klevu Indexing record, such as a product
 *
 * @api
 * @since 1.0.0
 * @link https://docs.klevu.com/indexing-apis/add-simple-products-in-your-catalog
 * @link https://docs.klevu.com/indexing-apis/api-definition
 */
interface RecordInterface
{
    /**
     * Returns the record's unique identifier within a store
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Returns the type of item being indexed
     *
     * @return string
     * @example KLEVU_PRODUCT
     */
    public function getType(): string;

    /**
     * Returns item relations, such as categories or parent relationships
     *
     * @return array<string, mixed[]>|null
     */
    public function getRelations(): ?array;

    /**
     * Returns array of all attribute name => value pairs to be updated in index for searching
     *  and faceting functionality
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array;

    /**
     * Returns array of all attribute name => value pairs to be stored in Klevu's index for
     *  return with search requests, for example to use in frontend customisations
     *
     * @return array<string, mixed>|null
     */
    public function getDisplay(): ?array;

    /**
     * Returns the record data in array format
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
