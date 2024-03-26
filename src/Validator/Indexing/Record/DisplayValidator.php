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
 * Validator to test that a Record's "display" value is valid for indexing
 *
 * @link https://docs.klevu.com/indexing-apis/api-definition
 * @since 1.0.0
 */
class DisplayValidator implements ValidatorInterface
{
    /**
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $attributeNameValidator;

    /**
     * @param ValidatorInterface|null $attributeNameValidator
     *      If null, a new instance of {@see AttributeNameValidator} is used
     */
    public function __construct(
        ?ValidatorInterface $attributeNameValidator = null,
    ) {
        $this->attributeNameValidator = $attributeNameValidator ?: new AttributeNameValidator();
    }

    /**
     * Rudimentary validation that the passed data is a valid "display" value
     *
     * Checks on content of data are not performed, beyond validation of attribute names. This includes
     *  checks that attributes exist in Klevu's index, and that values are of the corresponding type.
     *  Invalid data of this type will be rejected and detail in the API response
     *
     * @uses AttributeNameValidator
     *
     * @param mixed $data
     *
     * @return void
     * @throws InvalidDataValidationException Where one or more attribute names used as keys fail validation
     * @throws InvalidTypeValidationException Where the passed data is neither null nor an array
     */
    public function execute(mixed $data): void
    {
        $this->validateType($data);
        /** @var mixed[] $data */
        if (!$data) {
            return;
        }

        $this->validateAttributeNames($data);
    }

    /**
     * @param mixed $data
     *
     * @return void
     * @throws InvalidTypeValidationException
     */
    private function validateType(mixed $data): void
    {
        if (null !== $data && !is_array($data)) {
            throw new InvalidTypeValidationException(
                errors: [
                    sprintf(
                        'Display must be array|null, received %s',
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
                    'Invalid keys for display: "%s"',
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
}
