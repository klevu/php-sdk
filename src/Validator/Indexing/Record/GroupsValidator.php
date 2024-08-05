<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Validator\Indexing\Record;

use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\Indexing\Record;
use Klevu\PhpSDK\Validator\Indexing\GroupNameValidator;
use Klevu\PhpSDK\Validator\ValidatorInterface;

/**
 * Validator to test that a Record's "groups" value is valid for indexing
 *
 * @link https://docs.klevu.com/indexing-apis/api-schema-swaggeropenapi-specification
 * @since 1.0.0
 */
class GroupsValidator implements ValidatorInterface
{
    /**
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $groupNameValidator;
    /**
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $attributesValidator;

    /**
     * @param ValidatorInterface|null $groupNameValidator
     *      If null, a new instance of {@see GroupNameValidator} is used
     * @param ValidatorInterface|null $attributesValidator
     *      If null, a new instance of {@see AttributesValidator} is used
     */
    public function __construct(
        ?ValidatorInterface $groupNameValidator = null,
        ?ValidatorInterface $attributesValidator = null,
    ) {
        $this->groupNameValidator = $groupNameValidator ?: new GroupNameValidator();
        $this->attributesValidator = $attributesValidator ?: new AttributesValidator();
    }

    /**
     * Validation that the passed data is a valid "groups" value
     *
     * Must be null, or array of valid groupName => data
     *
     * @uses GroupNameValidator
     * @uses AttributesValidator
     *
     * @param mixed $data
     *
     * @return void
     * @throws InvalidTypeValidationException Where the passed data is not null or an array
     * @throws InvalidDataValidationException Where group name or content fails validation
     */
    public function execute(mixed $data): void
    {
        $this->validateType($data);
        /** @var mixed[] $data */
        if (!$data) {
            return;
        }

        $errors = [];
        $rowIndex = -1;
        foreach ($data as $groupName => $groupData) {
            $rowIndex++;

            try {
                $this->validateGroupRow(
                    groupName: $groupName,
                    groupData: $groupData,
                );
            } catch (ValidationException $exception) {
                $errors[$rowIndex] = sprintf(
                    '[%s] %s',
                    $groupName,
                    implode(
                        separator: '; ',
                        array: $exception->getErrors(),
                    ),
                );
            }
        }

        if ($errors) {
            throw new InvalidDataValidationException(
                errors: array_values($errors),
                message: sprintf(
                    'Invalid row(s) for groups: %s',
                    implode(
                        separator: ', ',
                        array: array_keys($errors),
                    ),
                ),
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
        if (null !== $data && !is_array($data)) {
            throw new InvalidTypeValidationException(
                errors: [
                    sprintf(
                        'Groups must be array or null, received %s',
                        get_debug_type($data),
                    ),
                ],
            );
        }
    }

    /**
     * @param string|int $groupName
     * @param mixed $groupData
     *
     * @return void
     * @throws InvalidDataValidationException
     */
    private function validateGroupRow(
        string|int $groupName,
        mixed $groupData,
    ): void {
        $errors = [];

        try {
            $this->groupNameValidator->execute($groupName);
        } catch (ValidationException $exception) {
            $errors[] = $exception->getErrors();
        }

        if (!is_array($groupData)) {
            $errors[] = [
                sprintf('Group data must be array, received %s', get_debug_type($groupData)),
            ];
        } else {
            try {
                $this->attributesValidator->execute(
                    data: $groupData[Record::FIELD_ATTRIBUTES] ?? [],
                );
            } catch (ValidationException $exception) {
                $errors[] = $exception->getErrors();
            }
        }

        if ($errors) {
            throw new InvalidDataValidationException(
                errors: array_merge([], ...$errors),
            );
        }
    }
}
