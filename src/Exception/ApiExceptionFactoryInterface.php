<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Exception;

interface ApiExceptionFactoryInterface
{
    /**
     * @param int $responseCode
     * @param string|null $responseBody
     *
     * @return ApiExceptionInterface|null
     */
    public function createFromResponse(
        int $responseCode,
        ?string $responseBody,
    ): ?ApiExceptionInterface;
}
