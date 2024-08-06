<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Example\Log;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

class Logger extends AbstractLogger
{
    /**
     * @var LoggerInterface|null
     */
    private readonly ?LoggerInterface $logger;
    /**
     * @var bool
     */
    private readonly bool $outputMessageAsText;

    /**
     * @param LoggerInterface|null $logger
     * @param bool $outputMessageAsText
     */
    public function __construct(
        ?LoggerInterface $logger = null,
        bool $outputMessageAsText = false,
    ) {
        $this->logger = $logger;
        $this->outputMessageAsText = $outputMessageAsText;
    }

    /**
     * @param string $level
     * @param \Stringable|string $message
     * @param mixed[] $context
     * @return void
     */
    public function log(
        $level, // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
        \Stringable|string $message,
        array $context = [],
    ): void {
        $this->logger?->log(
            level: $level,
            message: $message,
            context: $context,
        );

        if ($this->outputMessageAsText) {
            echo sprintf(
                '%s [%s] %s %s',
                date('Y-m-d H:i:s'),
                $level,
                $message,
                json_encode($context, JSON_PRETTY_PRINT),
            ) . PHP_EOL;
        }
    }
}
