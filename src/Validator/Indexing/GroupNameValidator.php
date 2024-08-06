<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Validator\Indexing;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Validator\ValidatorInterface;

class GroupNameValidator implements ValidatorInterface
{
    /**
     * [a-zA-Z0-9_]+: Matches one or more alphanumeric characters or underscores.
     */
    private const GROUP_NAME_REGEX = '/^[a-zA-Z0-9_]+$/';

    /**
     * Validates that the passed data is a string in a valid format to use as an indexing group name
     *
     * @note Value must be alphanumeric and may contain underscores
     *
     * @param mixed $data
     *
     * @return void
     * @throws InvalidTypeValidationException Where a data type other than string is passed
     * @throws InvalidDataValidationException Where the passed data is empty or contains unsupported characters
     */
    public function execute(mixed $data): void
    {
        $this->validateType($data);
        /** @var string|null $data */
        $this->validateNotEmpty($data);
        /** @var string $data */
        $this->validateMatchesExpectedFormat($data);
    }

    /**
     * @param mixed $data
     *
     * @return void
     * @throws InvalidTypeValidationException
     */
    private function validateType(mixed $data): void
    {
        // We include null here as it is more user-friendly to report the
        //  not-empty error, rather than an invalid type for null
        if (null !== $data && !is_string($data)) {
            throw new InvalidTypeValidationException(
                errors: [
                    sprintf(
                        'Group Name must be string, received %s',
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
                    'Group Name is required',
                ],
            );
        }
    }

    /**
     * @param string $data
     *
     * @return void
     * @throws InvalidDataValidationException
     */
    private function validateMatchesExpectedFormat(string $data): void
    {
        if (!preg_match(self::GROUP_NAME_REGEX, $data)) {
            throw new InvalidDataValidationException(
                errors: [
                    'Group Name must be alphanumeric, and can include underscores (_)',
                ],
            );
        }
    }
}
