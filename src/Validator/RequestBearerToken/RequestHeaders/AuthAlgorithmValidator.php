<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Validator\RequestBearerToken\RequestHeaders;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Model\Indexing\AuthAlgorithms;
use Klevu\PhpSDK\Validator\ValidatorInterface;

/**
 * Validator testing type and format of header value containing authorization algorithm name
 *
 * @since 1.0.0
 */
class AuthAlgorithmValidator implements ValidatorInterface
{
    /**
     * @var AuthAlgorithms[]
     */
    private array $supportedAlgorithms = [];
    /**
     * @var bool
     */
    private bool $allowRecursive = true;

    /**
     * @param AuthAlgorithms[] $supportedAlgorithms
     */
    public function __construct(
        ?array $supportedAlgorithms = null,
    ) {
        $supportedAlgorithms ??= AuthAlgorithms::cases();
        $this->supportedAlgorithms = [];
        array_walk($supportedAlgorithms, [$this, 'addSupportedAlgorithm']);
    }

    /**
     * Validates that the passed data is a string or array of strings which correspond to a supported hashing algorithm
     *
     * @param mixed $data
     *
     * @return void
     * @throws InvalidTypeValidationException
     *      Where the passed data is not an instance (or array of) {@see AuthAlgorithms}
     * @throws InvalidDataValidationException Where the passed data is empty; is an array containing conflicting values;
     *      or values not in the list of supported algorithms defined on object initialisation
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
            $this->validateSupportedAlgorithm($data);
        } finally {
            $this->allowRecursive = true;
        }
    }

    /**
     * @param AuthAlgorithms $supportedAlgorithm
     *
     * @return void
     */
    private function addSupportedAlgorithm(AuthAlgorithms $supportedAlgorithm): void
    {
        if (!in_array($supportedAlgorithm, $this->supportedAlgorithms, true)) {
            $this->supportedAlgorithms[] = $supportedAlgorithm;
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
                    'Auth Algorithm header value is required',
                ],
            );
        }

        $uniqueValues = array_unique($data);
        if (count($uniqueValues) > 1) {
            throw new InvalidDataValidationException(
                errors: [
                    sprintf(
                        'Conflicting Auth Algorithm header values found: %s',
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
                        'Auth Algorithm header value must be string|string[], received %s',
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
                    'Auth Algorithm header value is required',
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
    private function validateSupportedAlgorithm(string $data): void
    {
        $supported = false;
        foreach ($this->supportedAlgorithms as $algorithm) {
            if ($data === $algorithm->value) {
                $supported = true;
                break;
            }
        }

        if (!$supported) {
            $supportedAlgorithmStrings = array_map(
                static fn (AuthAlgorithms $algorithm): string => $algorithm->value,
                $this->supportedAlgorithms,
            );

            throw new InvalidDataValidationException(
                errors: [
                    sprintf(
                        'Auth Algorithm header value is not supported. Received %s; expected one of %s',
                        $data,
                        implode(', ', $supportedAlgorithmStrings),
                    ),
                ],
            );
        }
    }
}
