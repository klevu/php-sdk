<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Validator\Indexing;

use Klevu\PhpSDK\Api\Model\Indexing\RecordInterface;
use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\Indexing\Record;
use Klevu\PhpSDK\Validator\Indexing\Record\AttributesValidator;
use Klevu\PhpSDK\Validator\Indexing\Record\DisplayValidator;
use Klevu\PhpSDK\Validator\Indexing\Record\IdValidator as RecordIdValidator;
use Klevu\PhpSDK\Validator\Indexing\Record\RelationsValidator;
use Klevu\PhpSDK\Validator\Indexing\Record\TypeValidator as RecordTypeValidator;
use Klevu\PhpSDK\Validator\ValidatorInterface;

/**
 * Composite validator for testing a record is valid for requests to indexing APIs
 *
 * @link https://docs.klevu.com/indexing-apis/api-definition
 * @since 1.0.0
 */
class RecordValidator implements ValidatorInterface
{
    /**
     * @var ValidatorInterface[][]
     */
    private array $dataValidators = [];

    /**
     * @param array<string, ValidatorInterface[]|ValidatorInterface|null>|null $dataValidators
     *      If null, the following validators are added:
     *          {@see RecordIdValidator},
     *          {@see RecordTypeValidator},
     *          {@see RelationsValidator},
     *          {@see AttributesValidator},
     *          {@see DisplayValidator}
     */
    public function __construct(
        ?array $dataValidators = null,
    ) {
        if (null === $dataValidators) {
            $dataValidators = [
                Record::FIELD_ID => new RecordIdValidator(),
                Record::FIELD_TYPE => new RecordTypeValidator(),
                Record::FIELD_RELATIONS => new RelationsValidator(),
                Record::FIELD_ATTRIBUTES => new AttributesValidator(),
                Record::FIELD_DISPLAY => new DisplayValidator(),
            ];
        }
        $this->setDataValidators($dataValidators);
    }

    /**
     * Validates that the passed Record is valid to be PUT using child validators registered during object instantiation
     *
     * @param mixed $data
     *
     * @return void
     * @throws InvalidTypeValidationException Where the passed data is not an instance of {@see RecordInterface}
     * @throws InvalidDataValidationException Where checks performed by child validators fail for one or more sections
     */
    public function execute(mixed $data): void
    {
        $this->validateType($data);

        $errors = [];
        try {
            /** @var RecordInterface $data */
            $this->validateRecordId($data);
        } catch (ValidationException $exception) {
            $errors[] = array_map(
                static fn (string $error): string => 'id: ' . $error,
                $exception->getErrors(),
            );
        }
        try {
            $this->validateRecordType($data);
        } catch (ValidationException $exception) {
            $errors[] = array_map(
                static fn (string $error): string => 'type: ' . $error,
                $exception->getErrors(),
            );
        }
        try {
            $this->validateRelations($data);
        } catch (ValidationException $exception) {
            $errors[] = array_map(
                static fn (string $error): string => 'relations: ' . $error,
                $exception->getErrors(),
            );
        }
        try {
            $this->validateAttributes($data);
        } catch (ValidationException $exception) {
            $errors[] = array_map(
                static fn (string $error): string => 'attributes: ' . $error,
                $exception->getErrors(),
            );
        }
        try {
            $this->validateDisplay($data);
        } catch (ValidationException $exception) {
            $errors[] = array_map(
                static fn (string $error): string => 'display: ' . $error,
                $exception->getErrors(),
            );
        }

        if ($errors) {
            throw new InvalidDataValidationException(
                errors: array_merge([], ...$errors),
            );
        }
    }

    /**
     * @param array<string, ValidatorInterface[]|ValidatorInterface|null> $dataValidators
     *
     * @return void
     */
    private function setDataValidators(
        array $dataValidators,
    ): void {
        $this->dataValidators = [];
        array_walk($dataValidators, function (mixed $validators, mixed $property): void {
            if (null === $validators) {
                unset($this->dataValidators[$property]);
                return;
            }

            if ($validators instanceof ValidatorInterface) {
                $validators = [$validators];
            }
            foreach ($validators as $validator) {
                $this->addDataValidator(
                    property: $property,
                    validator: $validator,
                );
            }
        });
    }

    /**
     * @param string $property
     * @param ValidatorInterface $validator
     *
     * @return void
     */
    private function addDataValidator(
        string $property,
        ValidatorInterface $validator,
    ): void {
        $this->dataValidators[$property] ??= [];
        $this->dataValidators[$property][] = $validator;
    }

    /**
     * @param mixed $data
     *
     * @return void
     * @throws InvalidTypeValidationException
     */
    private function validateType(mixed $data): void
    {
        if (!($data instanceof RecordInterface)) {
            throw new InvalidTypeValidationException(
                errors: [
                    sprintf(
                        'Record must be instance of %s, received %s',
                        RecordInterface::class,
                        get_debug_type($data),
                    ),
                ],
            );
        }
    }

    /**
     * @param RecordInterface $data
     *
     * @return void
     * @throws InvalidTypeValidationException
     * @throws InvalidDataValidationException
     */
    private function validateRecordId(RecordInterface $data): void
    {
        $id = $data->getId();
        foreach ($this->dataValidators[Record::FIELD_ID] ?? [] as $validator) {
            $validator->execute($id);
        }
    }

    /**
     * @param RecordInterface $data
     *
     * @return void
     * @throws InvalidTypeValidationException
     * @throws InvalidDataValidationException
     */
    private function validateRecordType(RecordInterface $data): void
    {
        $type = $data->getType();
        foreach ($this->dataValidators[Record::FIELD_TYPE] ?? [] as $validator) {
            $validator->execute($type);
        }
    }

    /**
     * @param RecordInterface $data
     *
     * @return void
     * @throws InvalidTypeValidationException
     * @throws InvalidDataValidationException
     */
    private function validateRelations(RecordInterface $data): void
    {
        $relations = $data->getRelations();
        foreach ($this->dataValidators[Record::FIELD_RELATIONS] ?? [] as $validator) {
            $validator->execute($relations);
        }
    }

    /**
     * @param RecordInterface $data
     *
     * @return void
     * @throws InvalidTypeValidationException
     * @throws InvalidDataValidationException
     */
    private function validateAttributes(RecordInterface $data): void
    {
        $attributes = $data->getAttributes();
        foreach ($this->dataValidators[Record::FIELD_ATTRIBUTES] ?? [] as $validator) {
            $validator->execute($attributes);
        }
    }

    /**
     * @param RecordInterface $data
     *
     * @return void
     * @throws InvalidTypeValidationException
     * @throws InvalidDataValidationException
     */
    private function validateDisplay(RecordInterface $data): void
    {
        $display = $data->getDisplay();
        foreach ($this->dataValidators[Record::FIELD_DISPLAY] ?? [] as $validator) {
            $validator->execute($display);
        }
    }
}
