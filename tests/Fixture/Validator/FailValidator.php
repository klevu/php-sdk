<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Fixture\Validator;

use Klevu\PhpSDK\Validator\ValidatorInterface;

class FailValidator implements ValidatorInterface
{
    /**
     * @param \Throwable $exception
     */
    public function __construct(
        private readonly \Throwable $exception,
    ) {
    }

    /**
     * @param mixed $data
     *
     * @return void
     * @throws \Throwable
     */
    public function execute(
        mixed $data, // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    ): void {
        throw $this->exception;
    }
}
