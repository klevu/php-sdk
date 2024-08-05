<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Exception;

interface ApiExceptionInterface extends \Throwable
{
    /**
     * Status value returned in response body for invalid Klevu API requests
     *
     * @example SERVER_ERROR
     *
     * @return string|null
     */
    public function getApiCode(): ?string;

    /**
     * Resource path returned in response body for invalid Klevu API requests
     *
     * @return string|null
     */
    public function getPath(): ?string;

    /**
     * Array pf debugging messages returned in response body for invalid Klevu API requests
     *
     * @return string[]|null
     */
    public function getDebug(): ?array;
}
