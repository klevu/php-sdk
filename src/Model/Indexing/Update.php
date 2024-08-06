<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model\Indexing;

use Klevu\PhpSDK\Api\Model\Indexing\UpdateInterface;
use Klevu\PhpSDK\Service\Indexing\BatchService;
use Klevu\PhpSDK\Validator\Indexing\UpdateValidator;

/**
 * Data model representation of a Klevu Indexing update operation
 *
 * @link https://docs.klevu.com/indexing-apis/api-schema-swaggeropenapi-specification
 * @see BatchService
 * @since 1.0.0
 */
class Update implements UpdateInterface
{
    /**
     * Key used to reference record identifier (record_id) property when converting to/from array
     *
     * @see Update::toArray()
     * @see UpdateFactory::create()
     * @var string
     */
    final public const FIELD_RECORD_ID = 'record_id';
    /**
     * Key used to reference operation (op) property when converting to/from array
     *
     * @see Update::toArray()
     * @see UpdateFactory::create()
     * @var string
     */
    final public const FIELD_OP = 'op';
    /**
     * Key used to reference JSON pointer (path) property when converting to/from array
     *
     * @see Update::toArray()
     * @see UpdateFactory::create()
     * @var string
     */
    final public const FIELD_PATH = 'path';
    /**
     * Key used to reference value property when converting to/from array
     *
     * @see Update::toArray()
     * @see UpdateFactory::create()
     * @var string
     */
    final public const FIELD_VALUE = 'value';

    /**
     * @var string
     */
    private string $recordId = '';
    /**
     * @var string
     */
    private string $op = '';
    /**
     * @var string|null
     */
    private ?string $path = null;
    /**
     * @var mixed
     */
    private mixed $value = null;

    /**
     * @return string
     */
    public function getRecordId(): string
    {
        return $this->recordId;
    }

    /**
     * Sets record identifier on which update will be performed
     *
     * @see UpdateValidator
     *
     * @param string $recordId
     *
     * @return void
     */
    public function setRecordId(string $recordId): void
    {
        $this->recordId = $recordId;
    }

    /**
     * @return string
     */
    public function getOp(): string
    {
        return $this->op;
    }

    /**
     * Sets operation to be performed
     *
     * Value should be one of {@see UpdateOperations} values, though validation is not performed
     *  at this stage and unknown values will be permitted.
     *
     * @see UpdateOperations
     * @see UpdateValidator
     *
     * @param string $op
     *
     * @return void
     */
    public function setOp(string $op): void
    {
        $this->op = $op;
    }

    /**
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Sets path of item to be acted upon
     *
     * Value should be a valid JSON Pointer format, though validation is not performed
     *   at this stage and invalid values will be permitted.
     *
     * @note An empty string is a valid JSON Pointer, referencing the entire object; while a null value
     *   will be treated as not set
     *
     * @link https://datatracker.ietf.org/doc/html/rfc6901/
     * @see UpdateValidator
     *
     * @param string|null $path
     *
     * @return void
     */
    public function setPath(?string $path): void
    {
        $this->path = $path;
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Sets value to be added or replaced for the item being acted upon
     *
     * @param mixed $value
     *
     * @return void
     */
    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            self::FIELD_RECORD_ID => $this->getRecordId(),
            self::FIELD_OP => $this->getOp(),
            self::FIELD_PATH => $this->getPath(),
            self::FIELD_VALUE => $this->getValue(),
        ];
    }
}
