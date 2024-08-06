<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Validator\Indexing\Record;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Validator\Indexing\AttributeNameValidator;
use Klevu\PhpSDK\Validator\ValidatorInterface;

/**
 * Composite validator to test that a Record's "attributes" value is valid for indexing
 *
 * @link https://docs.klevu.com/indexing-apis/api-schema-swaggeropenapi-specification
 * @since 1.0.0
 */
class AttributesValidator implements ValidatorInterface
{
    /**
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $attributeNameValidator;
    /**
     * @var ValidatorInterface[]
     */
    private array $dataValidators = [];

    /**
     * @param ValidatorInterface|null $attributeNameValidator
     *      If null, a new instance of {@see AttributeNameValidator} is used
     * @param array<string|int, ValidatorInterface|null> $dataValidators
     */
    public function __construct(
        ?ValidatorInterface $attributeNameValidator = null,
        array $dataValidators = [],
    ) {
        $this->attributeNameValidator = $attributeNameValidator ?: new AttributeNameValidator();
        $this->setDataValidators($dataValidators);
    }

    /**
     * Validation that the passed data is a valid "attributes" value
     *
     * Checks on content are not performed by default, but supplementary validators can be registered during
     *  object instantiation allowing custom validation to be performed in the implementing platform
     * All validators are executed, returning every encountered error, as opposed to throwing a validation
     *  exception on the first issue found
     *
     * @uses AttributeNameValidator
     *
     * @param mixed $data
     *
     * @return void
     * @throws InvalidTypeValidationException Where one or more attribute names used as keys fail validation;
     *      or one or more registered child validators throws a {@see ValidationException}
     * @throws InvalidDataValidationException Where the passed data is not an array
     */
    public function execute(mixed $data): void
    {
        $this->validateType($data);
        /** @var mixed[] $data */
        if (!$data) {
            return;
        }

        $this->validateAttributeNames($data);
        $this->executeDataValidators($data);
    }

    /**
     * @param (ValidatorInterface|null)[] $dataValidators
     *
     * @return void
     */
    private function setDataValidators(
        array $dataValidators,
    ): void {
        $this->dataValidators = [];
        array_walk($dataValidators, function (mixed $validator, mixed $identifier): void {
            $this->addDataValidator(
                identifier: $identifier,
                validator: $validator,
            );
        });
    }

    /**
     * @param string|int $identifier
     * @param ValidatorInterface|null $validator
     *
     * @return void
     */
    private function addDataValidator(
        string|int $identifier,
        ValidatorInterface|null $validator,
    ): void {
        if (null === $validator) {
            return;
        }

        if (is_string($identifier)) {
            $this->dataValidators[$identifier] = $validator;
        } else {
            $this->dataValidators[] = $validator;
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
        if (!is_array($data)) {
            throw new InvalidTypeValidationException(
                errors: [
                    sprintf(
                        'Attributes must be array, received %s',
                        get_debug_type($data),
                    ),
                ],
            );
        }
    }

    /**
     * @param mixed[] $data
     *
     * @return void
     * @throws InvalidDataValidationException
     */
    private function validateAttributeNames(array $data): void
    {
        $invalidAttributeNameErrors = [];
        foreach (array_keys($data) as $attributeName) {
            try {
                $this->attributeNameValidator->execute($attributeName);
            } catch (ValidationException $exception) {
                $invalidAttributeNameErrors[] = [
                    'attributeName' => $attributeName,
                    'errors' => array_map(
                        static fn (string $errorMessage): string => sprintf(
                            '[%s] %s',
                            $attributeName,
                            $errorMessage,
                        ),
                        $exception->getErrors(),
                    ),
                ];
            }
        }

        if ($invalidAttributeNameErrors) {
            throw new InvalidDataValidationException(
                errors: array_merge(
                    [],
                    ...array_column(
                        array: $invalidAttributeNameErrors,
                        column_key: 'errors',
                    ),
                ),
                message: sprintf(
                    'Invalid keys for attributes: "%s"',
                    implode(
                        separator: '", "',
                        array: array_column(
                            array: $invalidAttributeNameErrors,
                            column_key: 'attributeName',
                        ),
                    ),
                ),
            );
        }
    }

    /**
     * @param mixed[] $data
     *
     * @return void
     * @throws InvalidDataValidationException
     */
    private function executeDataValidators(array $data): void
    {
        $dataValidationErrors = [];
        foreach ($this->dataValidators as $validator) {
            try {
                $validator->execute($data);
            } catch (ValidationException $exception) {
                $dataValidationErrors[] = $exception->getErrors();
            }
        }

        if ($dataValidationErrors) {
            throw new InvalidDataValidationException(
                errors: array_merge([], ...$dataValidationErrors),
                message: 'Attributes data is not valid',
            );
        }
    }
}
