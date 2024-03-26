<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Model;

use Klevu\PhpSDK\Api\Model\ApiResponseInterface;

/**
 * Representation of an HTTP response from an API call made by the SDK
 *
 * @since 1.0.0
 */
class ApiResponse implements ApiResponseInterface
{
    /**
     * @param int $responseCode HTTP status code returned by the response
     * @param string $message Message returned by the API within the response body (optional)
     * @param string|null $status Status returned by the API within the response body (optional)
     * @param string|null $jobId ID of job created in Klevu's pipelines, for example in batch index requests (optional)
     * @param string[]|null $errors Array of errors returned by the API within the response body
     */
    public function __construct(
        public readonly int $responseCode,
        public readonly string $message = '',
        public readonly ?string $status = null,
        public readonly ?string $jobId = null,
        public readonly ?array $errors = null,
    ) {
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return 200 === $this->responseCode
            && empty($this->errors);
    }

    /**
     * @return int
     */
    public function getResponseCode(): int
    {
        return $this->responseCode;
    }

    /**
     * @return string[]
     */
    public function getMessages(): array
    {
        $messages = array_filter(
            array_merge(
                [$this->message],
                $this->errors ?? [],
            ),
        );
        natcasesort($messages);

        return array_values($messages);
    }
}
