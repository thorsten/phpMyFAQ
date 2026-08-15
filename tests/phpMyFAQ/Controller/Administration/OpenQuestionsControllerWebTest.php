<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(OpenQuestionsController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class OpenQuestionsControllerWebTest extends ControllerWebTestCase
{
    public function testOpenQuestionsPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/questions');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="phpmyfaq-open-questions"', $response);
    }

    public function testQuestionHistoryPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/questions/history/1');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('bi-clock-history', $response);
    }

    public function testQuestionHistoryPageRedirectsGuestToLogin(): void
    {
        $response = $this->requestAdminGuest('GET', '/questions/history/1');

        self::assertResponseStatusCodeSame(302, $response);
        self::assertRedirectLocationContains('/login', $response);
    }
}
