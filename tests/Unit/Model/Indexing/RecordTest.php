<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Model\Indexing;

use Klevu\PhpSDK\Api\Model\Indexing\RecordInterface;
use Klevu\PhpSDK\Model\Indexing\Record;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Record::class)]
class RecordTest extends TestCase
{
    #[Test]
    public function testImplementsInterface(): void
    {
        $record = new Record(
            id: '123-456',
            type: 'KLEVU_PRODUCT',
        );

        $this->assertInstanceOf(RecordInterface::class, $record);
    }

    #[Test]
    public function testConstruct(): void
    {
        $record = new Record(
            id: '123-456',
            type: 'KLEVU_PRODUCT',
        );

        $this->assertSame('123-456', $record->getId());
        $this->assertSame('KLEVU_PRODUCT', $record->getType());
        $this->assertSame(null, $record->getRelations());
        $this->assertSame([], $record->getAttributes());
        $this->assertSame(null, $record->getChannels());
    }

    #[Test]
    public function testConstruct_Full(): void
    {
        $record = new Record(
            id: '123-456',
            type: 'KLEVU_PRODUCT',
            relations: [
                'categories' => [
                    'type' => 'KLEVU_CATEGORY',
                    'values' => [
                        'foo',
                    ],
                ],
            ],
            attributes: [
                'name' => [
                    'default' => 'Foo',
                ],
            ],
            channels: [
                'default' => [
                    'foo' => 'bar',
                ],
            ],
        );

        $this->assertSame('123-456', $record->getId());
        $this->assertSame('KLEVU_PRODUCT', $record->getType());
        $this->assertSame(
            expected: [
                'categories' => [
                    'type' => 'KLEVU_CATEGORY',
                    'values' => [
                        'foo',
                    ],
                ],
            ],
            actual: $record->getRelations(),
        );
        $this->assertSame(
            expected: [
                'name' => [
                    'default' => 'Foo',
                ],
            ],
            actual: $record->getAttributes(),
        );
        $this->assertSame(
            expected: [
                'default' => [
                    'foo' => 'bar',
                ],
            ],
            actual: $record->getChannels(),
        );
    }

    #[Test]
    public function testGetSetAddRelations(): void
    {
        $record = new Record(
            id: '123-456',
            type: 'KLEVU_PRODUCT',
        );

        $this->assertSame(null, $record->getRelations());

        $record->addRelation(
            key: 'categories',
            relation: [
                'type' => 'KLEVU_CATEGORY',
                'values' => [
                    'foo',
                ],
            ],
        );
        $this->assertSame(
            expected: [
                'categories' => [
                    'type' => 'KLEVU_CATEGORY',
                    'values' => [
                        'foo',
                    ],
                ],
            ],
            actual: $record->getRelations(),
        );

        $record->setRelations([
            'parentProduct' => [
                'type' => 'KLEVU_PRODUCT',
                'values' => [
                    'bar',
                ],
            ],
        ]);
        $this->assertSame(
            expected: [
                'parentProduct' => [
                    'type' => 'KLEVU_PRODUCT',
                    'values' => [
                        'bar',
                    ],
                ],
            ],
            actual: $record->getRelations(),
        );
    }

    #[Test]
    public function testGetSetAddAttributes(): void
    {
        $record = new Record(
            id: '123-456',
            type: 'KLEVU_PRODUCT',
        );

        $this->assertSame([], $record->getAttributes());

        $record->addAttribute(
            attributeName: 'foo',
            value: 'bar',
        );
        $this->assertSame(
            expected: [
                'foo' => 'bar',
            ],
            actual: $record->getAttributes(),
        );

        $record->setAttributes([
            'wom' => [
                'default' => 'bat',
                'additionalProp1' => 'baz',
            ],
        ]);
        $this->assertSame(
            expected: [
                'wom' => [
                    'default' => 'bat',
                    'additionalProp1' => 'baz',
                ],
            ],
            actual: $record->getAttributes(),
        );
    }

    #[Test]
    public function testGetSetAddGroups(): void
    {
        $record = new Record(
            id: '123-456',
            type: 'KLEVU_PRODUCT',
        );

        $this->assertSame(null, $record->getGroups());

        $record->addGroup(
            groupName: 'foo',
            groupData: [
                'attributes' => [
                    'bar' => 'baz',
                ],
            ],
        );
        $this->assertSame(
            expected: [
                'foo' => [
                    'attributes' => [
                        'bar' => 'baz',
                    ],
                ],
            ],
            actual: $record->getGroups(),
        );

        $record->setGroups([
            'wom' => [
                'attributes' => [
                    'foo' => [],
                ],
            ],
        ]);
        $this->assertSame(
            expected: [
                'wom' => [
                    'attributes' => [
                        'foo' => [],
                    ],
                ],
            ],
            actual: $record->getGroups(),
        );
    }

    #[Test]
    public function testGetSetAddChannels(): void
    {
        $record = new Record(
            id: '123-456',
            type: 'KLEVU_PRODUCT',
        );

        $this->assertSame(null, $record->getChannels());

        $record->addChannel(
            channelName: 'foo',
            channelData: [
                'attributes' => [
                    'bar' => 'baz',
                ],
            ],
        );
        $this->assertSame(
            expected: [
                'foo' => [
                    'attributes' => [
                        'bar' => 'baz',
                    ],
                ],
            ],
            actual: $record->getChannels(),
        );

        $record->setChannels([
            'wom' => [
                'groups' => [
                    'foo' => [],
                ],
            ],
        ]);
        $this->assertSame(
            expected: [
                'wom' => [
                    'groups' => [
                        'foo' => [],
                    ],
                ],
            ],
            actual: $record->getChannels(),
        );
    }

    #[Test]
    public function testToArray(): void
    {
        $record = new Record(
            id: '123-456',
            type: 'KLEVU_PRODUCT',
            relations: [
                'categories' => [
                    'type' => 'KLEVU_CATEGORY',
                    'values' => [
                        'foo',
                    ],
                ],
            ],
            attributes: [
                'name' => [
                    'default' => 'Foo',
                ],
            ],
            groups: [
                'fr_FR' => [
                    'attributes' => [
                        'wom' => 'bat',
                    ],
                ],
            ],
            channels: [
                'default' => [
                    'attributes' => [
                        'foo' => 'bar',
                    ],
                    'groups' => [
                        'fr_FR' => [
                            'attributes' => [
                                'wom' => [
                                    'default' => 'bat',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        );

        $this->assertSame(
            expected: [
                'id' => '123-456',
                'type' => 'KLEVU_PRODUCT',
                'relations' => [
                    'categories' => [
                        'type' => 'KLEVU_CATEGORY',
                        'values' => [
                            'foo',
                        ],
                    ],
                ],
                'attributes' => [
                    'name' => [
                        'default' => 'Foo',
                    ],
                ],
                'groups' => [
                    'fr_FR' => [
                        'attributes' => [
                            'wom' => 'bat',
                        ],
                    ],
                ],
                'channels' => [
                    'default' => [
                        'attributes' => [
                            'foo' => 'bar',
                        ],
                        'groups' => [
                            'fr_FR' => [
                                'attributes' => [
                                    'wom' => [
                                        'default' => 'bat',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            actual: $record->toArray(),
        );
    }
}
