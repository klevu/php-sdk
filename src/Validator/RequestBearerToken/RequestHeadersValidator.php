<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Validator\RequestBearerToken;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Provider\RequestBearerTokenProviderInterface;
use Klevu\PhpSDK\Validator\RequestBearerToken\RequestHeaders\ApiKeyValidator;
use Klevu\PhpSDK\Validator\RequestBearerToken\RequestHeaders\AuthAlgorithmValidator;
use Klevu\PhpSDK\Validator\RequestBearerToken\RequestHeaders\ContentTypeValidator;
use Klevu\PhpSDK\Validator\RequestBearerToken\RequestHeaders\TimestampValidator;
use Klevu\PhpSDK\Validator\ValidatorInterface;

/**
 * Composite validator testing an array of request header values for use in generating a Request Bearer Token
 *
 * @see RequestBearerTokenProviderInterface
 * @since 1.0.0
 */
class RequestHeadersValidator implements ValidatorInterface
{
    /**
     * @var ValidatorInterface[]
     */
    private array $headerValidators = [];

    /**
     * @param ValidatorInterface[]|null $headerValidators
     *      If null, the following validators are added:
     *          {@see TimestampValidator},
     *          {@see ApiKeyValidator},
     *          {@see AuthAlgorithmValidator},
     *          {@see ContentTypeValidator}
     */
    public function __construct(
        ?array $headerValidators = null,
    ) {
        if (null === $headerValidators) {
            $headerValidators = [
                RequestBearerTokenProviderInterface::API_HEADER_KEY_TIMESTAMP => new TimestampValidator(),
                RequestBearerTokenProviderInterface::API_HEADER_KEY_APIKEY => new ApiKeyValidator(),
                RequestBearerTokenProviderInterface::API_HEADER_KEY_AUTH_ALGO => new AuthAlgorithmValidator(),
                RequestBearerTokenProviderInterface::API_HEADER_KEY_CONTENT_TYPE => new ContentTypeValidator(),
            ];
        }

        array_walk($headerValidators, [$this, 'addHeaderValidator']);
    }

    /**
     * Validates that the passed data is array of valid header values,
     *  based on child validator passed during initialisation
     *
     * @param mixed $data
     *
     * @return void
     * @throws InvalidTypeValidationException Where a data type other than an {@see AccountCredentials} object is passed
     * @throws InvalidDataValidationException Where either JS API Key or REST AUTH Key validation fails
     */
    public function execute(mixed $data): void
    {
        if (!is_array($data)) {
            throw new InvalidTypeValidationException(
                errors: [
                    sprintf('Headers data must be array, received %s', get_debug_type($data)),
                ],
            );
        }

        $errors = [];
        foreach ($this->headerValidators as $headerKey => $headerValidator) {
            try {
                $headerValidator->execute($data[$headerKey] ?? null);
            } catch (ValidationException $exception) {
                $errors[$headerKey] = $exception->getErrors();
            }
        }

        if ($errors) {
            throw new InvalidDataValidationException(
                errors: array_map(
                    static fn (array $errorMessages, string $headerKey): string => sprintf(
                        '%s: %s',
                        $headerKey,
                        implode('; ', $errorMessages),
                    ),
                    array_values($errors),
                    array_keys($errors),
                ),
            );
        }
    }

    /**
     * @param ValidatorInterface $headerValidator
     * @param string $headerKey
     *
     * @return void
     */
    private function addHeaderValidator(
        ValidatorInterface $headerValidator,
        string $headerKey,
    ): void {
        $this->headerValidators[$headerKey] = $headerValidator;
    }
}
