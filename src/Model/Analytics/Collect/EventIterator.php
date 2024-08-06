<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model\Analytics\Collect;

use Klevu\PhpSDK\Model\GenericIteratorTrait;
use Klevu\PhpSDK\Model\IteratorInterface;

/**
 * Iterator object containing only analytics event objects
 *
 * @see Event
 * @since 1.0.0
 * @property Event[] $data
 * @method Event[] toArray()
 * @method EventIterator filter(callable $callback, int $mode = 0)
 * @method EventIterator walk(callable $callback, mixed $arg = null)
 */
class EventIterator implements IteratorInterface
{
    use GenericIteratorTrait;

    /**
     * @param Event[] $data
     */
    public function __construct(array $data = [])
    {
        array_walk($data, [$this, 'addItem']);
    }

    /**
     * Add event to iterator
     *
     * @param Event $item
     *
     * @return void
     */
    public function addItem(Event $item): void
    {
        $this->data[] = $item;
    }

    /**
     * Returns the current event
     *
     * @link https://php.net/manual/en/iterator.current.php
     * @return Event|null
     */
    public function current(): ?Event
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
        return ($this->data[$this->key()] ?? null) instanceof Event;
    }
}
