<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model\Indexing;

/**
 * Factory class to create new instance of Attribute object
 *
 * @see Attribute
 * @since 1.0.0
 */
class AttributeFactory
{
    /**
     * Creates a new instance of Attribute, populated with passed data
     *
     * @param mixed[] $data Array of data with keys corresponding to Attribute FIELD_* constants
     *      For example, ['attributeName' => 'colour', 'label' => ['default' => 'Colour']]
     *
     * @return Attribute
     * @throws \TypeError Where value provided for data key does not match required type.
     *       For example, ['attributeName' => [false]]
     */
    public function create(array $data): Attribute
    {
        $data = array_filter(
            array: $data,
            callback: static fn ($value): bool => null !== $value,
        );

        $datatype = $data[Attribute::FIELD_DATATYPE] ?? '';
        if ($datatype instanceof DataType) {
            $datatype = $datatype->value;
        }

        $attribute = new Attribute(
            attributeName: (string)($data[Attribute::FIELD_ATTRIBUTE_NAME] ?? ''), // @phpstan-ignore-line
            datatype: $datatype,
        );

        if (array_key_exists(Attribute::FIELD_LABEL, $data)) {
            if (is_string($data[Attribute::FIELD_LABEL])) {
                $data[Attribute::FIELD_LABEL] = [
                    'default' => $data[Attribute::FIELD_LABEL],
                ];
            }
            $attribute->setLabel($data[Attribute::FIELD_LABEL]); // @phpstan-ignore-line
        }
        if (array_key_exists(Attribute::FIELD_SEARCHABLE, $data)) {
            $attribute->setSearchable($data[Attribute::FIELD_SEARCHABLE]); // @phpstan-ignore-line
        }
        if (array_key_exists(Attribute::FIELD_FILTERABLE, $data)) {
            $attribute->setFilterable($data[Attribute::FIELD_FILTERABLE]); // @phpstan-ignore-line
        }
        if (array_key_exists(Attribute::FIELD_RETURNABLE, $data)) {
            $attribute->setReturnable($data[Attribute::FIELD_RETURNABLE]); // @phpstan-ignore-line
        }
        if (array_key_exists(Attribute::FIELD_ABBREVIATE, $data)) {
            $attribute->setAbbreviate($data[Attribute::FIELD_ABBREVIATE]); // @phpstan-ignore-line
        }
        if (array_key_exists(Attribute::FIELD_RANGEABLE, $data)) {
            $attribute->setRangeable($data[Attribute::FIELD_RANGEABLE]); // @phpstan-ignore-line
        }
        if (array_key_exists(Attribute::FIELD_ALIASES, $data)) {
            $attribute->setAliases($data[Attribute::FIELD_ALIASES]); // @phpstan-ignore-line
        }
        if (array_key_exists(Attribute::FIELD_IMMUTABLE, $data)) {
            $attribute->setImmutable($data[Attribute::FIELD_IMMUTABLE]); // @phpstan-ignore-line
        }

        return $attribute;
    }
}
