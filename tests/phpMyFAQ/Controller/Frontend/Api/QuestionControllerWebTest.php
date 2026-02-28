<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(QuestionController::class)]
#[UsesNamespace('phpMyFAQ')]
final class QuestionControllerWebTest extends ControllerWebTestCase
{
    public function testQuestionCreateReturnsForbiddenWhenGuestQuestionsAreDisabled(): void
    {
        $this->overrideConfigurationValues([
            'records.allowQuestionsForGuests' => 0,
            'main.enableAskQuestions' => false,
        ], 'api');

        $response = $this->requestApiJson('POST', '/question/create', []);

        self::assertResponseStatusCodeSame(403, $response);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }
}
