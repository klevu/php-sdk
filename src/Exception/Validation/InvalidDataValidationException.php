<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Exception\Validation;

use Klevu\PhpSDK\Exception\ValidationException;

/**
 * Specific validation exception thrown when the data being checked does not match what is expected
 *
 * Example failure conditions include missing keys, empty values, incorrect data types within a larger structure
 *
 * @since 1.0.0
 */
class InvalidDataValidationException extends ValidationException
{
    /**
     * @param string[] $errors
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        array $errors,
        string $message = 'Data is not valid',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($errors, $message, $code, $previous);
    }
}
