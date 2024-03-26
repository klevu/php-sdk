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
        $datatype = $data[Attribute::FIELD_DATATYPE] ?? null;
        if ($datatype instanceof DataType) {
            $datatype = $datatype->value;
        }

        $attribute = new Attribute(
            attributeName: $data[Attribute::FIELD_ATTRIBUTE_NAME] ?? null,
            datatype: $datatype,
        );

        if (array_key_exists(Attribute::FIELD_LABEL, $data)) {
            if (is_string($data[Attribute::FIELD_LABEL])) {
                $data[Attribute::FIELD_LABEL] = [
                    'default' => $data[Attribute::FIELD_LABEL],
                ];
            }
            $attribute->setLabel($data[Attribute::FIELD_LABEL]);
        }
        if (array_key_exists(Attribute::FIELD_SEARCHABLE, $data)) {
            $attribute->setSearchable($data[Attribute::FIELD_SEARCHABLE]);
        }
        if (array_key_exists(Attribute::FIELD_FILTERABLE, $data)) {
            $attribute->setFilterable($data[Attribute::FIELD_FILTERABLE]);
        }
        if (array_key_exists(Attribute::FIELD_RETURNABLE, $data)) {
            $attribute->setReturnable($data[Attribute::FIELD_RETURNABLE]);
        }
        if (array_key_exists(Attribute::FIELD_IMMUTABLE, $data)) {
            $attribute->setImmutable($data[Attribute::FIELD_IMMUTABLE]);
        }

        return $attribute;
    }
}
