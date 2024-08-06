<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model;

/**
 * Trait to provide common iterator methods and properties
 *
 * @since 1.0.0
 */
trait GenericIteratorTrait
{
    /**
     * @var int
     */
    private int $position = 0;
    /**
     * @var object[]
     */
    private array $data = [];

    /**
     * Moves the internal pointer forward to next element
     *
     * @link https://php.net/manual/en/iterator.next.php
     * @return void
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * Returns the key of the current element
     *
     * @link https://php.net/manual/en/iterator.key.php
     * @return int|null
     */
    public function key(): ?int
    {
        return array_key_exists($this->position, $this->data)
            ? $this->position
            : null;
    }

    /**
     * Rewinds the internal pointer to the first element
     *
     * @link https://php.net/manual/en/iterator.rewind.php
     * @return void
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * Returns a count of the data elements within this object
     *
     * @link https://php.net/manual/en/countable.count.php
     * @return int
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * @return object[]
     * @ignore See IteratorInterface::toArray()
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * @param callable $callback
     * @param int $mode
     *
     * @return IteratorInterface
     * @ignore See IteratorInterface::filter()
     */
    public function filter(callable $callback, int $mode = 0): IteratorInterface
    {
        return new self(
            array_filter($this->toArray(), $callback, $mode),
        );
    }

    /**
     * @param callable $callback
     * @param mixed|null $arg
     *
     * @return IteratorInterface
     * @ignore See IteratorInterface::walk()
     */
    public function walk(callable $callback, mixed $arg = null): IteratorInterface
    {
        $data = $this->toArray();
        array_walk($data, $callback, $arg);

        return new self($data);
    }
}
