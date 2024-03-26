<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model\Indexing;

use Klevu\PhpSDK\Api\Model\Indexing\AttributeInterface;
use Klevu\PhpSDK\Model\GenericIteratorTrait;
use Klevu\PhpSDK\Model\IteratorInterface;

/**
 * Iterator object containing only indexing attribute objects
 *
 * @see AttributeInterface
 * @since 1.0.0
 * @property AttributeInterface[] $data
 * @method AttributeInterface[] toArray()
 * @method AttributeIterator filter(callable $callback, int $mode = 0)
 * @method AttributeIterator walk(callable $callback, mixed $arg = null)
 */
class AttributeIterator implements IteratorInterface
{
    use GenericIteratorTrait;

    /**
     * @param AttributeInterface[] $data
     */
    public function __construct(array $data = [])
    {
        array_walk($data, [$this, 'addItem']);
    }

    /**
     * Add attribute to iterator
     *
     * @param AttributeInterface $item
     *
     * @return void
     */
    public function addItem(AttributeInterface $item): void
    {
        $this->data[] = $item;
    }

    /**
     * Returns the current attribute
     *
     * @link https://php.net/manual/en/iterator.current.php
     * @return AttributeInterface
     */
    public function current(): AttributeInterface
    {
        return $this->data[$this->key()];
    }

    /**
     * Checks whether the current internal pointer position is valid
     *
     * @link https://php.net/manual/en/iterator.valid.php
     * @return bool
     */
    public function valid(): bool
    {
        return ($this->data[$this->key()] ?? null) instanceof AttributeInterface;
    }
}
