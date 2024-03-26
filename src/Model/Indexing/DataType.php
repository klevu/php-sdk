<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model\Indexing;

use Klevu\PhpSDK\Api\Model\Indexing\AttributeInterface;

/**
 * Enumeration of supported indexing attribute types
 *
 * @see AttributeInterface::getDatatype()
 * @since 1.0.0
 */
enum DataType: string
{
    /**
     * Simple string value, including multiline
     */
    case STRING = 'STRING';
    /**
     * Integer or float values
     */
    case NUMBER = 'NUMBER';
    /**
     * String in a valid datetime format
     */
    case DATETIME = 'DATETIME';
    /**
     * Array of string values
     */
    case MULTIVALUE = 'MULTIVALUE';
    /**
     * A valid JSON encoded value
     */
    case JSON = 'JSON';
    /**
     * True / False
     */
    case BOOLEAN = 'BOOLEAN';

    /**
     * Whether custom (ie not immutable) attributes can use this datatype
     *
     * Klevu's indexing will automatically register a number of core attributes
     *  (which return with the immutable flag). These attributes may use data
     *  types which cannot be used when registering custom attributes
     *  (see {@see AttributesServiceInterface::put()})
     * Where an attribute which is not immutable uses a data type which is not
     *  available to custom attributes, validation will fail before the request
     *  is sent (see {@see AttributeValidator::execute()})
     *
     * @return bool
     */
    public function isAvailableToCustomAttributes(): bool
    {
        return match ($this) {
            DataType::STRING, DataType::MULTIVALUE => true,
            // Future support
            DataType::NUMBER, DataType::DATETIME,
            // Core attributes only
            DataType::JSON, DataType::BOOLEAN => false,
        };
    }
}
