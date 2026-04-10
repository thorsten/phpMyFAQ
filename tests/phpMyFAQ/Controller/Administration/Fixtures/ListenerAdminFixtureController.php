<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Fixtures;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\User\CurrentUser;

/**
 * Lightweight fixture controller in the Administration namespace.
 * Used by ControllerContainerListenerTest to verify that admin
 * controllers receive an enforced authentication check.
 */
class ListenerAdminFixtureController extends AbstractController
{
    public int $initializeCalls = 0;

    public function __construct()
    {
        // Bypass parent constructor — we don't want the fallback container
        // or services.php loading during tests.
    }

    public function setCurrentUserForTest(CurrentUser $currentUser): void
    {
        $this->currentUser = $currentUser;
    }

    protected function initializeFromContainer(): void
    {
        ++$this->initializeCalls;
    }

    public function index(): void {}
}
