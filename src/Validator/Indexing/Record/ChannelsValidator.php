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
use Klevu\PhpSDK\Validator\Indexing\ChannelNameValidator;
use Klevu\PhpSDK\Validator\ValidatorInterface;

/**
 * Validator to test that a Record's "channels" value is valid for indexing
 *
 * @link https://docs.klevu.com/indexing-apis/api-schema-swaggeropenapi-specification
 * @since 1.0.0
 */
class ChannelsValidator implements ValidatorInterface
{
    /**
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $channelNameValidator;
    /**
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $attributesValidator;
    /**
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $groupsValidator;

    /**
     * @param ValidatorInterface|null $channelNameValidator
     *      If null, a new instance of {@see ChannelNameValidator} is used
     * @param ValidatorInterface|null $attributesValidator
     *      If null, a new instance of {@see AttributesValidator} is used
     * @param ValidatorInterface|null $groupsValidator
     *      If null, a new instance of {@see GroupsValidator} is used
     */
    public function __construct(
        ?ValidatorInterface $channelNameValidator = null,
        ?ValidatorInterface $attributesValidator = null,
        ?ValidatorInterface $groupsValidator = null,
    ) {
        $this->channelNameValidator = $channelNameValidator ?: new ChannelNameValidator();
        $this->attributesValidator = $attributesValidator ?: new AttributesValidator();
        $this->groupsValidator = $groupsValidator ?: new GroupsValidator(
            attributesValidator: $this->attributesValidator,
        );
    }

    /**
     * Validation that the passed data is a valid "channels" value
     *
     * Must be null, or array of valid channelName => data
     *
     * @uses ChannelNameValidator
     * @uses AttributesValidator
     *
     * @param mixed $data
     *
     * @return void
     * @throws InvalidTypeValidationException Where the passed data is not null or an array
     * @throws InvalidDataValidationException Where channel name or content fails validation
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
        foreach ($data as $channelName => $channelData) {
            $rowIndex++;

            try {
                $this->validateChannelRow(
                    channelName: $channelName,
                    channelData: $channelData,
                );
            } catch (ValidationException $exception) {
                $errors[$rowIndex] = sprintf(
                    '[%s] %s',
                    $channelName,
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
                    'Invalid row(s) for channels: %s',
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
                        'Channels must be array or null, received %s',
                        get_debug_type($data),
                    ),
                ],
            );
        }
    }

    /**
     * @param string|int $channelName
     * @param mixed $channelData
     *
     * @return void
     * @throws InvalidDataValidationException
     */
    private function validateChannelRow(
        string|int $channelName,
        mixed $channelData,
    ): void {
        $errors = [];

        try {
            $this->channelNameValidator->execute($channelName);
        } catch (ValidationException $exception) {
            $errors[] = $exception->getErrors();
        }

        if (!is_array($channelData)) {
            $errors[] = [
                sprintf('Channel data must be array, received %s', get_debug_type($channelData)),
            ];
        } else {
            try {
                $this->attributesValidator->execute(
                    data: $channelData[Record::FIELD_ATTRIBUTES] ?? [],
                );
            } catch (ValidationException $exception) {
                $errors[] = $exception->getErrors();
            }

            try {
                $this->groupsValidator->execute(
                    data: $channelData[Record::FIELD_GROUPS] ?? [],
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
