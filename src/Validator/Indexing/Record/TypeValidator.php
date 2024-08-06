<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Validator\Indexing\Record;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Validator\ValidatorInterface;

/**
 * Validator to test that a Record's "type" value is valid for indexing
 *
 * @link https://docs.klevu.com/indexing-apis/api-schema-swaggeropenapi-specification
 * @since 1.0.0
 */
class TypeValidator implements ValidatorInterface
{
    /**
     * Validate that the passed data is a valid "type" value
     *
     * Custom types are supported - strings are not restricted to just KLEVU_PRODUCT, etc
     *
     * @param mixed $data
     *
     * @return void
     * @throws InvalidTypeValidationException Where the passed data is neither null nor a string
     * @throws InvalidDataValidationException Where the passed data is empty after trimming
     */
    public function execute(mixed $data): void
    {
        $this->validateType($data);
        /** @var string $data */
        $this->validateNotEmpty($data);
    }

    /**
     * @param mixed $data
     *
     * @return void
     * @throws InvalidTypeValidationException
     */
    private function validateType(mixed $data): void
    {
        if (null !== $data && !is_string($data)) {
            throw new InvalidTypeValidationException(
                errors: [
                    sprintf(
                        'Record Type must be string, received %s',
                        get_debug_type($data),
                    ),
                ],
            );
        }
    }

    /**
     * @param string|null $data
     *
     * @return void
     * @throws InvalidDataValidationException
     */
    private function validateNotEmpty(?string $data): void
    {
        if ('' === trim((string)$data)) {
            throw new InvalidDataValidationException(
                errors: [
                    'Record Type is required',
                ],
            );
        }
    }
}
