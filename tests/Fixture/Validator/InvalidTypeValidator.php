<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Fixture\Validator;

use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;

class InvalidTypeValidator extends FailValidator
{
    /**
     * @param string[] $errors
     * @param string|null $message
     */
    public function __construct(
        array $errors,
        ?string $message = null,
    ) {
        $exception = (null === $message)
            ? new InvalidTypeValidationException(
                errors: $errors,
            )
            : new InvalidTypeValidationException(
                errors: $errors,
                message: $message,
            );

        parent::__construct(
            exception: $exception,
        );
    }
}
