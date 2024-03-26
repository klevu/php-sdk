<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model\Indexing;

// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
use Klevu\PhpSDK\Api\Model\Indexing\AttributeInterface;
use Klevu\PhpSDK\Api\Model\Indexing\RecordInterface;
use Klevu\PhpSDK\Service\Indexing\BatchService;

/**
 * Data model representation of a Klevu Indexing record, such as a product
 *
 * @link https://docs.klevu.com/indexing-apis/add-simple-products-in-your-catalog
 * @link https://docs.klevu.com/indexing-apis/api-definition
 * @see BatchService
 * @since 1.0.0
 */
class Record implements RecordInterface
{
    /**
     * Key used to reference id property when converting to/from array
     *
     * @see Record::toArray()
     * @see RecordFactory::create()
     * @var string
     */
    final public const FIELD_ID = 'id';
    /**
     * Key used to reference id property when converting to/from array
     *
     * @see Record::toArray()
     * @see RecordFactory::create()
     * @var string
     */
    final public const FIELD_TYPE = 'type';
    /**
     * Key used to reference relations property when converting to/from array
     *
     * @see Record::toArray()
     * @see RecordFactory::create()
     * @var string
     */
    final public const FIELD_RELATIONS = 'relations';
    /**
     * Key used to reference attributes property when converting to/from array
     *
     * @see Record::toArray()
     * @see RecordFactory::create()
     * @var string
     */
    final public const FIELD_ATTRIBUTES = 'attributes';
    /**
     * Key used to reference display property when converting to/from array
     *
     * @see Record::toArray()
     * @see RecordFactory::create()
     * @var string
     */
    final public const FIELD_DISPLAY = 'display';

    /**
     * @var string
     */
    private readonly string $id;
    /**
     * @var string
     */
    private readonly string $type;
    /**
     * @var mixed[][]|null
     */
    private ?array $relations = null;
    /**
     * @var mixed[]
     */
    private array $attributes = [];
    /**
     * @var mixed[]|null
     */
    private ?array $display = null;

    /**
     * @param string $id
     * @param string $type
     * @param mixed[][]|null $relations
     * @param mixed[]|null $attributes
     * @param mixed[]|null $display
     */
    public function __construct(
        string $id,
        string $type,
        ?array $relations = null,
        ?array $attributes = null,
        ?array $display = null,
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->setRelations($relations);
        if (null !== $attributes) {
            $this->setAttributes($attributes);
        }
        $this->setDisplay($display);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array<string, mixed[]>|null
     */
    public function getRelations(): ?array
    {
        return $this->relations;
    }

    /**
     * Sets array of relations
     *
     * Object key should be one of "categories" or "parentProduct", with corresponding data
     *  for value, as found in API documentation
     *
     * @link https://docs.klevu.com/indexing-apis/api-definition
     *
     * @param array<string, mixed[]>|null $relations
     *
     * @return void
     */
    public function setRelations(?array $relations): void
    {
        if (null === $relations) {
            $this->relations = null;

            return;
        }

        $this->relations = [];
        array_walk(
            $relations,
            // Cannot use [$this, 'addRelation'] because then types are silently juggled to string
            function (mixed $relation, mixed $key): void {
                $this->addRelation(
                    key: $key,
                    relation: $relation,
                );
            },
        );
    }

    /**
     * Adds a relation to the existing relations array
     *
     * @link https://docs.klevu.com/indexing-apis/api-definition
     *
     * @param mixed[] $relation See API documentation for required fields / format
     * @param string $key Should be one of "categories", "parentProduct"
     *
     * @return void
     */
    public function addRelation(string $key, array $relation): void
    {
        $this->relations[$key] = $relation;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Sets array of attribute name => value pairs to be updated in index for searching
     *   and faceting functionality
     *
     * Array key should correspond to an existing {@see AttributeInterface::getAttributeName()} value, with a
     *  value of the appropriate type based on the attribute definition
     *
     * @param array<string, mixed> $attributes
     *
     * @return void
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = [];
        array_walk(
            $attributes,
            function (mixed $value, mixed $attributeName): void {
                $this->addAttribute(
                    attributeName: $attributeName,
                    value: $value,
                );
            },
        );
    }

    /**
     * Adds to the existing attribute data to be indexed
     *
     * @param string $attributeName Should correspond to an existing attribute's attributeName value
     * @param mixed $value Should be of appropriate type based on the attribute definition
     *
     * @return void
     */
    public function addAttribute(string $attributeName, mixed $value): void
    {
        $this->attributes[$attributeName] = $value;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDisplay(): ?array
    {
        return $this->display;
    }

    /**
     * Sets array of attribute name => value pairs to be updated in index for return with search requests
     *
     *  Array key should correspond to an existing {@see AttributeInterface::getAttributeName()} value, with a
     *   value of the appropriate type based on the attribute definition
     *
     * @param array<string, mixed>|null $display
     *
     * @return void
     */
    public function setDisplay(?array $display): void
    {
        if (null === $display) {
            $this->display = null;

            return;
        }

        $this->display = [];
        array_walk(
            $display,
            function (mixed $value, mixed $attributeCode): void {
                $this->addDisplay(
                    attributeName: $attributeCode,
                    value: $value,
                );
            },
        );
    }

    /**
     * Adds to the existing attribute data to be updated in index for return with search requests
     *
     * @param string $attributeName Should correspond to an existing attribute's attributeName value
     * @param mixed $value Should be of appropriate type based on the attribute definition
     *
     * @return void
     */
    public function addDisplay(string $attributeName, mixed $value): void
    {
        $this->display[$attributeName] = $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            self::FIELD_ID => $this->getId(),
            self::FIELD_TYPE => $this->getType(),
            self::FIELD_RELATIONS => $this->getRelations(),
            self::FIELD_ATTRIBUTES => $this->getAttributes(),
            self::FIELD_DISPLAY => $this->getDisplay(),
        ];
    }
}
