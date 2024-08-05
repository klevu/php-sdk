<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model\Indexing;

use Klevu\PhpSDK\Api\Model\Indexing\AttributeInterface;
use Klevu\PhpSDK\Exception\CouldNotUpdateException;
use Klevu\PhpSDK\Service\Indexing\AttributesService;

/**
 * Data model representation of a Klevu Indexing attribute
 *
 * @note Does not perform validation or persistence of data
 * @link https://docs.klevu.com/indexing-apis/adding-additionalcustom-attributes-to-a-product
 * @see AttributesService
 * @since 1.0.0
 */
class Attribute implements AttributeInterface
{
    /**
     * Key used to reference attributeName property when converting to/from array
     *
     * @see Attribute::toArray()
     * @see AttributeFactory::create()
     * @var string
     */
    final public const FIELD_ATTRIBUTE_NAME = 'attributeName';
    /**
     * Key used to reference datatype property when converting to/from array
     *
     * @see Attribute::toArray()
     * @see AttributeFactory::create()
     * @var string
     */
    final public const FIELD_DATATYPE = 'datatype';
    /**
     * Key used to reference label property when converting to/from array
     *
     * @see Attribute::toArray()
     * @see AttributeFactory::create()
     * @var string
     */
    final public const FIELD_LABEL = 'label';
    /**
     * Key used to reference searchable property when converting to/from array
     *
     * @see Attribute::toArray()
     * @see AttributeFactory::create()
     * @var string
     */
    final public const FIELD_SEARCHABLE = 'searchable';
    /**
     * Key used to reference filterable property when converting to/from array
     *
     * @see Attribute::toArray()
     * @see AttributeFactory::create()
     * @var string
     */
    final public const FIELD_FILTERABLE = 'filterable';
    /**
     * Key used to reference returnable property when converting to/from array
     *
     * @see Attribute::toArray()
     * @see AttributeFactory::create()
     * @var string
     */
    final public const FIELD_RETURNABLE = 'returnable';
    /**
     * Key used to reference abbreviate property when converting to/from array
     *
     * @see Attribute::toArray()
     * @see AttributeFactory::create()
     * @var string
     */
    final public const FIELD_ABBREVIATE = 'abbreviate';
    /**
     * Key used to reference rangeable property when converting to/from array
     *
     * @see Attribute::toArray()
     * @see AttributeFactory::create()
     * @var string
     */
    final public const FIELD_RANGEABLE = 'rangeable';
    /**
     * Key used to reference immutable property when converting to/from array
     *
     * @see Attribute::toArray()
     * @see AttributeFactory::create()
     * @var string
     */
    final public const FIELD_IMMUTABLE = 'immutable';

    /**
     * @var string
     */
    private readonly string $attributeName;
    /**
     * @var string
     */
    private readonly string $datatype;
    /**
     * @var string[]
     */
    private array $label = [];
    /**
     * @var bool
     */
    private bool $searchable = true;
    /**
     * @var bool
     */
    private bool $filterable = true;
    /**
     * @var bool
     */
    private bool $returnable = true;
    /**
     * @var bool
     */
    private bool $abbreviate = false;
    /**
     * @var bool
     */
    private bool $rangeable = false;
    /**
     * @var bool
     */
    private bool $immutable = false;

    /**
     * @param string $attributeName
     * @param string $datatype
     * @param string[]|null $label
     * @param bool|null $searchable
     * @param bool|null $filterable
     * @param bool|null $returnable
     * @param bool|null $immutable
     */
    public function __construct(
        string $attributeName,
        string $datatype,
        ?array $label = null,
        ?bool $searchable = null,
        ?bool $filterable = null,
        ?bool $returnable = null,
        ?bool $abbreviate = null,
        ?bool $rangeable = null,
        ?bool $immutable = null,
    ) {
        $this->attributeName = $attributeName;
        $this->datatype = $datatype;
        if (null !== $label) {
            $this->setLabel($label);
        }
        if (null !== $searchable) {
            $this->setSearchable($searchable);
        }
        if (null !== $filterable) {
            $this->setFilterable($filterable);
        }
        if (null !== $returnable) {
            $this->setReturnable($returnable);
        }
        if (null !== $abbreviate) {
            $this->setAbbreviate($abbreviate);
        }
        if (null !== $rangeable) {
            $this->setRangeable($rangeable);
        }
        if (null !== $immutable) {
            $this->setImmutable($immutable);
        }
    }

    /**
     * @return string
     */
    public function getAttributeName(): string
    {
        return $this->attributeName;
    }

    /**
     * @return string
     */
    public function getDatatype(): string
    {
        return $this->datatype;
    }

    /**
     * @return string[]
     */
    public function getLabel(): array
    {
        return $this->label;
    }

