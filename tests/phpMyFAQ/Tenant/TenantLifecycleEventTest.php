<?php

namespace phpMyFAQ\Tenant;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TenantLifecycleEvent::class)]
class TenantLifecycleEventTest extends TestCase
{
    public function testReturnsTenantIdAndContext(): void
    {
        $event = new TenantLifecycleEvent(12, ['plan' => 'pro', 'source' => 'test']);

        $this->assertSame(12, $event->getTenantId());
        $this->assertSame(['plan' => 'pro', 'source' => 'test'], $event->getContext());
    }
}
