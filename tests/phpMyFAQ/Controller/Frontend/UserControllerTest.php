<?php

/**
 * Frontend UserController Test — password-change step-up guard.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Controller\Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-08-13
 */

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Auth\AuthDatabase;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
class UserControllerTest extends TestCase
{
    private UserController $controller;
    private CurrentUser $currentUserMock;

    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->currentUserMock = $this->createMock(CurrentUser::class);

        $reflection = new ReflectionClass(UserController::class);
        $this->controller = $reflection->newInstanceWithoutConstructor();

        $currentUserProp = $reflection->getParentClass()->getProperty('currentUser');
        $currentUserProp->setValue($this->controller, $this->currentUserMock);
    }

    private function isRemovalRequestForCurrentUser(int|false|null $userId, string $loginName, string $email): bool
    {
        $method = new ReflectionMethod(UserController::class, 'isRemovalRequestForCurrentUser');

        return $method->invoke($this->controller, $userId, $loginName, $email);
    }

    private function sessionUserIs(int $userId, string $login, string $email, bool $loggedIn = true): void
    {
        $this->currentUserMock->method('isLoggedIn')->willReturn($loggedIn);
        $this->currentUserMock->method('getUserId')->willReturn($userId);
        $this->currentUserMock->method('getLogin')->willReturn($login);
        $this->currentUserMock->method('getUserData')->with('email')->willReturn($email);
    }

    /**
     * Regression for GHSA-6648-6g96-mg35: the old check called getUserById() on the
     * session user with the submitted id, so any self-consistent triple of another
     * existing account passed. The check must never touch getUserById().
     */
    public function testRemovalRequestForAnotherExistingUserIsRejected(): void
    {
        $this->sessionUserIs(3, 'attacker', 'att@x.test');
        $this->currentUserMock->expects($this->never())->method('getUserById');

        $this->assertFalse($this->isRemovalRequestForCurrentUser(7, 'victim', 'victim@example.com'));
    }

    public function testRemovalRequestForTheSessionUserIsAccepted(): void
    {
        $this->sessionUserIs(3, 'attacker', 'att@x.test');
        $this->currentUserMock->expects($this->never())->method('getUserById');

        $this->assertTrue($this->isRemovalRequestForCurrentUser(3, 'attacker', 'att@x.test'));
    }

    public function testRemovalRequestWithMismatchedLoginOrEmailIsRejected(): void
    {
        $this->sessionUserIs(3, 'attacker', 'att@x.test');

        $this->assertFalse($this->isRemovalRequestForCurrentUser(3, 'victim', 'att@x.test'));
        $this->assertFalse($this->isRemovalRequestForCurrentUser(3, 'attacker', 'victim@example.com'));
        $this->assertFalse($this->isRemovalRequestForCurrentUser(false, 'attacker', 'att@x.test'));
        $this->assertFalse($this->isRemovalRequestForCurrentUser(null, 'attacker', 'att@x.test'));
    }

    public function testRemovalRequestFromAnonymousUserIsRejected(): void
    {
        $this->sessionUserIs(-1, '', '', loggedIn: false);

        $this->assertFalse($this->isRemovalRequestForCurrentUser(-1, '', ''));
    }

    private function changePassword(string $currentPassword, string $password, string $confirm): JsonResponse|bool
    {
        $method = new ReflectionMethod(UserController::class, 'changePassword');

        return $method->invoke($this->controller, $currentPassword, $password, $confirm);
    }

    public function testNoPasswordChangeRequestedReturnsFalseAndNeverTouchesAuth(): void
    {
        $this->currentUserMock->expects($this->never())->method('verifyPassword');
        $this->currentUserMock->expects($this->never())->method('getAuthContainer');

        $this->assertFalse($this->changePassword('', '', ''));
    }

    public function testMismatchedNewPasswordsAreRejectedBeforeVerification(): void
    {
        $this->currentUserMock->expects($this->never())->method('verifyPassword');
        $this->currentUserMock->expects($this->never())->method('getAuthContainer');

        $result = $this->changePassword('current', 'NewPass123!', 'Different123!');

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertSame(Response::HTTP_CONFLICT, $result->getStatusCode());
    }

    public function testTooShortNewPasswordIsRejectedBeforeVerification(): void
    {
        $this->currentUserMock->expects($this->never())->method('verifyPassword');
        $this->currentUserMock->expects($this->never())->method('getAuthContainer');

        $result = $this->changePassword('current', 'short', 'short');

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertSame(Response::HTTP_CONFLICT, $result->getStatusCode());
    }

    public function testWrongCurrentPasswordIsForbiddenAndPasswordIsNeverWritten(): void
    {
        $this->currentUserMock->method('verifyPassword')->with('wrong-current')->willReturn(false);
        // The password write must never be reached when verification fails (CWE-620).
        $this->currentUserMock->expects($this->never())->method('getAuthContainer');

        $result = $this->changePassword('wrong-current', 'NewPass123!', 'NewPass123!');

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertSame(Response::HTTP_FORBIDDEN, $result->getStatusCode());
    }

    public function testValidCurrentPasswordWritesTheNewPassword(): void
    {
        $driver = $this->createMock(AuthDatabase::class);
        $driver->method('disableReadOnly')->willReturn(false);
        $driver->expects($this->once())->method('update')->with('jdoe', 'NewPass123!')->willReturn(true);

        $this->currentUserMock->method('verifyPassword')->with('correct-current')->willReturn(true);
        $this->currentUserMock->method('getLogin')->willReturn('jdoe');
        $this->currentUserMock->method('getAuthContainer')->willReturn([$driver]);

        $this->assertTrue($this->changePassword('correct-current', 'NewPass123!', 'NewPass123!'));
    }

    /**
     * The self-service path writes the new password through the auth drivers directly, so it
     * must revoke the remember-me token itself: a stolen pmf-remember-me cookie must not
     * survive the password rotation that was meant to lock it out.
     */
    public function testValidPasswordChangeRevokesTheRememberMeToken(): void
    {
        $driver = $this->createMock(AuthDatabase::class);
        $driver->method('disableReadOnly')->willReturn(false);
        $driver->method('update')->willReturn(true);

        $this->currentUserMock->method('verifyPassword')->with('correct-current')->willReturn(true);
        $this->currentUserMock->method('getLogin')->willReturn('jdoe');
        $this->currentUserMock->method('getAuthContainer')->willReturn([$driver]);
        $this->currentUserMock->expects($this->once())->method('revokeRememberMe')->willReturn(true);

        $this->assertTrue($this->changePassword('correct-current', 'NewPass123!', 'NewPass123!'));
    }

    public function testFailedPasswordChangeDoesNotRevokeTheRememberMeToken(): void
    {
        $driver = $this->createMock(AuthDatabase::class);
        $driver->method('disableReadOnly')->willReturn(false);
        $driver->method('update')->willReturn(false);
        $driver->method('getErrors')->willReturn('driver error');

        $this->currentUserMock->method('verifyPassword')->with('correct-current')->willReturn(true);
        $this->currentUserMock->method('getLogin')->willReturn('jdoe');
        $this->currentUserMock->method('getAuthContainer')->willReturn([$driver]);
        $this->currentUserMock->expects($this->never())->method('revokeRememberMe');

        $result = $this->changePassword('correct-current', 'NewPass123!', 'NewPass123!');
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $result->getStatusCode());
    }

    private function requireTwoFactorStepUp(
        bool $isCurrentlyEnabled,
        bool $willBeEnabled,
        string $currentPassword,
    ): ?JsonResponse {
        $method = new ReflectionMethod(UserController::class, 'requireTwoFactorStepUp');

        return $method->invoke($this->controller, $isCurrentlyEnabled, $willBeEnabled, $currentPassword);
    }

    public function testEnablingTwoFactorNeedsNoStepUp(): void
    {
        $this->currentUserMock->expects($this->never())->method('verifyPassword');

        $this->assertNull($this->requireTwoFactorStepUp(false, true, ''));
    }

    public function testLeavingTwoFactorEnabledNeedsNoStepUp(): void
    {
        $this->currentUserMock->expects($this->never())->method('verifyPassword');

        $this->assertNull($this->requireTwoFactorStepUp(true, true, ''));
    }

    public function testKeepingTwoFactorDisabledNeedsNoStepUp(): void
    {
        $this->currentUserMock->expects($this->never())->method('verifyPassword');

        $this->assertNull($this->requireTwoFactorStepUp(false, false, ''));
    }

    public function testDisablingTwoFactorWithWrongPasswordIsForbidden(): void
    {
        $this->currentUserMock->method('verifyPassword')->with('wrong')->willReturn(false);
        // The downgrade must never be written when the step-up fails (CWE-308).
        $this->currentUserMock->expects($this->never())->method('setUserData');

        $result = $this->requireTwoFactorStepUp(true, false, 'wrong');

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertSame(Response::HTTP_FORBIDDEN, $result->getStatusCode());
    }

    public function testDisablingTwoFactorWithValidPasswordIsAllowed(): void
    {
        $this->currentUserMock->method('verifyPassword')->with('correct')->willReturn(true);

        $this->assertNull($this->requireTwoFactorStepUp(true, false, 'correct'));
    }

    private function requireEmailChangeStepUp(string $newEmail, string $currentPassword): ?JsonResponse
    {
        $method = new ReflectionMethod(UserController::class, 'requireEmailChangeStepUp');

        return $method->invoke($this->controller, $newEmail, $currentPassword);
    }

    public function testUnchangedEmailNeedsNoStepUp(): void
    {
        $this->currentUserMock->method('getUserData')->with('email')->willReturn('jane@example.com');
        $this->currentUserMock->expects($this->never())->method('verifyPassword');

        $this->assertNull($this->requireEmailChangeStepUp('jane@example.com', ''));
    }

    public function testEmptyEmailNeedsNoStepUp(): void
    {
        // An invalid submitted address filters to '' upstream; it must not trip the step-up.
        $this->currentUserMock->expects($this->never())->method('verifyPassword');

        $this->assertNull($this->requireEmailChangeStepUp('', ''));
    }

    /**
     * Regression guard for the e-mail-swap account-takeover report: a hijacked session
     * must not be able to repoint the account e-mail (the password-reset trust anchor)
     * without proving the current password (CWE-620).
     */
    public function testChangingEmailWithWrongPasswordIsForbiddenAndEmailIsNeverWritten(): void
    {
        $this->currentUserMock->method('getUserData')->with('email')->willReturn('jane@example.com');
        $this->currentUserMock->method('isStepUpLockedOut')->willReturn(false);
        $this->currentUserMock->method('verifyPassword')->with('wrong')->willReturn(false);
        // The new address must never be written when the step-up fails.
        $this->currentUserMock->expects($this->never())->method('setUserData');
        $this->currentUserMock->expects($this->once())->method('stepUpFailure');

        $result = $this->requireEmailChangeStepUp('attacker@evil.example', 'wrong');

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertSame(Response::HTTP_FORBIDDEN, $result->getStatusCode());
    }

    public function testChangingEmailWithValidPasswordIsAllowed(): void
    {
        $this->currentUserMock->method('getUserData')->with('email')->willReturn('jane@example.com');
        $this->currentUserMock->method('isStepUpLockedOut')->willReturn(false);
        $this->currentUserMock->method('verifyPassword')->with('correct')->willReturn(true);
        $this->currentUserMock->expects($this->once())->method('stepUpSuccess');

        $this->assertNull($this->requireEmailChangeStepUp('attacker@evil.example', 'correct'));
    }

    public function testLockedOutAccountCannotChangeTheEmail(): void
    {
        $this->currentUserMock->method('getUserData')->with('email')->willReturn('jane@example.com');
        $this->currentUserMock->method('isStepUpLockedOut')->willReturn(true);
        $this->currentUserMock->expects($this->never())->method('verifyPassword');
        $this->currentUserMock->expects($this->never())->method('setUserData');

        $result = $this->requireEmailChangeStepUp('attacker@evil.example', 'correct');

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $result->getStatusCode());
    }

    /**
     * @return array<string, int|string>
     */
    private function verifyCurrentPassword(#[\SensitiveParameter] string $currentPassword): ?JsonResponse
    {
        $method = new ReflectionMethod(UserController::class, 'verifyCurrentPassword');

        return $method->invoke($this->controller, $currentPassword);
    }

    /**
     * Regression guard for the unthrottled step-up report: a hijacked session must not be
     * able to guess the current password without limit.
     */
    public function testLockedOutStepUpIsRejectedBeforeThePasswordIsChecked(): void
    {
        $this->currentUserMock->method('isStepUpLockedOut')->willReturn(true);
        $this->currentUserMock->expects($this->never())->method('verifyPassword');
        $this->currentUserMock->expects($this->never())->method('stepUpFailure');

        $response = $this->verifyCurrentPassword('correct');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
    }

    public function testWrongStepUpPasswordConsumesTheFailureBudget(): void
    {
        $this->currentUserMock->method('isStepUpLockedOut')->willReturn(false);
        $this->currentUserMock->method('verifyPassword')->with('wrong')->willReturn(false);
        $this->currentUserMock->expects($this->once())->method('stepUpFailure');
        $this->currentUserMock->expects($this->never())->method('stepUpSuccess');

        $response = $this->verifyCurrentPassword('wrong');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testCorrectStepUpPasswordClearsTheFailureBudget(): void
    {
        $this->currentUserMock->method('isStepUpLockedOut')->willReturn(false);
        $this->currentUserMock->method('verifyPassword')->with('correct')->willReturn(true);
        $this->currentUserMock->expects($this->never())->method('stepUpFailure');
        $this->currentUserMock->expects($this->once())->method('stepUpSuccess');

        $this->assertNull($this->verifyCurrentPassword('correct'));
    }

    public function testLockedOutAccountCannotDisableTwoFactor(): void
    {
        $this->currentUserMock->method('isStepUpLockedOut')->willReturn(true);
        $this->currentUserMock->expects($this->never())->method('verifyPassword');
        $this->currentUserMock->expects($this->never())->method('setUserData');

        $response = $this->requireTwoFactorStepUp(true, false, 'correct');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
    }

    public function testLockedOutAccountCannotChangeThePassword(): void
    {
        $this->currentUserMock->method('isStepUpLockedOut')->willReturn(true);
        $this->currentUserMock->expects($this->never())->method('verifyPassword');
        $this->currentUserMock->expects($this->never())->method('getAuthContainer');

        $response = $this->changePassword('correct-current', 'NewPass123!', 'NewPass123!');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
    }

    public function testWrongCurrentPasswordOnPasswordChangeConsumesTheFailureBudget(): void
    {
        $this->currentUserMock->method('verifyPassword')->with('wrong-current')->willReturn(false);
        $this->currentUserMock->expects($this->once())->method('stepUpFailure');

        $response = $this->changePassword('wrong-current', 'NewPass123!', 'NewPass123!');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    private function buildUserDataExport(): array
    {
        $method = new ReflectionMethod(UserController::class, 'buildUserDataExport');

        return $method->invoke($this->controller);
    }

    public function testDataExportNeverContainsTheTotpSecret(): void
    {
        // The TOTP shared secret must never appear in a user-facing export (CWE-200):
        // anyone holding the archive could otherwise generate valid 2FA codes.
        $this->currentUserMock->method('getUserId')->willReturn(2);
        $this->currentUserMock
            ->method('getUserData')
            ->willReturnMap([
                ['last_modified',     '2026-08-13 00:00:00'],
                ['display_name',      'Jane Doe'],
                ['email',             'jane@example.com'],
                ['is_visible',        1],
                ['twofactor_enabled', 1],
                ['secret',            'LIVE-TOTP-SEED'],
            ]);

        $export = $this->buildUserDataExport();

        $this->assertArrayNotHasKey('secret', $export);
        $this->assertNotContains('LIVE-TOTP-SEED', $export);
    }

    public function testDataExportOnlyContainsAllowlistedFields(): void
    {
        $this->currentUserMock->method('getUserId')->willReturn(2);
        $this->currentUserMock->method('getUserData')->willReturn('');

        $export = $this->buildUserDataExport();

        $this->assertSame(
            ['user_id', 'last_modified', 'display_name', 'email', 'is_visible', 'twofactor_enabled'],
            array_keys($export),
        );
    }
}
