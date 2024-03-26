<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Validator\Indexing\Record;

use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Validator\ValidatorInterface;

/**
 * Validator to test that a Record's "relations" value is valid for indexing
 *
 * @link https://docs.klevu.com/indexing-apis/api-definition
 * @since 1.0.0
 */
class RelationsValidator implements ValidatorInterface
{
    /**
     * Rudimentary validation that the passed data is a valid "relations" value
     *
     * Checks on content of data are not performed, meaning some invalid data may be permitted through.
     *  In this instance, the Klevu API response will detail this rejection
     *
     * @param mixed $data
     *
     * @return void
     * @throws InvalidTypeValidationException Where the passed data is neither null nor an array
     */
    public function execute(mixed $data): void
    {
        $this->validateType($data);
    }

    /**
     * @param mixed $data
     *
     * @return void
     * @throws InvalidTypeValidationException
     */
    private function validateType(mixed $data): void
    {
        if (null !== $data && !is_array($data)) {
            throw new InvalidTypeValidationException(
                errors: [
                    sprintf(
                        'Relations must be array|null, received %s',
                        get_debug_type($data),
                    ),
                ],
            );
        }
    }
}
