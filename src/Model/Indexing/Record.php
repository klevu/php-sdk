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
// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
use Klevu\PhpSDK\Validator\Indexing\Record\ChannelsValidator;
// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
use Klevu\PhpSDK\Validator\Indexing\Record\GroupsValidator;

/**
 * Data model representation of a Klevu Indexing record, such as a product
 *
 * @link https://docs.klevu.com/indexing-apis/add-simple-products-in-your-catalog
 * @link https://docs.klevu.com/indexing-apis/api-schema-swaggeropenapi-specification
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
     * Key used to reference groups property when converting to/from array
     *
     * @see Record::toArray()
     * @see RecordFactory::create()
     * @var string
     */
    final public const FIELD_GROUPS = 'groups';
    /**
     * Key used to reference channels property when converting to/from array
     *
     * @see Record::toArray()
     * @see RecordFactory::create()
     * @var string
     */
    final public const FIELD_CHANNELS = 'channels';

    /**
     * @var string
     */
    private readonly string $id;
    /**
     * @var string
     */
    private readonly string $type;
    /**
     * @var array<string, mixed[]>|null
     */
    private ?array $relations = null;
    /**
     * @var array<string, mixed>
     */
    private array $attributes = [];
    /**
     * @var array<string|int, array<string, mixed>|mixed>|null
     */
    private ?array $groups = null;
    /**
     * @var array<string|int, array<string, mixed>|mixed>|null
     */
    private ?array $channels = null;

    /**
     * @param string $id
     * @param string $type
     * @param mixed[][]|null $relations
     * @param mixed[]|null $attributes
     * @param mixed[]|null $groups
     * @param mixed[]|null $channels
     */
    public function __construct(
        string $id,
        string $type,
        ?array $relations = null,
        ?array $attributes = null,
        ?array $groups = null,
        ?array $channels = null,
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->setRelations($relations);
        if (null !== $attributes) {
            $this->setAttributes($attributes);
        }
        $this->setGroups($groups);
        $this->setChannels($channels);
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
     * @link https://docs.klevu.com/indexing-apis/api-schema-swaggeropenapi-specification
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
     * @link https://docs.klevu.com/indexing-apis/api-schema-swaggeropenapi-specification
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
     * @return array<string|int, array<string, mixed>|mixed>|null
     */
    public function getGroups(): ?array
    {
        return $this->groups;
    }

    /**
     * Sets array of group name => values to be overridden
     *
     * @note Invalid data is accepted at this stage; validation is performed before sync using {@see GroupsValidator}
     *
     * @param array<string, array<string, mixed>|mixed>|null $groups
     *
     * @return void
     */
    public function setGroups(?array $groups): void
    {
        if (null === $groups) {
            $this->groups = null;

            return;
        }

        $this->groups = [];
        array_walk(
            $groups,
            function (mixed $groupData, mixed $groupName): void {
                $this->addGroup(
                    groupName: $groupName,
                    groupData: $groupData,
                );
            },
        );
    }

    /**
     * Adds to the existing group data to be updated in the index
     *
     * @note Invalid data is accepted at this stage; validation is performed before sync using {@see GroupsValidator}
     *
     * @param string|int $groupName Group name to be created / updated
     * @param array<string, mixed>|mixed $groupData Array of data to be assigned to this group
     *
     * @return void
     */
    public function addGroup(string|int $groupName, mixed $groupData): void
    {
        $this->groups[$groupName] = $groupData;
    }

    /**
     * @return array<string|int, array<string, mixed>|mixed>|null
     */
    public function getChannels(): ?array
    {
        return $this->channels;
    }

    /**
     * Sets array of channel name => values to be overridden
     *
     * @note Invalid data is accepted at this stage; validation is performed before sync using {@see ChannelsValidator}
     *
     * @param array<string, array<string, mixed>|mixed>|null $channels
     *
     * @return void
     */
    public function setChannels(?array $channels): void
    {
        if (null === $channels) {
            $this->channels = null;

            return;
        }

        $this->channels = [];
        array_walk(
            $channels,
            function (mixed $channelData, mixed $channelName): void {
                $this->addChannel(
                    channelName: $channelName,
                    channelData: $channelData,
                );
            },
        );
    }

    /**
     * Adds to the existing channel data to be updated in the index
     *
     * @note Invalid data is accepted at this stage; validation is performed before sync using {@see ChannelsValidator}
     *
     * @param string|int $channelName Channel name to be created / updated
     * @param array<string, mixed>|mixed $channelData Array of data to be assigned to this channel
     *
     * @return void
     */
    public function addChannel(string|int $channelName, mixed $channelData): void
    {
        $this->channels[$channelName] = $channelData;
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
            self::FIELD_GROUPS => $this->getGroups(),
            self::FIELD_CHANNELS => $this->getChannels(),
        ];
    }
}
