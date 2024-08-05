<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Exception;

trait ApiExceptionTrait
{
    /**
     * @var string|null
     */
    private ?string $apiCode = null;
    /**
     * @var string|null
     */
    private ?string $path = null;
    /**
     * @var string[]|null
     */
    private ?array $debug = null;

    /**
     * Status value returned in response body for invalid Klevu API requests
     *
     * @example SERVER_ERROR
     *
     * @return string|null
     */
    public function getApiCode(): ?string
    {
        return $this->apiCode;
    }

    /**
     * @param string|null $apiCode
     *
     * @return void
     */
    public function setApiCode(?string $apiCode): void
    {
        $this->apiCode = $apiCode;
    }

    /**
     * Resource path returned in response body for invalid Klevu API requests
     *
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @param string|null $path
     *
     * @return void
     */
    public function setPath(?string $path): void
    {
        $this->path = $path;
    }

    /**
     * Array of debugging messages returned in response body for invalid Klevu API requests
     *
     * @return string[]|null
     */
    public function getDebug(): ?array
    {
        return $this->debug;
    }

    /**
     * @param scalar[]|null $debug
     *
     * @return void
     */
    public function setDebug(?array $debug): void
    {
        $this->debug = (null === $debug)
            ? null
            : array_map('strval', $debug);
    }
}
