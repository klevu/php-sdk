<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDK\Test\Unit\Model\Analytics\Collect;

use Klevu\PhpSDK\Model\Analytics\Collect\Event;
use Klevu\PhpSDK\Model\Analytics\Collect\EventType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(Event::class)]
class EventTest extends TestCase
{
    /**
     * @param EventType $eventType
     * @param string $apikey
     * @param string $version
     * @param mixed[] $data
     *
     * @return void
     */
    #[Test]
    #[TestWith([EventType::ORDER_PURCHASE, 'klevu-1234567890', '1.0.0', ['foo' => 'bar']])]
    public function testConstructor_Valid(
        EventType $eventType,
        string $apikey,
        string $version,
        array $data,
    ): void {
        $event = new Event(
            event: $eventType,
            apikey: $apikey,
            version: $version,
            data: $data,
        );

        $this->assertSame($eventType, $event->event);
        $this->assertSame($apikey, $event->apikey);
        $this->assertSame($version, $event->version);
        $this->assertSame($data, $event->data);
    }
}
