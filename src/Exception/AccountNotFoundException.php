<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Exception;

use Klevu\PhpSDK\Model\AccountCredentials;

/**
 * Exception thrown when a Klevu account matching the provided credentials (JS or REST API keys) is not found
 *
 * @see AccountCredentials
 * @since 1.0.0
 */
class AccountNotFoundException extends \LogicException
{
    /**
     * @param string $jsApiKey
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $jsApiKey,
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        if (!$message) {
            $message = sprintf(
                'Klevu account not found for key "%s"',
                $jsApiKey,
            );
        }

        parent::__construct($message, $code, $previous);
    }
}
