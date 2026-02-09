<?php

/**
 * Tenant event dispatcher
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-02-08
 */

declare(strict_types=1);

namespace phpMyFAQ\Tenant;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TenantEventDispatcher
{
    public const string TENANT_CREATED = 'tenant.created';
    public const string TENANT_SUSPENDED = 'tenant.suspended';
    public const string TENANT_DELETED = 'tenant.deleted';
    public const string TENANT_PLAN_CHANGED = 'tenant.plan.changed';

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function dispatchTenantCreated(int $tenantId, array $context = []): TenantLifecycleEvent
    {
        return $this->dispatch(self::TENANT_CREATED, $tenantId, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function dispatchTenantSuspended(int $tenantId, array $context = []): TenantLifecycleEvent
    {
        return $this->dispatch(self::TENANT_SUSPENDED, $tenantId, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function dispatchTenantDeleted(int $tenantId, array $context = []): TenantLifecycleEvent
    {
        return $this->dispatch(self::TENANT_DELETED, $tenantId, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function dispatchTenantPlanChanged(
        int $tenantId,
        string $oldPlan,
        string $newPlan,
        array $context = [],
    ): TenantLifecycleEvent {
        $eventContext = $context;
        $eventContext['oldPlan'] = $oldPlan;
        $eventContext['newPlan'] = $newPlan;

        return $this->dispatch(self::TENANT_PLAN_CHANGED, $tenantId, $eventContext);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function dispatch(string $eventName, int $tenantId, array $context): TenantLifecycleEvent
    {
        $event = new TenantLifecycleEvent($tenantId, $context);
        $this->eventDispatcher->dispatch($event, $eventName);

        return $event;
    }
}
