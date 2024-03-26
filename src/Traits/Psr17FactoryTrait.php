<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Traits;

use Http\Discovery\Psr17Factory;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;

/**
 * @internal
 */
trait Psr17FactoryTrait
{
    /**
     * @var Psr17Factory|null
     */
    private ?Psr17Factory $psr17Factory = null;
    /**
     * @var RequestFactoryInterface|null
     */
    private ?RequestFactoryInterface $requestFactory = null;
    /**
     * @var ResponseFactoryInterface|null
     */
    private ?ResponseFactoryInterface $responseFactory = null;

    /**
     * @return Psr17Factory
     */
    private function getPsr17Factory(): Psr17Factory
    {
        return $this->psr17Factory ??= new Psr17Factory(
            requestFactory: $this->requestFactory,
            responseFactory: $this->responseFactory,
        );
    }
}
