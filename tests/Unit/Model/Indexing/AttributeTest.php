<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Model\Indexing;

use Klevu\PhpSDK\Api\Model\Indexing\AttributeInterface;
use Klevu\PhpSDK\Exception\CouldNotUpdateException;
use Klevu\PhpSDK\Model\Indexing\Attribute;
use Klevu\PhpSDK\Model\Indexing\DataType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Attribute::class)]
class AttributeTest extends TestCase
{
    #[Test]
    public function testImplementsInterface(): void
    {
        $attribute = new Attribute(
            attributeName: 'test_attribute',
            datatype: DataType::STRING->value,
        );

        $this->assertInstanceOf(AttributeInterface::class, $attribute);
    }

    #[Test]
    public function testConstruct(): void
    {
        $attribute = new Attribute(
            attributeName: 'test_attribute',
            datatype: DataType::STRING->value,
        );

        $this->assertSame('test_attribute', $attribute->getAttributeName());
        $this->assertSame(DataType::STRING->value, $attribute->getDatatype());
        $this->assertSame([], $attribute->getLabel());
        $this->assertTrue($attribute->isSearchable());
        $this->assertTrue($attribute->isFilterable());
        $this->assertTrue($attribute->isReturnable());
        $this->assertFalse($attribute->isAbbreviate());
        $this->assertFalse($attribute->isRangeable());
        $this->assertFalse($attribute->isImmutable());
    }

    #[Test]
    public function testConstruct_Full(): void
    {
        $attribute = new Attribute(
            attributeName: 'test_attribute',
            datatype: DataType::STRING->value,
            label: [
                'default' => 'Test',
            ],
            searchable: false,
            filterable: false,
            returnable: false,
            abbreviate: true,
            rangeable: true,
            immutable: true,
        );

        $this->assertSame('test_attribute', $attribute->getAttributeName());
        $this->assertSame(DataType::STRING->value, $attribute->getDatatype());
        $this->assertSame(
            expected: [
                'default' => 'Test',
            ],
            actual: $attribute->getLabel(),
        );
        $this->assertFalse($attribute->isSearchable());
        $this->assertFalse($attribute->isFilterable());
        $this->assertFalse($attribute->isReturnable());
        $this->assertTrue($attribute->isAbbreviate());
        $this->assertTrue($attribute->isRangeable());
        $this->assertTrue($attribute->isImmutable());
    }

    #[Test]
    public function testGetSetAddLabel(): void
    {
        $attribute = new Attribute(
            attributeName: 'test_attribute',
            datatype: DataType::STRING->value,
        );

        $this->assertSame([], $attribute->getLabel());

        $attribute->setLabel(['default' => 'Test Attribute']);
        $this->assertSame(
            expected: [
                'default' => 'Test Attribute',
            ],
            actual: $attribute->getLabel(),
        );

        $attribute->addLabel(label: 'Bar', key: 'foo');
        $this->assertSame(
            expected: [
                'default' => 'Test Attribute',
                'foo' => 'Bar',
            ],
            actual: $attribute->getLabel(),
        );

        $attribute->addLabel(label: 'Baz', key: 'foo');
        $this->assertSame(
            expected: [
                'default' => 'Test Attribute',
                'foo' => 'Baz',
            ],
            actual: $attribute->getLabel(),
        );
    }

    #[Test]
    public function testGetSetAddLabel_Immutable(): void
    {
        $attribute = new Attribute(
            attributeName: 'test_attribute',
            datatype: DataType::STRING->value,
            immutable: true,
        );

        $this->assertSame([], $attribute->getLabel());

        $this->expectException(CouldNotUpdateException::class);
        $attribute->setLabel(['default' => 'Test Attribute']);
    }

    #[Test]
    public function testGetSetSearchable(): void
    {
        $attribute = new Attribute(
            attributeName: 'test_attribute',
            datatype: DataType::STRING->value,
        );

        $this->assertTrue($attribute->isSearchable());

        $attribute->setSearchable(false);
        $this->assertFalse($attribute->isSearchable());
    }

    #[Test]
    public function testGetSetSearchable_Immutable(): void
    {
        $attribute = new Attribute(
            attributeName: 'test_attribute',
            datatype: DataType::STRING->value,
            immutable: true,
        );

        $this->assertTrue($attribute->isSearchable());

        $this->expectException(CouldNotUpdateException::class);
        $attribute->setSearchable(false);
    }

    #[Test]
    public function testGetSetFilterable(): void
    {
        $attribute = new Attribute(
            attributeName: 'test_attribute',
            datatype: DataType::STRING->value,
        );

        $this->assertTrue($attribute->isFilterable());

        $attribute->setFilterable(false);
        $this->assertFalse($attribute->isFilterable());
    }

    #[Test]
    public function testGetSetFilterable_Immutable(): void
    {
        $attribute = new Attribute(
            attributeName: 'test_attribute',
            datatype: DataType::STRING->value,
            immutable: true,
        );

        $this->assertTrue($attribute->isFilterable());

        $this->expectException(CouldNotUpdateException::class);
        $attribute->setFilterable(false);
    }

