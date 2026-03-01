<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Helper\QuestionHelper;
use phpMyFAQ\Notification;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Question;
use phpMyFAQ\Search;
use phpMyFAQ\StopWords;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(QuestionController::class)]
#[UsesNamespace('phpMyFAQ')]
final class QuestionControllerValidationTest extends ApiControllerTestCase
{
    private function createController(): QuestionController
    {
        return new QuestionController(
            $this->createStub(StopWords::class),
            $this->createStub(QuestionHelper::class),
            $this->createStub(Search::class),
            $this->createStub(Question::class),
            $this->createStub(Notification::class),
        );
    }

    public function testCreateReturnsForbiddenWhenQuestionSubmissionIsDisabled(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'records.allowQuestionsForGuests' => '0',
            'main.enableAskQuestions' => '0',
        ]);

        $controller = $this->createController();
        $currentUser = $this->createAuthenticatedUserMock(-1);
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => false]);
        $this->injectControllerState($controller, $currentUser, $this->createSession());

        $request = Request::create('/api/question/create', 'POST', content: json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'lang' => 'en',
            'question' => 'How does this work?',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
    }

    public function testCreateReturnsBadRequestWhenCaptchaValidationFails(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'records.allowQuestionsForGuests' => '1',
            'main.enableSmartAnswering' => '1',
        ]);

        $controller = $this->createController();
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $this->createSession());

        $request = Request::create('/api/question/create', 'POST', content: json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'lang' => 'en',
            'question' => 'How does this work?',
            'captcha' => 'invalid',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
    }
}
