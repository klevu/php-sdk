<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Validator\Account;

use Klevu\PhpSDK\Api\Service\Account\UpdateStoreFeedUrlServiceInterface;
use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Model\Platforms;
use Klevu\PhpSDK\Validator\ValidatorInterface;

/**
 * Validator testing a payload intended for an update store feed request is valid
 *
 * @see UpdateStoreFeedUrlServiceInterface
 * @since 1.0.0
 */
class UpdateStoreFeedUrlPayloadValidator implements ValidatorInterface
{
    /**
     * Validates that the passed data is an array containing expected keys with valid values for an update store
     *      feed API request
     *
     * @param mixed $data
     *
     * @return void
     * @throws InvalidTypeValidationException Where the passed data is not an array
     * @throws InvalidDataValidationException Where the passed data is missing required keys; or values are invalid
     */
    public function execute(mixed $data): void
    {
        if (!is_array($data)) {
            throw new InvalidTypeValidationException([
                sprintf(
                    'Payload must be array, received "%s"',
                    get_debug_type($data),
                ),
            ]);
        }

        $errors = array_filter(array_merge(
            $this->getErrorsForIndexingUrl($data),
            $this->getErrorsForStoreType($data),
            $this->getErrorsForStoreUrl($data),
        ));

        if ($errors) {
            throw new InvalidDataValidationException($errors);
        }
    }

    /**
     * @param mixed[] $data
     *
     * @return string[]
     */
    private function getErrorsForIndexingUrl(array $data): array
    {
        $errors = [];
        switch (true) {
            case !array_key_exists(UpdateStoreFeedUrlServiceInterface::PAYLOAD_KEY_INDEXING_URL, $data):
                $errors[] = 'Payload must contain indexingUrl';
                break;

            case !is_string($data[UpdateStoreFeedUrlServiceInterface::PAYLOAD_KEY_INDEXING_URL]):
                $errors[] = 'indexingUrl must be string';
                break;

            case !trim($data[UpdateStoreFeedUrlServiceInterface::PAYLOAD_KEY_INDEXING_URL]):
                $errors[] = 'indexingUrl must not be empty';
                break;

            case !filter_var($data[UpdateStoreFeedUrlServiceInterface::PAYLOAD_KEY_INDEXING_URL], FILTER_VALIDATE_URL):
                $errors[] = 'indexingUrl must be valid URL';
                break;
        }

        return $errors;
    }

    /**
     * @param mixed[] $data
     *
     * @return string[]
     */
    private function getErrorsForStoreType(array $data): array
    {
        $errors = [];
        switch (true) {
            case !array_key_exists(UpdateStoreFeedUrlServiceInterface::PAYLOAD_KEY_STORE_TYPE, $data):
                $errors[] = 'Payload must contain storeType';
                break;

            case !is_string($data[UpdateStoreFeedUrlServiceInterface::PAYLOAD_KEY_STORE_TYPE]):
                $errors[] = 'storeType must be string';
                break;

            case !Platforms::tryFrom($data[UpdateStoreFeedUrlServiceInterface::PAYLOAD_KEY_STORE_TYPE])?->isShopify()
                && !Platforms::tryFrom($data[UpdateStoreFeedUrlServiceInterface::PAYLOAD_KEY_STORE_TYPE])?->isBigCommerce(): // phpcs:ignore Generic.Files.LineLength.TooLong
                $errors[] = sprintf(
                    'Store of type "%s" cannot define custom feed URLs',
                    $data[UpdateStoreFeedUrlServiceInterface::PAYLOAD_KEY_STORE_TYPE],
                );
                break;
        }

        return $errors;
    }

    /**
     * @param mixed[] $data
     *
     * @return string[]
     */
    private function getErrorsForStoreUrl(array $data): array
    {
        $errors = [];
        switch (true) {
            case !array_key_exists(UpdateStoreFeedUrlServiceInterface::PAYLOAD_KEY_STORE_URL, $data):
                $errors[] = 'Payload must contain storeUrl';
                break;

            case !is_string($data[UpdateStoreFeedUrlServiceInterface::PAYLOAD_KEY_STORE_URL]):
                $errors[] = 'storeUrl must be string';
                break;

            case !trim($data[UpdateStoreFeedUrlServiceInterface::PAYLOAD_KEY_STORE_URL]):
                $errors[] = 'storeUrl must not be empty';
                break;
        }

        return $errors;
    }
}
