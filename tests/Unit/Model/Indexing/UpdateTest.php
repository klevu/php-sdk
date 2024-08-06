<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Model\Indexing;

use Klevu\PhpSDK\Api\Model\Indexing\UpdateInterface;
use Klevu\PhpSDK\Model\Indexing\Update;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(Update::class)]
class UpdateTest extends TestCase
{
    #[Test]
    public function testImplementsInterface(): void
    {
        $update = new Update();

        $this->assertInstanceOf(
            expected: UpdateInterface::class,
            actual: $update,
        );
    }

    #[Test]
    #[TestWith([''])]
    #[TestWith(['add'])]
    #[TestWith(['remove'])]
    #[TestWith(['replace'])]
    #[TestWith(['-gf56gfd8*hgfd15'])]
    public function testGetSetRecordId(
        string $recordId,
    ): void {
        $update = new Update();

        $this->assertSame('', $update->getRecordId());

        $update->setRecordId($recordId);
        $this->assertSame($recordId, $update->getRecordId());
    }

    #[Test]
    #[TestWith([''])]
    #[TestWith(['add'])]
    #[TestWith(['remove'])]
    #[TestWith(['replace'])]
    #[TestWith(['-gf56gfd8*hgfd15'])]
    public function testGetSetOp(
        string $op,
    ): void {
        $update = new Update();

        $this->assertSame('', $update->getOp());

        $update->setOp($op);
        $this->assertSame($op, $update->getOp());
    }

    #[Test]
    #[TestWith([''])]
    #[TestWith([null])]
    #[TestWith(['/'])]
    #[TestWith(['/foo.bar/@baz-123'])]
    #[TestWith(['12345'])]
    public function testGetSetPath(
        ?string $path,
    ): void {
        $update = new Update();

        $this->assertSame(null, $update->getPath());

        $update->setPath($path);
        $this->assertSame($path, $update->getPath());
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testGetSetValue(): array
    {
        return [
            [null],
            [''],
            ['foo'],
            [42],
            [3.14],
            [true],
            [
                ['foo' => 'bar'],
            ],
            [
                (object)['foo' => 'bar'],
            ],
        ];
    }

    #[Test]
    #[DataProvider('dataProvider_testGetSetValue')]
    public function testGetSetValue(
        mixed $value,
    ): void {
        $update = new Update();

        $this->assertSame(null, $update->getValue());

        $update->setValue($value);
        $this->assertSame($value, $update->getValue());
    }

    #[Test]
    public function testToArray(): void
    {
        $update = new Update();

        $this->assertSame(
            expected: [
                'record_id' => '',
                'op' => '',
                'path' => null,
                'value' => null,
            ],
            actual: $update->toArray(),
        );

        $update->setRecordId('PRODUCT001');
        $update->setOp('replace');
        $update->setPath('/foo/1/bar');
        $update->setValue([
            'foo' => 'bar',
        ]);

        $this->assertSame(
            expected: [
                'record_id' => 'PRODUCT001',
                'op' => 'replace',
                'path' => '/foo/1/bar',
                'value' => [
                    'foo' => 'bar',
                ],
            ],
            actual: $update->toArray(),
        );
    }
}
