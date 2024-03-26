<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Validator;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;

/**
 * Validator testing type and format of Klevu JavaScript API Keys (public key; eg klevu-1234567890)
 *
 * @since 1.0.0
 */
class JsApiKeyValidator implements ValidatorInterface
{
    /**
     * Validates that the passed data is a string in the JS API Key format
     *
     * @note Does NOT validate that the key corresponds to an existing Klevu account
     *
     * @param mixed $data
     *
     * @return void
     * @throws InvalidTypeValidationException Where a data type other than string is passed
     * @throws InvalidDataValidationException Where the passed data is empty or not a valid JS API Key
     */
    public function execute(mixed $data): void
    {
        if (!is_string($data)) {
            throw new InvalidTypeValidationException([
                sprintf(
                    'JS API Key must be of type "string"; received "%s"',
                    get_debug_type($data),
                ),
            ]);
        }

        $errors = [];
        switch (true) {
            case '' === trim($data):
                $errors[] = 'JS API Key must not be empty';
                break;

            case !preg_match('/^klevu-\d{1,20}$/', $data):
                $errors[] = 'JS API Key is not valid';
                break;
        }

        if ($errors) {
            throw new InvalidDataValidationException($errors);
        }
    }
}
