<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Exception\Api;

use Klevu\PhpSDK\Exception\ApiExceptionFactoryInterface;
use Klevu\PhpSDK\Exception\ApiExceptionInterface;

class JsonExceptionFactory implements ApiExceptionFactoryInterface
{
    /**
     * @var bool
     */
    private readonly bool $requireValidJsonBody;

    /**
     * @param bool $requireValidJsonBody
     */
    public function __construct(
        bool $requireValidJsonBody = true,
    ) {
        $this->requireValidJsonBody = $requireValidJsonBody;
    }

    /**
     * @param int $responseCode
     * @param string|null $responseBody
     *
     * @return ApiExceptionInterface|null
     */
    public function createFromResponse(
        int $responseCode,
        ?string $responseBody,
    ): ?ApiExceptionInterface {
        $responseBodyDecoded = [];
        if (null !== $responseBody) {
            /** @var array<string|string[]> $responseBodyDecoded */
            $responseBodyDecoded = @json_decode( // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
                json: $responseBody,
                associative: true,
            ) ?: [];

            if (json_last_error() && $this->requireValidJsonBody) {
                return new BadResponseException(
                    message: 'Received invalid JSON response',
                    code: $responseCode,
                    errors: [
                        json_last_error_msg(),
                    ],
                );
            }
        }

        $responseMessage = $this->getMessageFromResponseBodyDecoded($responseBodyDecoded);
        $errors = $this->getErrorsFromResponseBodyDecoded($responseBodyDecoded);
        $apiCode = $this->getApiCodeFromResponseBodyDecoded($responseBodyDecoded);
        $path = $this->getPathFromResponseBodyDecoded($responseBodyDecoded);
        $debug = $this->getDebugFromResponseBodyDecoded($responseBodyDecoded);

        if (499 <= $responseCode) {
            return new BadResponseException(
                message: $responseMessage ?: sprintf(
                    'Unexpected Response Code [%d]',
                    $responseCode,
                ),
                code: $responseCode,
                errors: $errors,
                apiCode: $apiCode,
                path: $path,
                debug: $debug,
            );
        }

        if (400 <= $responseCode) {
            return new BadRequestException(
                message: $responseMessage ?: sprintf(
                    'API request rejected by Klevu API [%d]',
                    $responseCode,
                ),
                code: $responseCode,
                errors: $errors,
                apiCode: $apiCode,
                path: $path,
                debug: $debug,
            );
        }

        return null;
    }

    /**
     * @param array<string|string[]> $responseBodyDecoded
     *
     * @return string|null
     */
    private function getMessageFromResponseBodyDecoded(
        array $responseBodyDecoded,
    ): ?string {
        $message = $responseBodyDecoded['message'] ?? null;
        if (is_array($message)) {
            $message = implode(', ', $message);
        }

        return $message;
    }

    /**
     * @param array<string|string[]> $responseBodyDecoded
     *
     * @return string[]
     */
    private function getErrorsFromResponseBodyDecoded(
        array $responseBodyDecoded,
    ): array {
        $responseMessage = $responseBodyDecoded['message'] ?? '';
        $errors = match (true) {
            is_array($responseMessage) => $responseMessage,
            is_scalar($responseMessage) => [
                (string)$responseMessage,
            ],
        };
        $responseErrors = $responseBodyDecoded['errors'] ?? null;
        if (is_array($responseErrors)) {
            $errors = array_merge(
                $errors,
                $responseErrors,
            );
        }

        return array_filter($errors);
    }

    /**
     * @param array<string|string[]> $responseBodyDecoded
     *
     * @return string|null
     */
    private function getApiCodeFromResponseBodyDecoded(
        array $responseBodyDecoded,
    ): ?string {
        $apiCode = $responseBodyDecoded['code'] ?? null;
        if (is_array($apiCode)) {
            $apiCode = implode(', ', $apiCode);
        }

        return $apiCode;
    }

    /**
     * @param array<string|string[]> $responseBodyDecoded
     *
     * @return string|null
     */
    private function getPathFromResponseBodyDecoded(
        array $responseBodyDecoded,
    ): ?string {
        $path = $responseBodyDecoded['path'] ?? null;
        if (is_array($path)) {
            $path = implode(', ', $path);
        }

        return $path;
    }

    /**
     * @param array<string|string[]|mixed[]> $responseBodyDecoded
     *
     * @return string[]|null
     */
    private function getDebugFromResponseBodyDecoded(
        array $responseBodyDecoded,
    ): ?array {
        $debug = $responseBodyDecoded['debug'] ?? null;

        if (is_scalar($debug)) {
            return array_filter([
                trim((string)$debug),
            ]);
        }

        if (!is_array($debug)) {
            return null;
        }

        $debugMessages = [];
        foreach ($debug as $debugRow) {
            if (is_scalar($debugRow)) {
                $debugMessages[] = [trim((string)$debugRow)];
                continue;
            }

            if (!is_array($debugRow) || empty($debugRow['message'])) {
                continue;
            }

            $debugMessages[] = array_map('strval', (array)$debugRow['message']);
        }

        return array_filter(
            array_merge([], ...$debugMessages),
        );
    }
}
