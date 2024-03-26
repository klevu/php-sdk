<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model;

/**
 * Extended iterator interface for klevu/php-sdk
 *
 * @extends \Iterator<int|string, mixed>
 * @link https://www.php.net/manual/en/class.iterator.php
 * @link https://www.php.net/manual/en/class.countable.php
 * @since 1.0.0
 */
interface IteratorInterface extends \Iterator, \Countable
{
    /**
     * Converts and returns class' data to array type
     *
     * Implemented for functions and methods which do not support iterable types and explicitly require arrays
     *
     * @return object[]
     */
    public function toArray(): array;

    /**
     * Filters this class' data using the passed callback function
     *
     * @note This method creates and returns a copy of self, leaving $this untouched
     *
     * @link https://www.php.net/array_filter
     *
     * @param int $mode
     * @param callable $callback
     *
     * @return IteratorInterface Copy of self containing filtered data
     */
    public function filter(callable $callback, int $mode = 0): IteratorInterface;

    /**
     * Applies callback function to each member of this class' data
     *
     * @note Unlike array_walk, this method creates and returns a copy of self, leaving $this untouched
     *      as opposed to applying by reference
     *
     * @param callable $callback
     *
     * @return IteratorInterface Copy of self containing modified data
     */
    public function walk(callable $callback): IteratorInterface;
}
