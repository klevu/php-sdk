<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Validator\RequestBearerToken\RequestHeaders;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Validator\JsApiKeyValidator;
use Klevu\PhpSDK\Validator\ValidatorInterface;

/**
 * Validator testing type and format of header value containing Klevu JS API Key(s)
 *
 * @since 1.0.0
 */
class ApiKeyValidator implements ValidatorInterface
{
    /**
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $jsApiKeyValidator;
    /**
     * @var bool
     */
    private bool $allowRecursive = true;

    /**
     * @param ValidatorInterface|null $jsApiKeyValidator
     *      If null, a new instance of {@see JsApiKeyValidator} is used
     */
    public function __construct(
        ?ValidatorInterface $jsApiKeyValidator = null,
    ) {
        $this->jsApiKeyValidator = $jsApiKeyValidator ?: new JsApiKeyValidator();
    }

    /**
     * Validates that the passed data is a string or array of strings containing valid Klevu JS API Keys
     *
     * @uses JsApiKeyValidator
     *
     * @param mixed $data
     *
     * @return void
     * @throws InvalidDataValidationException Where the passed data is empty; is an array containing conflicting values;
     *      or value(s) are not valid JS API Keys
     * @throws InvalidTypeValidationException Where the passed data is not a string or array of strings
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
            $this->jsApiKeyValidator->execute($data);
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
                    'API Key header value is required',
                ],
            );
        }

        $uniqueValues = array_unique($data);
        if (count($uniqueValues) > 1) {
            throw new InvalidDataValidationException(
                errors: [
                    sprintf(
                        'Conflicting API Key header values found: %s',
                        implode(', ', $uniqueValues),
                    ),
                ],
            );
        }
    }
}
