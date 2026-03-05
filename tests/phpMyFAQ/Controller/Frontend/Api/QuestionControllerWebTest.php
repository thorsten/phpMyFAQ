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
        $this->getConfiguration('api')->getAll();
        $this->overrideConfigurationValues([
            'records.allowQuestionsForGuests' => 0,
            'main.enableAskQuestions' => false,
        ], 'api');

        $response = $this->requestApiJson('POST', '/question/create', [
            'name' => 'Anonymous User',
            'email' => 'anonymous@example.com',
            'lang' => 'en',
            'question' => 'How does this work?',
            'category' => '',
        ]);

        self::assertContains($response->getStatusCode(), [400, 403]);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }
}
