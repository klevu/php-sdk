<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Provider\Indexing;

/**
 * Enumeration of versions of Klevu Indexing services
 *
 * @since 1.0.0
 */
enum IndexingVersions
{
    /**
     * XML-based indexing
     *
     * @link https://docs.klevu.com/indexing-apis/XML
     */
    case XML;
    /**
     * JSON-based indexing
     *
     * @link https://docs.klevu.com/indexing-apis/JSON
     */
    case JSON;

    /**
     * Returns the Indexing URL route prefix for version
     *
     * @example /v2
     * @return string
     */
    public function getUrlRoutePrefix(): string
    {
        return match ($this) {
            IndexingVersions::XML => '',
            IndexingVersions::JSON => '/v2',
        };
    }
}
