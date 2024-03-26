<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Exception\Api;

/**
 * Exception thrown when an API request is rejected by the receiver.
 *
 * Example failure conditions include insufficient permissions; incorrect credentials; badly formed requests
 *
 * @since 1.0.0
 */
class BadRequestException extends \LogicException
{
    /**
     * @var string[]
     */
    private readonly array $errors;

    /**
     * @param string $message
     * @param int $code
     * @param string[] $errors
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message,
        int $code,
        array $errors = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);

        $errors = array_map('strval', $errors);
        // Where errors from batch requests are returned, they should be grouped by item,
        //  which they aren't by default in the response
        natcasesort($errors);
        $this->errors = array_values($errors);
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
