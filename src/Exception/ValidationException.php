<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Exception;

use Klevu\PhpSDK\Validator\ValidatorInterface;

/**
 * Base exception thrown when any Validator implementation fails
 *
 * All errors found during validation can be accessed through getErrors()
 *
 * @see ValidatorInterface
 * @since 1.0.0
 */
class ValidationException extends \LogicException
{
    /**
     * @var string[]
     */
    private array $errors;

    /**
     * @param string[] $errors
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        array $errors,
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);

        $this->errors = array_map('strval', $errors);
    }

    /**
     * Returns collection of unique errors encountered which caused validation to fail
     *
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
