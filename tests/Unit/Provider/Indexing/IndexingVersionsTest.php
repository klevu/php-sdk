<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Provider\Indexing;

use Klevu\PhpSDK\Provider\Indexing\IndexingVersions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(IndexingVersions::class)]
class IndexingVersionsTest extends TestCase
{
    #[Test]
    public function testGetUrlRoutePrefix(): void
    {
        $this->assertSame('', IndexingVersions::XML->getUrlRoutePrefix());
        $this->assertSame('/v2', IndexingVersions::JSON->getUrlRoutePrefix());
    }
}
