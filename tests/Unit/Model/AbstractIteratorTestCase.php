<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Model;

use Klevu\PhpSDK\Model\IteratorInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

abstract class AbstractIteratorTestCase extends TestCase
{
    /**
     * @var string
     */
    protected string $iteratorFqcn = '';
    /**
     * @var string
     */
    protected string $itemFqcn = \stdClass::class;

    /**
     * @return mixed[][]
     */
    abstract public static function dataProvider_valid(): array;

    /**
     * @return mixed[][]
     */
    abstract public static function dataProvider_invalid(): array;

    /**
     * @return mixed[][]
     */
    abstract public static function dataProvider_filter(): array;

    /**
     * @return mixed[][]
     */
    abstract public static function dataProvider_walk(): array;

    /**
     * @param mixed[] $data
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_valid')]
    public function testInitWithData_Valid(array $data): void
    {
        /** @var IteratorInterface $iterator */
        $iterator = new $this->iteratorFqcn($data);
        $this->assertCount(count($data), $iterator);
        foreach ($iterator as $key => $item) {
            $this->assertSame($data[$key], $item, sprintf('Item #%d', $key));
        }
    }

    /**
     * @param mixed[] $data
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_valid')]
    public function testAddItem_Valid(array $data): void
    {
        /** @var IteratorInterface $iterator */
        $iterator = new $this->iteratorFqcn();
        if (!method_exists($iterator, 'addItem')) {
            $this->fail('addItem method not implemented');
        }

        $this->assertCount(0, $iterator);

        foreach ($data as $item) {
            $iterator->addItem($item);
        }

        $this->assertCount(count($data), $iterator);
        foreach ($iterator as $key => $item) {
            $this->assertSame($data[$key], $item, sprintf('Item #%d', $key));
        }
    }

    /**
     * @param mixed[] $data
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_invalid')]
    public function testInitWithData_Invalid(array $data): void
    {
        $this->expectException(\TypeError::class);

        // phpcs:ignore SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable
        $iterator = new $this->iteratorFqcn($data);
    }

    /**
     * @param mixed[] $data
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_invalid')]
    public function testAddItem_Invalid(array $data): void
    {
        /** @var IteratorInterface $iterator */
        $iterator = new $this->iteratorFqcn();
        if (!method_exists($iterator, 'addItem')) {
            $this->fail('addItem method not implemented');
        }

        $this->assertCount(0, $iterator);

        $this->expectException(\TypeError::class);
        foreach ($data as $item) {
            $iterator->addItem($item);
        }
    }

    /**
     * @param mixed[] $data
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_valid')]
    public function testArrayFeatures(array $data): void
    {
        /** @var IteratorInterface $iterator */
        $iterator = new $this->iteratorFqcn($data);

        $this->assertTrue($iterator->valid(), 'Valid at start');

        $this->assertSame(key($data), $iterator->key(), 'Key at start');
        $this->assertSame(current($data), $iterator->current(), 'Current at start');

        next($data);
        $iterator->next();
        $this->assertSame(key($data), $iterator->key(), 'Key after next');
        $this->assertSame(
            expected: (null === $iterator->key()) // @phpstan-ignore-line
                ? null // We use null, not false, on out of bounds
                : current($data),
            actual: $iterator->current(),
            message: 'Current after next',
        );

        reset($data);
        $iterator->rewind();
        $this->assertSame(key($data), $iterator->key(), 'Key after rewind');
        $this->assertsame(current($data), $iterator->current(), 'Current after rewind');
    }

    /**
     * @param mixed[] $data
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_valid')]
    public function testCount(array $data): void
    {
        /** @var IteratorInterface $iterator */
        $iterator = new $this->iteratorFqcn($data);
        $this->assertSame(count($data), $iterator->count());
    }

    /**
     * @param mixed[] $data
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_valid')]
    public function testToArray(array $data): void
    {
        /** @var IteratorInterface $iterator */
        $iterator = new $this->iteratorFqcn($data);

        $this->assertSame($data, $iterator->toArray());
    }

    /**
     * @param mixed[] $data
     * @param callable $callback
     * @param mixed[] $expectedResultData
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_filter')]
    public function testFilter(
        array $data,
        callable $callback,
        array $expectedResultData,
    ): void {
        /** @var IteratorInterface $iterator */
        $iterator = new $this->iteratorFqcn($data);

        $result = $iterator->filter($callback);
        $this->assertInstanceOf(IteratorInterface::class, $result);

        $this->assertCount(count($expectedResultData), $result);
        foreach ($result as $key => $item) {
            $this->assertEquals($expectedResultData[$key], $item, sprintf('Item #%d', $key));
        }
    }

    /**
     * @param mixed[] $data
     * @param callable $callback
     * @param mixed[] $expectedResultData
     *
     * @return void
     */
    #[Test]
    #[DataProvider('dataProvider_walk')]
    public function testWalk(
        array $data,
        callable $callback,
        array $expectedResultData,
    ): void {
        /** @var IteratorInterface $iterator */
        $iterator = new $this->iteratorFqcn($data);

        $result = $iterator->walk($callback);
        $this->assertInstanceOf(IteratorInterface::class, $result);

        $this->assertCount(count($expectedResultData), $result);
        foreach ($result as $key => $item) {
            $this->assertEquals($expectedResultData[$key], $item, sprintf('Item #%d', $key));
        }
    }
}
