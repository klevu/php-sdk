<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model\Indexing;

use Klevu\PhpSDK\Api\Model\Indexing\UpdateInterface;
use Klevu\PhpSDK\Model\GenericIteratorTrait;
use Klevu\PhpSDK\Model\IteratorInterface;

/**
 * Iterator object containing only indexing record objects
 *
 * @see RecordInterface
 * @since 1.0.0
 * @property UpdateInterface[] $data
 * @method UpdateInterface[] toArray()
 * @method UpdateInterface filter(callable $callback, int $mode = 0)
 * @method UpdateInterface walk(callable $callback, mixed $arg = null)
 */
class UpdateIterator implements IteratorInterface
{
    use GenericIteratorTrait;

    /**
     * @param UpdateInterface[] $data
     */
    public function __construct(array $data = [])
    {
        array_walk($data, [$this, 'addItem']);
    }

    /**
     * Add update item to iterator
     *
     * @param UpdateInterface $item
     *
     * @return void
     */
    public function addItem(UpdateInterface $item): void
    {
        $this->data[] = $item;
    }

    /**
     * Returns the current update item
     *
     * @link https://php.net/manual/en/iterator.current.php
     * @return UpdateInterface|null
     */
    public function current(): ?UpdateInterface
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
        return ($this->data[$this->key()] ?? null) instanceof UpdateInterface;
    }
}
