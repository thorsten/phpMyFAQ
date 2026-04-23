<?php

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\TwoFactor;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
class AuthenticationControllerTest extends TestCase
{
    private function buildController(
        Session $session,
        ?CurrentUser $currentUser = null,
        ?CurrentUser $containerCurrentUser = null,
        ?TwoFactor $twoFactor = null,
    ): AuthenticationController {
        $controller = (new ReflectionClass(AuthenticationController::class))->newInstanceWithoutConstructor();

        $sessionUser = $currentUser ?? $this->createMock(CurrentUser::class);
        $sessionUser->method('isLoggedIn')->willReturn(false);

        $container = $this->createMock(ContainerBuilder::class);
        $container->method('get')->willReturnCallback(
            function (string $id) use ($session, $containerCurrentUser, $twoFactor) {
                return match ($id) {
                    'session' => $session,
                    'phpmyfaq.user.current_user' => $containerCurrentUser ?? $this->createMock(CurrentUser::class),
                    'phpmyfaq.user.two-factor' => $twoFactor ?? $this->createMock(TwoFactor::class),
                    default => null,
                };
            }
        );

        $reflection = new ReflectionClass(AuthenticationController::class);
        $parent = $reflection->getParentClass()->getParentClass();
        $containerProperty = $parent->getProperty('container');
        $containerProperty->setValue($controller, $container);
        $currentUserProperty = $parent->getProperty('currentUser');
        $currentUserProperty->setValue($controller, $sessionUser);

        return $controller;
    }

    private function newSession(): Session
    {
        return new Session(new MockArraySessionStorage());
    }

    public function testCheckRejectsRequestWithoutPendingSession(): void
    {
        $session = $this->newSession();
        $controller = $this->buildController($session);

        $request = new Request([], ['token' => '123456', 'user-id' => '1']);

        $response = $controller->check($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('./login', $response->getTargetUrl());
    }

    public function testCheckRejectsMismatchedUserId(): void
    {
        $session = $this->newSession();
        $session->set('2fa_pending_user_id', 7);
        $controller = $this->buildController($session);

        $request = new Request([], ['token' => '123456', 'user-id' => '1']);

        $response = $controller->check($request);

        $this->assertSame('./login', $response->getTargetUrl());
    }

    public function testCheckLocksOutAfterFiveFailures(): void
    {
        $session = $this->newSession();
        $session->set('2fa_pending_user_id', 1);
        $session->set('2fa_failed_attempts', 5);
        $controller = $this->buildController($session);

        $request = new Request([], ['token' => '000000', 'user-id' => '1']);

        $response = $controller->check($request);

        $this->assertSame('./login', $response->getTargetUrl());
        $this->assertNull($session->get('2fa_pending_user_id'));
        $this->assertNull($session->get('2fa_failed_attempts'));
    }

    public function testCheckIncrementsFailedAttemptsOnWrongToken(): void
    {
        $session = $this->newSession();
        $session->set('2fa_pending_user_id', 1);

        $tfa = $this->createMock(TwoFactor::class);
        $tfa->method('validateToken')->willReturn(false);
        $sessionUser = $this->createMock(CurrentUser::class);
        $sessionUser->method('isLoggedIn')->willReturn(false);
        $containerUser = $this->createMock(CurrentUser::class);

        $controller = $this->buildController($session, $sessionUser, $containerUser, $tfa);

        $request = new Request([], ['token' => '000000', 'user-id' => '1']);
        $response = $controller->check($request);

        $this->assertSame('./token?user-id=1', $response->getTargetUrl());
        $this->assertSame(1, $session->get('2fa_failed_attempts'));
        $this->assertSame(1, $session->get('2fa_pending_user_id'));
    }

    public function testCheckClearsSessionAndLogsInOnSuccess(): void
    {
        $session = $this->newSession();
        $session->set('2fa_pending_user_id', 1);
        $session->set('2fa_failed_attempts', 2);

        $tfa = $this->createMock(TwoFactor::class);
        $tfa->method('validateToken')->willReturn(true);

        $sessionUser = $this->createMock(CurrentUser::class);
        $sessionUser->method('isLoggedIn')->willReturn(false);

        $containerUser = $this->createMock(CurrentUser::class);
        $containerUser->expects($this->once())->method('twoFactorSuccess');

        $controller = $this->buildController($session, $sessionUser, $containerUser, $tfa);

        $request = new Request([], ['token' => '654321', 'user-id' => '1']);
        $response = $controller->check($request);

        $this->assertSame('./', $response->getTargetUrl());
        $this->assertNull($session->get('2fa_pending_user_id'));
        $this->assertNull($session->get('2fa_failed_attempts'));
    }

    public function testCheckRedirectsWhenAlreadyLoggedIn(): void
    {
        $session = $this->newSession();
        $sessionUser = $this->createMock(CurrentUser::class);
        $sessionUser->method('isLoggedIn')->willReturn(true);

        $controller = $this->buildController($session, $sessionUser);

        $request = new Request([], ['token' => '000000', 'user-id' => '1']);
        $response = $controller->check($request);

        $this->assertSame('./', $response->getTargetUrl());
    }
}
