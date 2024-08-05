<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Validator\Indexing;

use Klevu\PhpSDK\Api\Model\Indexing\UpdateInterface;
use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\Indexing\UpdateOperations;
use Klevu\PhpSDK\Validator\Indexing\Record\IdValidator;
use Klevu\PhpSDK\Validator\ValidatorInterface;

class UpdateValidator implements ValidatorInterface
{
    /**
     * Regular expression to match a valid JSON Pointer used by JSON PATCH operations
     * Converted from https://regex101.com/r/pUEonc/1
     */
    private const REGEX_JSON_POINTER = '/^((?:(?:[-._~a-zA-Z0-9]|%[a-fA-F0-9]{2}|[!$&\'()*+,;=]|@)+\/(?:[-._~a-zA-Z0-9]|%[a-fA-F0-9]{2}|[!$&\'()*+,;=]|@|:)*)|(?:[-._~a-zA-Z0-9]|%[a-fA-F0-9]{2}|[!$&\'()*+,;=]|@)+\/?(?:[-._~a-zA-Z0-9]|%[a-fA-F0-9]{2}|[!$&\'()*+,;=]|@|:)*(\/(?:[-._~a-zA-Z0-9]|%[a-fA-F0-9]{2}|[!$&\'()*+,;=]|@|:)*)?|\/(?:[-._~a-zA-Z0-9]|%[a-fA-F0-9]{2}|[!$&\'()*+,;=]|@)+(\/(?:[-._~a-zA-Z0-9]|%[a-fA-F0-9]{2}|[!$&\'()*+,;=]|@|:)*)*|(?:\/(?:[-._~a-zA-Z0-9]|%[a-fA-F0-9]{2}|[!$&\'()*+,;=]|@|:)*)*)$/'; // phpcs:ignore Generic.Files.LineLength.TooLong

    /**
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $recordIdValidator;

    /**
     * @param ValidatorInterface|null $recordIdValidator
     */
    public function __construct(
        ?ValidatorInterface $recordIdValidator = null,
    ) {
        $this->recordIdValidator = $recordIdValidator ?: new IdValidator();
    }

    /**
     * Validates that the passed Update is valid to be PATCHed
     *
     * @param mixed $data
     *
     * @return void
     * @throws InvalidDataValidationException Where op, path, or value data are not valid or missing (if required)
     * @throws InvalidTypeValidationException Where the passed data is not an instance of {@see UpdateInterface}
     */
    public function execute(mixed $data): void
    {
        $this->validateType($data);

        $errors = [];

        // phpcs:ignore SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable
        /** @var UpdateInterface $data */
        try {
            $this->recordIdValidator->execute(
                data: $data->getRecordId(),
            );
        } catch (ValidationException $exception) {
            $errors[] = $exception->getErrors();
        }

        try {
            $this->validateOp($data);
        } catch (ValidationException $exception) {
            $errors[] = $exception->getErrors();
        }

        try {
            $this->validatePath($data);
        } catch (ValidationException $exception) {
            $errors[] = $exception->getErrors();
        }

        if ($errors) {
            throw new InvalidDataValidationException(
                errors: array_merge([], ...$errors),
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
        if (!($data instanceof UpdateInterface)) {
            throw new InvalidTypeValidationException(
                errors: [
                    sprintf(
                        'Update must be instance of %s, received %s',
                        UpdateInterface::class,
                        get_debug_type($data),
                    ),
                ],
            );
        }
    }

    /**
     * @param UpdateInterface $data
     *
     * @return void
     * @throws InvalidDataValidationException
     */
    private function validateOp(UpdateInterface $data): void
    {
        $op = trim($data->getOp());

        if (!$op) {
            throw new InvalidDataValidationException(
                errors: [
                    'Update operation [op] is required',
                ],
            );
        }

        $updateOperation = UpdateOperations::tryFrom($op);
        if (!$updateOperation) {
            $updateOperationValues = array_map(
                callback: static fn (UpdateOperations $updateOperation): string => $updateOperation->value,
                array: UpdateOperations::cases(),
            );

            throw new InvalidDataValidationException(
                errors: [
                    sprintf(
                        'Unrecognised update operation [op] "%s", must be one of %s',
                        $op,
                        implode(', ', $updateOperationValues),
                    ),
                ],
            );
        }
    }

    /**
     * @param UpdateInterface $data
     *
     * @return void
     */
    private function validatePath(UpdateInterface $data): void
    {
        $path = $data->getPath();

        // Note, "" is a valid JSON PATCH reference to the root; null, however, means a path has not been set
        if (null === $path) {
            throw new InvalidDataValidationException(
                errors: [
                    'Path must be set',
                ],
            );
        }

        if (!preg_match(self::REGEX_JSON_POINTER, $path)) {
            throw new InvalidDataValidationException(
                errors: [
                    sprintf(
                        'Path "%s" is not a valid JSON Pointer value',
                        $path,
                    ),
                ],
            );
        }
    }
}
