<?php

namespace phpMyFAQ\Tenant;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

#[CoversClass(TenantEventDispatcher::class)]
#[UsesClass(TenantLifecycleEvent::class)]
class TenantEventDispatcherTest extends TestCase
{
    public function testDispatchTenantCreated(): void
    {
        $dispatcher = new EventDispatcher();
        $receivedEvent = null;
        $receivedName = null;

        $dispatcher->addListener(TenantEventDispatcher::TENANT_CREATED, function (
            TenantLifecycleEvent $event,
            string $eventName
        ) use (&$receivedEvent, &$receivedName): void {
            $receivedEvent = $event;
            $receivedName = $eventName;
        });

        $tenantDispatcher = new TenantEventDispatcher($dispatcher);
        $event = $tenantDispatcher->dispatchTenantCreated(10, ['source' => 'self-service']);

        $this->assertInstanceOf(TenantLifecycleEvent::class, $event);
        $this->assertSame(10, $event->getTenantId());
        $this->assertSame(['source' => 'self-service'], $event->getContext());
        $this->assertSame(TenantEventDispatcher::TENANT_CREATED, $receivedName);
        $this->assertSame($event, $receivedEvent);
    }

    public function testDispatchTenantSuspended(): void
    {
        $dispatcher = new EventDispatcher();
        $receivedEvent = null;

        $dispatcher->addListener(TenantEventDispatcher::TENANT_SUSPENDED, function (TenantLifecycleEvent $event) use (
            &$receivedEvent
        ): void {
            $receivedEvent = $event;
        });

        $tenantDispatcher = new TenantEventDispatcher($dispatcher);
        $event = $tenantDispatcher->dispatchTenantSuspended(11, ['reason' => 'billing']);

        $this->assertSame(11, $event->getTenantId());
        $this->assertSame(['reason' => 'billing'], $event->getContext());
        $this->assertSame($event, $receivedEvent);
    }

    public function testDispatchTenantDeleted(): void
    {
        $dispatcher = new EventDispatcher();
        $receivedEvent = null;

        $dispatcher->addListener(TenantEventDispatcher::TENANT_DELETED, function (TenantLifecycleEvent $event) use (
            &$receivedEvent
        ): void {
            $receivedEvent = $event;
        });

        $tenantDispatcher = new TenantEventDispatcher($dispatcher);
        $event = $tenantDispatcher->dispatchTenantDeleted(12, ['requestedBy' => 'admin']);

        $this->assertSame(12, $event->getTenantId());
        $this->assertSame(['requestedBy' => 'admin'], $event->getContext());
        $this->assertSame($event, $receivedEvent);
    }

    public function testDispatchTenantPlanChangedAddsOldAndNewPlanToContext(): void
    {
        $dispatcher = new EventDispatcher();
        $receivedEvent = null;
        $receivedName = null;

        $dispatcher->addListener(TenantEventDispatcher::TENANT_PLAN_CHANGED, function (
            TenantLifecycleEvent $event,
            string $eventName
        ) use (&$receivedEvent, &$receivedName): void {
            $receivedEvent = $event;
            $receivedName = $eventName;
        });

        $tenantDispatcher = new TenantEventDispatcher($dispatcher);
        $event = $tenantDispatcher->dispatchTenantPlanChanged(13, 'basic', 'pro', ['source' => 'upgrade-flow']);

        $this->assertSame(13, $event->getTenantId());
        $this->assertSame(
            [
                'source' => 'upgrade-flow',
                'oldPlan' => 'basic',
                'newPlan' => 'pro',
            ],
            $event->getContext(),
        );
        $this->assertSame(TenantEventDispatcher::TENANT_PLAN_CHANGED, $receivedName);
        $this->assertSame($event, $receivedEvent);
    }
}
