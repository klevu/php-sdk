<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Validator;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;

/**
 * Service contract for classes used for validation
 *
 * @since 1.0.0
 */
interface ValidatorInterface
{
    /**
     * Performs validation against provided data. Returns silently if data is valid, or throws ValidationException
     *      if any issues are encountered
     *
     * @param mixed $data
     *
     * @return void
     * @throws ValidationException All exceptions thrown must extend the base ValidationException class
     * @throws InvalidTypeValidationException Where provided data is not of the expected type
     *      (eg, passing an array where a string is expected)
     * @throws InvalidDataValidationException Where data is of the correct type, but does not
     *      contain valid content
     */
    public function execute(mixed $data): void;
}
