<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Exception\Validation;

use Klevu\PhpSDK\Exception\ValidationException;

/**
 * Specific validation exception thrown when the data sent to test is of an incorrect type, meaning no contextual
 *  checks can be performed
 *
 * @since 1.0.0
 */
class InvalidTypeValidationException extends ValidationException
{
    /**
     * @param string[] $errors
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        array $errors,
        string $message = 'Invalid data type received',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($errors, $message, $code, $previous);
    }
}
