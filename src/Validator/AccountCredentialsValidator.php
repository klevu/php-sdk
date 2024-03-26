<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Validator;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\AccountCredentials;

/**
 * Validator testing type and content of an expected AccountCredentials argument
 */
class AccountCredentialsValidator implements ValidatorInterface
{
    /**
     * @var ValidatorInterface
     */
    private ValidatorInterface $jsApiKeyValidator;
    /**
     * @var ValidatorInterface
     */
    private ValidatorInterface $restAuthKeyValidator;

    /**
     * @param ValidatorInterface|null $jsApiKeyValidator
     *      If null, a new instance of {@see JsApiKeyValidator} is used
     * @param ValidatorInterface|null $restAuthKeyValidator
     *      If null, a new instance of {@see RestAuthKeyValidator} is used
     */
    public function __construct(
        ?ValidatorInterface $jsApiKeyValidator = null,
        ?ValidatorInterface $restAuthKeyValidator = null,
    ) {
        $this->jsApiKeyValidator = $jsApiKeyValidator ?: new JsApiKeyValidator();
        $this->restAuthKeyValidator = $restAuthKeyValidator ?: new RestAuthKeyValidator();
    }

    /**
     * Validates that the passed data is an AccountCredentials object containing valid, non-empty data
     *
     * @uses JsApiKeyValidator
     * @uses RestAuthKeyValidator
     *
     * @param mixed $data
     *
     * @return void
     * @throws InvalidTypeValidationException Where a data type other than an {@see AccountCredentials} object is passed
     * @throws InvalidDataValidationException Where either JS API Key or REST AUTH Key validation fails
     */
    public function execute(mixed $data): void
    {
        if (!($data instanceof AccountCredentials)) {
            throw new InvalidTypeValidationException([
                sprintf(
                    'Account credentials must be of type "%s"; received "%s"',
                    AccountCredentials::class,
                    get_debug_type($data),
                ),
            ]);
        }

        $errors = [];
        try {
            $this->jsApiKeyValidator->execute($data->jsApiKey);
        } catch (ValidationException $e) {
            $errors[] = $e->getErrors();
        }

        try {
            $this->restAuthKeyValidator->execute($data->restAuthKey);
        } catch (ValidationException $e) {
            $errors[] = $e->getErrors();
        }

        if ($errors) {
            throw new InvalidDataValidationException(array_merge([], ...$errors));
        }
    }
}
