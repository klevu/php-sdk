<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Validator\RequestBearerToken\RequestHeaders;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Validator\ValidatorInterface;

/**
 * Validator testing type and format of header value containing request timestamp value
 *
 * @since 1.0.0
 */
class TimestampValidator implements ValidatorInterface
{
    /**
     * @var bool
     */
    private bool $allowRecursive = true;

    /**
     * Validates that the passed data is a string or array of strings containing ISO-8601 formatted dates
     *
     * @link https://www.iso.org/iso-8601-date-and-time-format.html
     *
     * @param mixed $data
     *
     * @return void
     * @throws InvalidTypeValidationException Where the passed data is not a string or array of strings
     * @throws InvalidDataValidationException Where the passed data is empty; is an array containing conflicting values;
     *      value(s) are not valid ISO-8601 dates; value(s) are in the future;
     *      or value(s) are more than 10 minutes in the past
     * @throws ValidationException
     */
    public function execute(mixed $data): void
    {
        if (is_array($data) && $this->allowRecursive) {
            $this->validateArrayData($data);

            $this->allowRecursive = false;
            array_walk($data, [$this, 'execute']);

            return;
        }

        try {
            $this->validateType($data);
            /** @var ?string $data */
            $this->validateNotEmpty($data);
            /** @var non-empty-string $data */
            $this->validateFormat($data);
            $this->validateTimeWindow($data);
        } finally {
            $this->allowRecursive = true;
        }
    }

    /**
     * @param mixed[] $data
     *
     * @return void
     * @throws InvalidDataValidationException
     */
    private function validateArrayData(array $data): void
    {
        if (!array_filter($data)) {
            throw new InvalidDataValidationException(
                errors: [
                    'Timestamp header value is required',
                ],
            );
        }

        $uniqueValues = array_unique($data);
        if (count($uniqueValues) > 1) {
            throw new InvalidDataValidationException(
                errors: [
                    sprintf(
                        'Conflicting Timestamp header values found: %s',
                        implode(', ', $uniqueValues),
                    ),
                ],
            );
        }
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
            // Null is allowed during type check as the later check on empty will catch
            //  and throw a more understandable error
            throw new InvalidTypeValidationException(
                errors: [
                    sprintf(
                        'Timestamp header value must be string|string[], received %s',
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
        if (!trim((string)$data)) {
            throw new InvalidDataValidationException(
                errors: [
                    'Timestamp header value is required',
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
    private function validateFormat(string $data): void
    {
        $errorMessage = sprintf(
            'Timestamp header value must be valid ISO-8601 date time string; received %s',
            $data,
        );

        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/', $data)) {
            throw new InvalidDataValidationException(
                errors: [
                    $errorMessage,
                ],
            );
        }

        $ymd = explode(
            separator: '-',
            string: substr(
                string: $data,
                offset: 0,
                length: 10,
            ),
        );
        $checkdate = checkdate(
            month: (int)$ymd[1],
            day: (int)$ymd[2],
            year: (int)$ymd[0],
        );
        if (!$checkdate) {
            throw new InvalidDataValidationException(
                errors: [
                    $errorMessage,
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
    private function validateTimeWindow(string $data): void
    {
        $unixTimeNow = time();
        $unixTimeData = strtotime($data);

        // Add 60 seconds as a buffer
        if ($unixTimeData > ($unixTimeNow + 60)) {
            throw new InvalidDataValidationException(
                errors: [
                    sprintf('Timestamp header value must not be in the future; received %s', $data),
                ],
            );
        }

        // Allow 10 minutes in the past
        if ($unixTimeData < ($unixTimeNow - 600)) {
            throw new InvalidDataValidationException(
                errors: [
                    sprintf(
                        'Timestamp header value must not be more than 10 minutes in the past; received %s',
                        $data,
                    ),
                ],
            );
        }
    }
}
