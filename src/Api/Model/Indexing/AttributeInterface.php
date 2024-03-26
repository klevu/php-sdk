<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Api\Model\Indexing;

/**
 * Data model contract for a Klevu Indexing attribute
 *
 * @api
 * @since 1.0.0
 * @link https://docs.klevu.com/indexing-apis/adding-additionalcustom-attributes-to-a-product
 * @link https://docs.klevu.com/indexing-apis/api-definition
 */
interface AttributeInterface
{
    /**
     * Returns the attribute name
     *
     * @return string
     * @example color
     */
    public function getAttributeName(): string;

    /**
     * Returns the attribute's data type
     *
     * @return string
     * @example STRING
     * @see DataType
     */
    public function getDatatype(): string;

    /**
     * Returns array of labels, using channel codes as array keys
     *
     * Object keys should be ISO 639-1 standard and values are strings.
     *  One key is expected to always be present, which is 'default'.
     *
     * @return array<string, string>
     * @example ["default" => "Colour", "en-GB" => "Colour", "en-US" => "Color", "fi-FI" => "Väri"]
     */
    public function getLabel(): array;

    /**
     * Whether this attribute's value is considered when matching search results
     *
     * @return bool
     */
    public function isSearchable(): bool;

    /**
     * Whether this attribute is used to create facets in search results
     *
     * @return bool
     */
    public function isFilterable(): bool;

    /**
     * Whether this attribute's value is returned as part of search results for use in customisations
     *
     * @return bool
     */
    public function isReturnable(): bool;

    /**
     * Whether this attribute can be modified and/or deleted
     *
     * During indexing setup, Klevu will register a number of core attributes which cannot be modified or removed.
     *  These will be returned by calls to the API alongside custom attributes
     *
     * @return bool
     */
    public function isImmutable(): bool;

    /**
     * Returns the attribute data in array format
     *
     * @return array<string, string|array<string, string>|bool>
     */
    public function toArray(): array;
}
