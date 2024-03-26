<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Validator\Indexing;

use Klevu\PhpSDK\Api\Model\Indexing\AttributeInterface;
use Klevu\PhpSDK\Exception\Validation\InvalidDataValidationException;
use Klevu\PhpSDK\Exception\Validation\InvalidTypeValidationException;
use Klevu\PhpSDK\Model\Indexing\DataType;
use Klevu\PhpSDK\Validator\ValidatorInterface;

/**
 * Validator for testing an AttributeInterface is valid for PUT requests to indexing APIs
 *
 * @since 1.0.0
 */
class AttributeValidator implements ValidatorInterface
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
     * Validates that the passed attribute is valid to be PUT using the Attribute service class
     *
     * @uses AttributeNameValidator
     *
     * @param mixed $data
     *
     * @return void
     * @throws InvalidDataValidationException Where the attribute name is invalid; or the datatype is missing,
     *      unrecognised, or unavailable to custom attributes
     * @throws InvalidTypeValidationException Where the passed data is not an instance of {@see AttributeInterface}
     */
    public function execute(mixed $data): void
    {
        $this->validateType($data);

        /** @var AttributeInterface $data */ // phpcs:ignore SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable
        $this->attributeNameValidator->execute(
            data: $data->getAttributeName(),
        );

        $this->validateDatatype($data);
    }

    /**
     * @param mixed $data
     *
     * @return void
     * @throws InvalidTypeValidationException
     */
    private function validateType(mixed $data): void
    {
        if (!($data instanceof AttributeInterface)) {
            throw new InvalidTypeValidationException(
                errors: [
                    sprintf(
                        'Attribute must be instance of %s; received %s',
                        AttributeInterface::class,
                        get_debug_type($data),
                    ),
                ],
            );
        }
    }

    /**
     * @param AttributeInterface $attribute
     *
     * @return void
     * @throws InvalidDataValidationException
     */
    private function validateDatatype(AttributeInterface $attribute): void
    {
        if ($attribute->isImmutable()) {
            // Data type restrictions only apply to custom attributes
            // All data types are available to core (immutable) attributes
            return;
        }

        $datatype = $attribute->getDatatype();
        if ('' === trim($datatype)) {
            throw new InvalidDataValidationException(
                errors: [
                    'Attribute datatype must not be empty',
                ],
            );
        }

        $datatypeEnum = DataType::tryFrom($datatype);
        if (!$datatypeEnum) {
            throw new InvalidDataValidationException(
                errors: [
                    sprintf(
                        'Attribute datatype "%s" is not a recognised value',
                        $datatype,
                    ),
                ],
            );
        }

        if (!$datatypeEnum->isAvailableToCustomAttributes()) {
            throw new InvalidDataValidationException(
                errors: [
                    sprintf(
                        'Attribute datatype "%s" is not available to custom attributes',
                        $datatypeEnum->value,
                    ),
                ],
            );
        }
    }
}
