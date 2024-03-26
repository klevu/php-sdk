<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Validator\RequestBearerToken\RequestHeaders;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Validator\ValidatorInterface;

/**
 * Validator testing type and format of header value containing content type information
 *
 * @since 1.0.0
 */
class ContentTypeValidator implements ValidatorInterface
{
    /**
     * Supported content types used if no values provided during object initialisation
     *
     * Currently only JSON is supported as bearer token generation is exclusive to newer versions
     *  of the indexing API, which do not support XML
     */
    private const DEFAULT_SUPPORTED_CONTENT_TYPES = [
        'application/json',
    ];

    /**
     * @var string[]
     */
    private readonly array $supportedContentTypes;
    /**
     * @var bool
     */
    private bool $allowRecursive = true;

    /**
     * @param string[] $supportedContentTypes
     */
    public function __construct(
        array $supportedContentTypes = self::DEFAULT_SUPPORTED_CONTENT_TYPES,
    ) {
        $this->supportedContentTypes = array_map('strval', $supportedContentTypes);
    }

    /**
     * Validates that the passed data is a string or array of strings containing valid and supported content type values
     *
     * @param mixed $data
     *
     * @return void
     * @throws InvalidTypeValidationException Where the passed data is not a string or array of strings
     * @throws InvalidDataValidationException Where the passed data is empty; is an array containing conflicting values;
     *       or value(s) are not in the list of supported content types
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
            $this->validateSupportedContentType($data);
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
                    'Content Type header value is required',
                ],
            );
        }

        $uniqueValues = array_unique($data);
        if (count($uniqueValues) > 1) {
            throw new InvalidDataValidationException(
                errors: [
                    sprintf(
                        'Conflicting Content Type header values found: %s',
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
                        'Content Type header value must be string|string[], received %s',
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
                    'Content Type header value is required',
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
    private function validateSupportedContentType(string $data): void
    {
        if (!in_array($data, $this->supportedContentTypes, true)) {
            throw new InvalidDataValidationException(
                errors: [
                    sprintf(
                        'Content Type header value is not supported. Received %s; expected one of %s',
                        $data,
                        implode(', ', $this->supportedContentTypes),
                    ),
                ],
            );
        }
    }
}
