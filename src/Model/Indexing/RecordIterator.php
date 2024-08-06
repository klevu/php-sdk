<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model\Indexing;

use Klevu\PhpSDK\Api\Model\Indexing\RecordInterface;
use Klevu\PhpSDK\Model\GenericIteratorTrait;
use Klevu\PhpSDK\Model\IteratorInterface;

/**
 * Iterator object containing only indexing record objects
 *
 * @see RecordInterface
 * @since 1.0.0
 * @property RecordInterface[] $data
 * @method RecordInterface[] toArray()
 * @method RecordIterator filter(callable $callback, int $mode = 0)
 * @method RecordIterator walk(callable $callback, mixed $arg = null)
 */
class RecordIterator implements IteratorInterface
{
    use GenericIteratorTrait;

    /**
     * @param RecordInterface[] $data
     */
    public function __construct(array $data = [])
    {
        array_walk($data, [$this, 'addItem']);
    }

    /**
     * Add record to iterator
     *
     * @param RecordInterface $item
     *
     * @return void
     */
    public function addItem(RecordInterface $item): void
    {
        $this->data[] = $item;
    }

    /**
     * Returns the current record
     *
     * @link https://php.net/manual/en/iterator.current.php
     * @return RecordInterface|null
     */
    public function current(): ?RecordInterface
    {
        return $this->data[$this->key()] ?? null;
    }

    /**
     * Checks whether the current internal pointer position is valid
     *
     * @link https://php.net/manual/en/iterator.valid.php
     * @return bool
     */
    public function valid(): bool
    {
        return ($this->data[$this->key()] ?? null) instanceof RecordInterface;
    }
}
