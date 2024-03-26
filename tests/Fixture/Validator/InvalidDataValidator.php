<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Fixture\Validator;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;

class InvalidDataValidator extends FailValidator
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
            ? new InvalidDataValidationException(
                errors: $errors,
            )
            : new InvalidDataValidationException(
                errors: $errors,
                message: $message,
            );

        parent::__construct(
            exception: $exception,
        );
    }
}
