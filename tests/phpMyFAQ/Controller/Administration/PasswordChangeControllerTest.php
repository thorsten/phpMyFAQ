<?php

/**
 * Administration PasswordChangeController Test — password step-up throttle.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Controller\Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-11-08
 */

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Auth\AuthDatabase;
use phpMyFAQ\Auth\AuthException;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

#[AllowMockObjectsWithoutExpectations]
class PasswordChangeControllerTest extends TestCase
{
    private PasswordChangeController $controller;
    private CurrentUser $currentUserMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currentUserMock = $this->createMock(CurrentUser::class);
        $this->currentUserMock->method('getLogin')->willReturn('editor');

        $reflection = new ReflectionClass(PasswordChangeController::class);
        $this->controller = $reflection->newInstanceWithoutConstructor();

        $currentUserProp = $reflection->getParentClass()->getParentClass()->getProperty('currentUser');
        $currentUserProp->setValue($this->controller, $this->currentUserMock);
    }

    private function verifyCurrentPassword(AuthDatabase $authSource, string $currentPassword): bool
    {
        $method = new ReflectionMethod(PasswordChangeController::class, 'verifyCurrentPassword');

        return $method->invoke($this->controller, $authSource, $currentPassword);
    }

    /**
     * Regression guard: the admin password form shares the account's login failure
     * budget, so a hijacked admin session cannot guess the current password without
     * limit (CWE-307).
     */
    public function testWrongCurrentPasswordConsumesTheFailureBudget(): void
    {
        $driver = $this->createMock(AuthDatabase::class);
        $driver->method('checkCredentials')->willThrowException(new AuthException('ERROR_USER_INCORRECT_PASSWORD'));

        $this->currentUserMock->expects($this->once())->method('stepUpFailure')->willReturn(true);
        $this->currentUserMock->expects($this->never())->method('stepUpSuccess');
        $this->currentUserMock->expects($this->never())->method('changePassword');

        $this->assertFalse($this->verifyCurrentPassword($driver, 'wrong'));
    }

    public function testFalseReturningDriverAlsoConsumesTheFailureBudget(): void
    {
        $driver = $this->createMock(AuthDatabase::class);
        $driver->method('checkCredentials')->willReturn(false);

        $this->currentUserMock->expects($this->once())->method('stepUpFailure')->willReturn(true);
        $this->currentUserMock->expects($this->never())->method('stepUpSuccess');

        $this->assertFalse($this->verifyCurrentPassword($driver, 'wrong'));
    }

    public function testCorrectCurrentPasswordClearsTheFailureBudget(): void
    {
        $driver = $this->createMock(AuthDatabase::class);
        $driver->expects($this->once())
            ->method('checkCredentials')
            ->with('editor', 'Correct#Pass1')
            ->willReturn(true);

        $this->currentUserMock->expects($this->once())->method('stepUpSuccess')->willReturn(true);
        $this->currentUserMock->expects($this->never())->method('stepUpFailure');

        $this->assertTrue($this->verifyCurrentPassword($driver, 'Correct#Pass1'));
    }
}