    #[Test]
    public function testGetSetReturnable(): void
    {
        $attribute = new Attribute(
            attributeName: 'test_attribute',
            datatype: DataType::STRING->value,
        );

        $this->assertTrue($attribute->isReturnable());

        $attribute->setReturnable(false);
        $this->assertFalse($attribute->isReturnable());
    }

    #[Test]
    public function testGetSetReturnable_Immutable(): void
    {
        $attribute = new Attribute(
            attributeName: 'test_attribute',
            datatype: DataType::STRING->value,
            immutable: true,
        );

        $this->assertTrue($attribute->isReturnable());

        $this->expectException(CouldNotUpdateException::class);
        $attribute->setReturnable(false);
    }

    #[Test]
    public function testGetSetAbbreviate(): void
    {
        $attribute = new Attribute(
            attributeName: 'test_attribute',
            datatype: DataType::STRING->value,
        );

        $this->assertFalse($attribute->isAbbreviate());

        $attribute->setAbbreviate(true);
        $this->assertTrue($attribute->isAbbreviate());
    }

    #[Test]
    public function testGetSetAbbreviate_Immutable(): void
    {
        $attribute = new Attribute(
            attributeName: 'test_attribute',
            datatype: DataType::STRING->value,
            immutable: true,
        );

        $this->assertFalse($attribute->isAbbreviate());

        $this->expectException(CouldNotUpdateException::class);
        $attribute->setAbbreviate(true);
    }

    #[Test]
    public function testGetSetRangeable(): void
    {
        $attribute = new Attribute(
            attributeName: 'test_attribute',
            datatype: DataType::STRING->value,
        );

        $this->assertFalse($attribute->isRangeable());

        $attribute->setRangeable(true);
        $this->assertTrue($attribute->isRangeable());
    }

    #[Test]
    public function testGetSetRangeable_Immutable(): void
    {
        $attribute = new Attribute(
            attributeName: 'test_attribute',
            datatype: DataType::STRING->value,
            immutable: true,
        );

        $this->assertFalse($attribute->isRangeable());

        $this->expectException(CouldNotUpdateException::class);
        $attribute->setRangeable(true);
    }

    public function testGetSetImmutable(): void
    {
        $attribute = new Attribute(
            attributeName: 'test_attribute',
            datatype: DataType::STRING->value,
        );

        $this->assertFalse($attribute->isImmutable());

        $attribute->setImmutable(true);
        $this->assertTrue($attribute->isImmutable());
    }

    #[Test]
    public function testGetSetImmutable_Immutable(): void
    {
        $attribute = new Attribute(
            attributeName: 'test_attribute',
            datatype: DataType::STRING->value,
            immutable: true,
        );

        $this->assertTrue($attribute->isImmutable());

        $attribute->setImmutable(false);
        $this->assertFalse($attribute->isImmutable());
    }

    #[Test]
    public function testSetData_AfterImmutable(): void
    {
        $attribute = new Attribute(
            attributeName: 'test_attribute',
            datatype: DataType::STRING->value,
            immutable: true,
        );

        $this->assertTrue($attribute->isImmutable());

        $attribute->setImmutable(false);
        $this->assertFalse($attribute->isImmutable());

        // We are not expecting any exceptions
        $attribute->setLabel(['default' => 'Test Attribute']);
        $this->assertSame(
            expected: [
                'default' => 'Test Attribute',
            ],
            actual: $attribute->getLabel(),
        );

        $attribute->setSearchable(false);
        $this->assertFalse($attribute->isSearchable());

        $attribute->setFilterable(false);
        $this->assertFalse($attribute->isFilterable());

        $attribute->setReturnable(false);
        $this->assertFalse($attribute->isReturnable());

        $attribute->setAbbreviate(true);
        $this->assertTrue($attribute->isAbbreviate());

        $attribute->setRangeable(true);
        $this->assertTrue($attribute->isRangeable());
    }

    #[Test]
    public function testToArray(): void
    {
        $attribute = new Attribute(
            attributeName: 'test_attribute',
            datatype: DataType::STRING->value,
        );
        $attribute->setLabel([
            'default' => 'Test Attribute',
            'foo' => 'Bar',
        ]);
        $attribute->setSearchable(false);
        $attribute->setFilterable(false);
        $attribute->setReturnable(false);
        $attribute->setAbbreviate(true);
        $attribute->setRangeable(true);
        $attribute->setImmutable(true);

        $this->assertSame(
            expected: [
                Attribute::FIELD_ATTRIBUTE_NAME => 'test_attribute',
                Attribute::FIELD_DATATYPE => DataType::STRING->value,
                Attribute::FIELD_LABEL => [
                    'default' => 'Test Attribute',
                    'foo' => 'Bar',
                ],
                Attribute::FIELD_SEARCHABLE => false,
                Attribute::FIELD_FILTERABLE => false,
                Attribute::FIELD_RETURNABLE => false,
                Attribute::FIELD_ABBREVIATE => true,
                Attribute::FIELD_RANGEABLE => true,
                Attribute::FIELD_IMMUTABLE => true,
            ],
            actual: $attribute->toArray(),
        );
    }
}
