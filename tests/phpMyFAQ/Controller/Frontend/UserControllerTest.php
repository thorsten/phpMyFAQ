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

    /**
     * @return array<string, int|string>
     */
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
        $this->currentUserMock->method('getUserData')->willReturnMap([
            ['last_modified', '2026-08-13 00:00:00'],
            ['display_name', 'Jane Doe'],
            ['email', 'jane@example.com'],
            ['is_visible', 1],
            ['twofactor_enabled', 1],
            ['secret', 'LIVE-TOTP-SEED'],
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
