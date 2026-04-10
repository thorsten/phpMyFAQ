<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Fixtures;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Controller\Administration\SkipsAuthenticationCheck;
use phpMyFAQ\User\CurrentUser;

/**
 * Fixture controller in the Administration namespace that opts out of the
 * listener's authentication enforcement via the SkipsAuthenticationCheck
 * marker interface — mirroring the production AuthenticationController
 * without inheriting from it (it is final).
 */
class ListenerAuthenticationFixtureController extends AbstractController implements SkipsAuthenticationCheck
{
    public int $initializeCalls = 0;

    public function __construct()
    {
        // Bypass parent constructor on purpose.
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
