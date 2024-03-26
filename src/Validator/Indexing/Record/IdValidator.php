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
 * Validator to test that a Record's "id" value is valid for indexing
 *
 * @link https://docs.klevu.com/indexing-apis/api-definition
 * @since 1.0.0
 */
class IdValidator implements ValidatorInterface
{
    /**
     * Validates that the passed data is a valid "id" value
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
                        'Record Id must be string, received %s',
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
                    'Record Id is required',
                ],
            );
        }
    }
}
