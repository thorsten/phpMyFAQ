<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Faq;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Helper\FaqHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Notification;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Question;
use phpMyFAQ\StopWords;
use phpMyFAQ\User\UserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(FaqController::class)]
#[UsesNamespace('phpMyFAQ')]
final class FaqControllerValidationTest extends ApiControllerTestCase
{
    private function createController(?StopWords $stopWords = null): FaqController
    {
        $language = $this->createStub(Language::class);
        $language->method('setLanguageFromConfiguration')->willReturn('en');
        $language->method('setLanguageWithDetection')->willReturn('en');

        return new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(FaqHelper::class),
            $this->createStub(Question::class),
            $stopWords ?? $this->createStub(StopWords::class),
            $this->createStub(UserSession::class),
            $language,
            $this->createStub(CategoryHelper::class),
            $this->createStub(Notification::class),
        );
    }

    public function testCreateReturnsForbiddenWhenFaqSubmissionIsDisabled(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['records.allowNewFaqsForGuests' => '0']);

        $controller = $this->createController();
        $currentUser = $this->createAuthenticatedUserMock(-1);
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => false]);
        $this->injectControllerState($controller, $currentUser, $this->createSession());

        $response = $controller->create(Request::create('/api/faq/create', 'POST', content: json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'question' => 'Test question?',
            'answer' => 'Test answer',
            'keywords' => 'test',
        ], JSON_THROW_ON_ERROR)));

        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
    }

    public function testCreateReturnsBadRequestWhenCaptchaValidationFails(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['records.allowNewFaqsForGuests' => '1']);

        $controller = $this->createController();
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $this->createSession());

        $request = Request::create('/api/faq/create', 'POST', content: json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'question' => 'Test question?',
            'answer' => 'Test answer',
            'keywords' => 'test',
            'rubrik' => [1],
            'captcha' => 'invalid',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
    }

    public function testCreateReturnsBadRequestWhenQuestionTextFailsStopWordCheck(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['records.allowNewFaqsForGuests' => '1']);

        $stopWords = $this->createStub(StopWords::class);
        $stopWords->method('checkBannedWord')->willReturn(false);

        $controller = $this->createController($stopWords);
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $this->createSession());

        $request = Request::create('/api/faq/create', 'POST', content: json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'question' => 'Test question?',
            'answer' => 'Test answer',
            'keywords' => 'test',
            'rubrik' => [1],
            'captcha' => 'ignored-for-logged-in-user',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
    }

    public function testCreateReturnsBadRequestWhenAnswerFailsStopWordCheck(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['records.allowNewFaqsForGuests' => '1']);

        $stopWords = $this->createMock(StopWords::class);
        $stopWords->expects($this->exactly(2))
            ->method('checkBannedWord')
            ->willReturnOnConsecutiveCalls(true, false);

        $controller = $this->createController($stopWords);
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $this->createSession());

        $request = Request::create('/api/faq/create', 'POST', content: json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'question' => 'Test question?',
            'answer' => 'Answer with blocked content',
            'keywords' => 'test',
            'rubrik' => [1],
            'captcha' => 'ignored-for-logged-in-user',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
    }
}
