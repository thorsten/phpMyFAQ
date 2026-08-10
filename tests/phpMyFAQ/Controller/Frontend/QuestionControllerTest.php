<?php

/**
 * Frontend QuestionController Test — ask-question authorization guard.
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
 * @since     2026-08-10
 */

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Configuration;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[AllowMockObjectsWithoutExpectations]
class QuestionControllerTest extends TestCase
{
    private QuestionController $controller;
    private Configuration $configurationMock;
    private CurrentUser $currentUserMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurationMock = $this->createMock(Configuration::class);
        $this->currentUserMock = $this->createMock(CurrentUser::class);

        $reflection = new ReflectionClass(QuestionController::class);
        $this->controller = $reflection->newInstanceWithoutConstructor();

        $configProp = $reflection->getParentClass()->getProperty('configuration');
        $configProp->setValue($this->controller, $this->configurationMock);

        $currentUserProp = $reflection->getParentClass()->getProperty('currentUser');
        $currentUserProp->setValue($this->controller, $this->currentUserMock);
    }

    private function isAddingQuestionsAllowed(): bool
    {
        $method = new \ReflectionMethod(QuestionController::class, 'isAddingQuestionsAllowed');

        return $method->invoke($this->controller);
    }

    /**
     * @return array<string, array{int, bool, bool, bool}>
     */
    public static function authorizationProvider(): array
    {
        // [userId, enableAskQuestions, allowQuestionsForGuests, expectedAllowed]
        return [
            'feature disabled blocks guests' => [-1, false, true, false],
            'feature disabled blocks logged-in users' => [1, false, true, false],
            'guest denied when guest questions disabled' => [-1, true, false, false],
            'guest allowed when guest questions enabled' => [-1, true, true, true],
            'logged-in user allowed regardless of guest setting' => [1, true, false, true],
        ];
    }

    #[DataProvider('authorizationProvider')]
    public function testIsAddingQuestionsAllowed(
        int $userId,
        bool $enableAskQuestions,
        bool $allowQuestionsForGuests,
        bool $expected
    ): void {
        $this->currentUserMock->method('getUserId')->willReturn($userId);
        $this->configurationMock
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'main.enableAskQuestions' => $enableAskQuestions,
                'records.allowQuestionsForGuests' => $allowQuestionsForGuests,
                default => null,
            });

        $this->assertSame($expected, $this->isAddingQuestionsAllowed());
    }
}
