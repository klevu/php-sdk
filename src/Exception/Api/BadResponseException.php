<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Exception\Api;

use Klevu\PhpSDK\Exception\ApiExceptionInterface;
use Klevu\PhpSDK\Exception\ApiExceptionTrait;

/**
 * Exception thrown when a valid API request does not return in the expected manner
 *
 * Example failure conditions include timeouts, endpoint not found, and server errors
 *
 * @since 1.0.0
 */
class BadResponseException extends \RuntimeException implements ApiExceptionInterface
{
    use ApiExceptionTrait;

    /**
     * @var string[]
     */
    private readonly array $errors;

    /**
     * @param string $message
     * @param int $code
     * @param string[] $errors
     * @param string|null $apiCode
     * @param string|null $path
     * @param string[]|null $debug
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message,
        int $code,
        array $errors = [],
        ?string $apiCode = null,
        ?string $path = null,
        ?array $debug = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);

        $this->errors = array_map('strval', $errors);

        $this->setApiCode($apiCode);
        $this->setPath($path);
        $this->setDebug($debug);
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
