<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Fixture\Validator;

use Klevu\PhpSDK\Validator\ValidatorInterface;

class SuccessValidator implements ValidatorInterface
{
    /**
     * @param mixed $data
     *
     * @return void
     */
    public function execute(
        mixed $data, // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    ): void {
        return;
    }
}