    /**
     * Sets array of labels
     *
     * Object keys should be ISO 639-1 standard and values are strings.
     *  One key is expected to always be present, which is 'default'.
     *
     * @example ["default" => "Colour", "en-GB" => "Colour", "en-US" => "Color", "fi-FI" => "Väri"]
     *
     * @param array<string, string> $label
     *
     * @return void
     * @throws CouldNotUpdateException Where the attribute is marked as immutable
     */
    public function setLabel(array $label): void
    {
        $this->label = [];
        array_walk(
            $label,
            // Cannot use [$this, 'addLabel'] because then types are silently juggled to string
            function (mixed $label, mixed $key): void {
                $this->addLabel(
                    label: $label,
                    key: $key,
                );
            },
        );
    }

    /**
     * Adds a label to the existing label array
     *
     * @param string $label
     * @param string $key Should be ISO 639-1 standard
     *
     * @return void
     * @throws CouldNotUpdateException Where the attribute is marked as immutable
     */
    public function addLabel(string $label, string $key = 'default'): void
    {
        if ($this->isImmutable()) {
            throw new CouldNotUpdateException('Cannot update label property of immutable Attribute');
        }

        $this->label[$key] = $label;
    }

    /**
     * @return bool
     */
    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    /**
     * Enables / disables the searchable flag for this attribute
     *
     * @param bool $searchable
     *
     * @return void
     * @throws CouldNotUpdateException Where the attribute is marked as immutable
     */
    public function setSearchable(bool $searchable): void
    {
        if ($this->isImmutable()) {
            throw new CouldNotUpdateException('Cannot update searchable property of immutable Attribute');
        }

        $this->searchable = $searchable;
    }

    /**
     * @return bool
     */
    public function isFilterable(): bool
    {
        return $this->filterable;
    }

    /**
     * Enables / disables the filterable flag for this attribute
     *
     * @param bool $filterable
     *
     * @return void
     * @throws CouldNotUpdateException Where the attribute is marked as immutable
     */
    public function setFilterable(bool $filterable): void
    {
        if ($this->isImmutable()) {
            throw new CouldNotUpdateException('Cannot update filterable property of immutable Attribute');
        }

        $this->filterable = $filterable;
    }

    /**
     * @return bool
     */
    public function isReturnable(): bool
    {
        return $this->returnable;
    }

    /**
     * Enables / disables the returnable flag for this attribute
     *
     * @param bool $returnable
     *
     * @return void
     * @throws CouldNotUpdateException Where the attribute is marked as immutable
     */
    public function setReturnable(bool $returnable): void
    {
        if ($this->isImmutable()) {
            throw new CouldNotUpdateException('Cannot update returnable property of immutable Attribute');
        }

        $this->returnable = $returnable;
    }

    /**
     * @return bool
     */
    public function isAbbreviate(): bool
    {
        return $this->abbreviate;
    }

    /**
     * Enables / disables the abbreviate flag for this attribute
     *
     * @param bool $abbreviate
     *
     * @return void
     * @throws CouldNotUpdateException Where the attribute is marked as immutable
     */
    public function setAbbreviate(bool $abbreviate): void
    {
        if ($this->isImmutable()) {
            throw new CouldNotUpdateException('Cannot update abbreviate property of immutable Attribute');
        }

        $this->abbreviate = $abbreviate;
    }

    /**
     * @return bool
     */
    public function isRangeable(): bool
    {
        return $this->rangeable;
    }

    /**
     * Enables / disables the rangeable flag for this attribute
     *
     * @param bool $rangeable
     *
     * @return void
     * @throws CouldNotUpdateException Where the attribute is marked as immutable
     */
    public function setRangeable(bool $rangeable): void
    {
        if ($this->isImmutable()) {
            throw new CouldNotUpdateException('Cannot update rangeable property of immutable Attribute');
        }

        $this->rangeable = $rangeable;
    }

    /**
     * @return bool
     */
    public function isImmutable(): bool
    {
        return $this->immutable;
    }

    /**
     * Enables / disables the immutable flag for this attribute
     *
     * @note Enabling this flag will prevent modification to other properties
     * @note AttributesService::put() will reject any attributes with this flag enabled
     *
     * @see AttributesService::put()
     *
     * @param bool $immutable
     *
     * @return void
     */
    public function setImmutable(bool $immutable): void
    {
        $this->immutable = $immutable;
    }

    /**
     * @return array<string, string|array<string, string>|bool>
     */
    public function toArray(): array
    {
        return [
            self::FIELD_ATTRIBUTE_NAME => $this->getAttributeName(),
            self::FIELD_DATATYPE => $this->getDatatype(),
            self::FIELD_LABEL => $this->getLabel(),
            self::FIELD_SEARCHABLE => $this->isSearchable(),
            self::FIELD_FILTERABLE => $this->isFilterable(),
            self::FIELD_RETURNABLE => $this->isReturnable(),
            self::FIELD_ABBREVIATE => $this->isAbbreviate(),
            self::FIELD_RANGEABLE => $this->isRangeable(),
            self::FIELD_IMMUTABLE => $this->isImmutable(),
        ];
    }
}
