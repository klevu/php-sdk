<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Validator\Indexing;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Validator\ValidatorInterface;

/**
 * Validator testing type and format of attribute name used for indexing data with Klevu
 *
 * @since 1.0.0
 */
class AttributeNameValidator implements ValidatorInterface
{
    /**
     * Maximum length supported by Klevu API for an attribute name
     */
    final public const ATTRIBUTE_NAME_MAXLENGTH = 200;
    /**
     * (?!_): Negative lookahead to ensure that the string does not start with an underscore.
     * (?!.*_$): Negative lookahead to ensure that the string does not end with an underscore.
     * [a-zA-Z0-9_]+: Matches one or more alphanumeric characters or underscores.
     */
    private const ATTRIBUTE_NAME_REGEX = '/^(?!_)(?!.*_$)[a-zA-Z0-9_]+$/';

    /**
     * Validates that the passed data is a string in a valid format to use as an indexing attribute name
     *
     * @note Value must be alphanumeric and may contain underscores, but cannot start or end with an underscore
     *
     * @param mixed $data
     *
     * @return void
     * @throws InvalidTypeValidationException Where a data type other than string is passed
     * @throws InvalidDataValidationException Where the passed data is empty, too long,
     *                                          or contains unsupported characters
     */
    public function execute(mixed $data): void
    {
        $this->validateType($data);
        /** @var string|null $data */
        $this->validateNotEmpty($data);
        /** @var string $data */
        $this->validateExpectedLength($data);
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
                        'Attribute Name must be string, received %s',
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
                    'Attribute Name is required',
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
    private function validateExpectedLength(string $data): void
    {
        if (strlen($data) > self::ATTRIBUTE_NAME_MAXLENGTH) {
            throw new InvalidDataValidationException(
                errors: [
                    sprintf(
                        'Attribute Name must be less than or equal to %d characters',
                        self::ATTRIBUTE_NAME_MAXLENGTH,
                    ),
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
        if (!preg_match(self::ATTRIBUTE_NAME_REGEX, $data)) {
            throw new InvalidDataValidationException(
                errors: [
                    'Attribute Name must be alphanumeric, can include underscores (_) but cannot start or end with an underscore', // phpcs:ignore Generic.Files.LineLength.TooLong
                ],
            );
        }
    }
}
