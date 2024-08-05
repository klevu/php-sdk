<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Provider\Indexing\Batch;

use Klevu\PhpSDK\Model\Indexing\RecordIterator;
use Klevu\PhpSDK\Model\Indexing\UpdateIterator;
use Klevu\PhpSDK\Model\IteratorInterface;
use Klevu\PhpSDK\Provider\Indexing\Batch\RequestPayloadProvider\Record;
use Klevu\PhpSDK\Provider\Indexing\Batch\RequestPayloadProvider\Update;

/**
 * Converts collection of Indexing Record objects into JSON format, suitable for sending to
 *  Klevu via API for batch ADD or UPDATE, or PATCH requests
 *
 * @since 1.0.0
 * @link https://docs.klevu.com/indexing-apis/api-schema-swaggeropenapi-specification
 */
class RequestPayloadProvider implements RequestPayloadProviderInterface
{
    /**
     * @var RequestPayloadProviderInterface[]
     */
    private array $requestPayloadProviders = [];

    /**
     * @param RequestPayloadProviderInterface[]|null $requestPayloadProviders
     */
    public function __construct(
        ?array $requestPayloadProviders = null,
    ) {
        if (null === $requestPayloadProviders) {
            $requestPayloadProviders = [
                RecordIterator::class => new Record(),
                UpdateIterator::class => new Update(),
            ];
        }
        array_walk($requestPayloadProviders, [$this, 'addRequestPayloadProvider']);
    }

    public function get(IteratorInterface $records): string
    {
        $return = '';
        foreach ($this->requestPayloadProviders as $iteratorFqcn => $requestPayloadProvider) {
            if (!($records instanceof $iteratorFqcn)) {
                continue;
            }

            $return = $requestPayloadProvider->get($records);
        }

        return $return;
    }

    /**
     * @param RequestPayloadProviderInterface $requestPayloadProvider
     * @param string $iteratorFqcn
     *
     * @return void
     */
    private function addRequestPayloadProvider(
        RequestPayloadProviderInterface $requestPayloadProvider,
        string $iteratorFqcn,
    ): void {
        $this->requestPayloadProviders[$iteratorFqcn] = $requestPayloadProvider;
    }
}
