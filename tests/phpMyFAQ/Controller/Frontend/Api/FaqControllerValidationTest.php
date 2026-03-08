<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Core\Exception;
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
    private function seedCategory(int $categoryId = 1): void
    {
        $query = sprintf(
            "INSERT OR REPLACE INTO faqcategories (id, lang, parent_id, name, description, user_id, group_id, active, image, show_home)
             VALUES (%d, 'en', 0, 'Test Category', 'Test Category', 1, -1, 1, '', 1)",
            $categoryId,
        );

        $this->configuration->getDb()->query($query);
    }

    private function createController(
        ?Faq $faq = null,
        ?FaqHelper $faqHelper = null,
        ?StopWords $stopWords = null,
        ?UserSession $userSession = null,
        ?CategoryHelper $categoryHelper = null,
        ?Notification $notification = null,
    ): FaqController {
        $language = $this->createStub(Language::class);
        $language->method('setLanguageFromConfiguration')->willReturn('en');
        $language->method('setLanguageWithDetection')->willReturn('en');

        return new FaqController(
            $faq ?? $this->createStub(Faq::class),
            $faqHelper ?? $this->createStub(FaqHelper::class),
            $this->createStub(Question::class),
            $stopWords ?? $this->createStub(StopWords::class),
            $userSession ?? $this->createStub(UserSession::class),
            $language,
            $categoryHelper ?? $this->createStub(CategoryHelper::class),
            $notification ?? $this->createStub(Notification::class),
        );
    }

    public function testCreateThrowsExceptionWhenPayloadIsNotAnObject(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['records.allowNewFaqsForGuests' => '1']);

        $controller = $this->createController();
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid request payload');

        $controller->create(Request::create('/api/faq/create', 'POST', content: '[]'));
    }

    public function testCreateThrowsExceptionWhenNameIsMissing(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['records.allowNewFaqsForGuests' => '1']);

        $controller = $this->createController();
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing name');

        $controller->create(Request::create('/api/faq/create', 'POST', content: json_encode([
            'email' => 'test@example.com',
            'question' => 'Test question?',
            'answer' => 'Test answer',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenQuestionIsMissing(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['records.allowNewFaqsForGuests' => '1']);

        $controller = $this->createController();
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing or empty question');

        $controller->create(Request::create('/api/faq/create', 'POST', content: json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'answer' => 'Test answer',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenQuestionIsEmpty(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['records.allowNewFaqsForGuests' => '1']);

        $controller = $this->createController();
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing or empty question');

        $controller->create(Request::create('/api/faq/create', 'POST', content: json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'question' => '   ',
            'answer' => 'Test answer',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenAnswerIsMissing(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['records.allowNewFaqsForGuests' => '1']);

        $controller = $this->createController();
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing answer');

        $controller->create(Request::create('/api/faq/create', 'POST', content: json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'question' => 'Test question?',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenEmailIsInvalid(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['records.allowNewFaqsForGuests' => '1']);

        $controller = $this->createController();
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid email address');

        $controller->create(Request::create('/api/faq/create', 'POST', content: json_encode([
            'name' => 'Test User',
            'email' => 'invalid-email',
            'question' => 'Test question?',
            'answer' => 'Test answer',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenNoCategoriesAreAvailable(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['records.allowNewFaqsForGuests' => '1']);

        $controller = $this->createController();
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No categories available');

        $controller->create(Request::create('/api/faq/create', 'POST', content: json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'question' => 'Test question?',
            'answer' => 'Test answer',
            'keywords' => 'test',
        ], JSON_THROW_ON_ERROR)));
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

        $controller = $this->createController(stopWords: $stopWords);
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
            'openQuestionID' => 0,
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
        $stopWords->expects($this->exactly(2))->method('checkBannedWord')->willReturnOnConsecutiveCalls(true, false);

        $controller = $this->createController(stopWords: $stopWords);
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

    public function testCreateReturnsSuccessWhenFaqIsCreated(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'records.allowNewFaqsForGuests' => '1',
            'records.defaultActivation' => true,
            'security.permLevel' => 'basic',
        ]);
        $this->seedCategory();

        $faq = $this->createMock(Faq::class);
        $faq
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function ($entity): bool {
                return (
                    $entity->getQuestion() === 'Test question?'
                    && $entity->getAnswer() === 'Test answer'
                    && $entity->getAuthor() === 'Test User'
                    && $entity->getEmail() === 'test@example.com'
                );
            }))
            ->willReturnCallback(static function ($entity) {
                $entity->setId(123);
                return $entity;
            });

        $faqHelper = $this->createMock(FaqHelper::class);
        $faqHelper
            ->expects($this->once())
            ->method('createFaqUrl')
            ->willReturn('https://localhost/content/1/123/en/test-question.html');

        $stopWords = $this->createStub(StopWords::class);
        $stopWords->method('checkBannedWord')->willReturn(true);

        $userSession = $this->createMock(UserSession::class);
        $userSession->expects($this->once())->method('setCurrentUser')->willReturnSelf();
        $userSession->expects($this->once())->method('userTracking')->with('save_new_entry', 0);

        $categoryHelper = $this->createMock(CategoryHelper::class);
        $categoryHelper->expects($this->once())->method('setCategory')->willReturnSelf();
        $categoryHelper->expects($this->once())->method('setConfiguration')->willReturnSelf();
        $categoryHelper->expects($this->once())->method('getModerators')->with([1])->willReturn([]);

        $notification = $this->createMock(Notification::class);
        $notification->expects($this->once())->method('sendNewFaqAdded')->with([], $this->anything());

        $controller = $this->createController(
            faq: $faq,
            faqHelper: $faqHelper,
            stopWords: $stopWords,
            userSession: $userSession,
            categoryHelper: $categoryHelper,
            notification: $notification,
        );
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

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
        self::assertSame('https://localhost/content/1/123/en/test-question.html', $payload['link']);
    }

    public function testCreateReturnsSuccessWithoutRedirectWhenFaqIsInactiveByDefault(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'records.allowNewFaqsForGuests' => '1',
            'records.defaultActivation' => false,
            'security.permLevel' => 'basic',
        ]);
        $this->seedCategory();

        $faq = $this->createMock(Faq::class);
        $faq
            ->expects($this->once())
            ->method('create')
            ->willReturnCallback(static function ($entity) {
                $entity->setId(124);
                return $entity;
            });

        $faqHelper = $this->createMock(FaqHelper::class);
        $faqHelper->expects($this->never())->method('createFaqUrl');

        $stopWords = $this->createStub(StopWords::class);
        $stopWords->method('checkBannedWord')->willReturn(true);

        $userSession = $this->createMock(UserSession::class);
        $userSession->expects($this->once())->method('setCurrentUser')->willReturnSelf();
        $userSession->expects($this->once())->method('userTracking')->with('save_new_entry', 0);

        $categoryHelper = $this->createMock(CategoryHelper::class);
        $categoryHelper->expects($this->once())->method('setCategory')->willReturnSelf();
        $categoryHelper->expects($this->once())->method('setConfiguration')->willReturnSelf();
        $categoryHelper->expects($this->once())->method('getModerators')->with([1])->willReturn([]);

        $notification = $this->createMock(Notification::class);
        $notification->expects($this->once())->method('sendNewFaqAdded')->with([], $this->anything());

        $controller = $this->createController(
            faq: $faq,
            faqHelper: $faqHelper,
            stopWords: $stopWords,
            userSession: $userSession,
            categoryHelper: $categoryHelper,
            notification: $notification,
        );
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $this->createSession());

        $request = Request::create('/api/faq/create', 'POST', content: json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'question' => 'Inactive question?',
            'answer' => 'Inactive answer',
            'keywords' => 'test',
            'rubrik' => [1],
            'openQuestionID' => 0,
            'captcha' => 'ignored-for-logged-in-user',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
        self::assertArrayNotHasKey('link', $payload);
        self::assertArrayNotHasKey('info', $payload);
    }

    public function testCreateDeletesOpenQuestionWhenDeleteQuestionIsEnabled(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'records.allowNewFaqsForGuests' => '1',
            'records.defaultActivation' => false,
            'records.enableDeleteQuestion' => true,
            'security.permLevel' => 'basic',
        ]);
        $this->seedCategory();

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('create')->willReturnCallback(static function ($entity) {
            $entity->setId(125);
            return $entity;
        });

        $question = $this->createMock(Question::class);
        $question->expects($this->once())->method('delete')->with(55);
        $question->expects($this->never())->method('updateQuestionAnswer');

        $stopWords = $this->createStub(StopWords::class);
        $stopWords->method('checkBannedWord')->willReturn(true);

        $userSession = $this->createMock(UserSession::class);
        $userSession->expects($this->once())->method('setCurrentUser')->willReturnSelf();
        $userSession->expects($this->once())->method('userTracking')->with('save_new_entry', 0);

        $categoryHelper = $this->createMock(CategoryHelper::class);
        $categoryHelper->expects($this->once())->method('setCategory')->willReturnSelf();
        $categoryHelper->expects($this->once())->method('setConfiguration')->willReturnSelf();
        $categoryHelper->expects($this->once())->method('getModerators')->with([1])->willReturn([]);

        $notification = $this->createMock(Notification::class);
        $notification->expects($this->once())->method('sendNewFaqAdded')->with([], $this->anything());

        $language = $this->createStub(Language::class);
        $language->method('setLanguageFromConfiguration')->willReturn('en');
        $language->method('setLanguageWithDetection')->willReturn('en');

        $controller = new FaqController(
            $faq,
            $this->createStub(FaqHelper::class),
            $question,
            $stopWords,
            $userSession,
            $language,
            $categoryHelper,
            $notification,
        );
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $this->createSession());

        $request = Request::create('/api/faq/create', 'POST', content: json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'question' => 'Question from open item?',
            'answer' => 'Answer',
            'keywords' => 'test',
            'rubrik' => [1],
            'openQuestionID' => 55,
            'captcha' => 'ignored-for-logged-in-user',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
    }

    public function testCreateUpdatesOpenQuestionWhenDeleteQuestionIsDisabled(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'records.allowNewFaqsForGuests' => '1',
            'records.defaultActivation' => false,
            'records.enableDeleteQuestion' => false,
            'security.permLevel' => 'basic',
        ]);
        $this->seedCategory();

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('create')->willReturnCallback(static function ($entity) {
            $entity->setId(126);
            return $entity;
        });

        $question = $this->createMock(Question::class);
        $question->expects($this->never())->method('delete');
        $question->expects($this->once())->method('updateQuestionAnswer')->with(77, 126, 1);

        $stopWords = $this->createStub(StopWords::class);
        $stopWords->method('checkBannedWord')->willReturn(true);

        $userSession = $this->createMock(UserSession::class);
        $userSession->expects($this->once())->method('setCurrentUser')->willReturnSelf();
        $userSession->expects($this->once())->method('userTracking')->with('save_new_entry', 0);

        $categoryHelper = $this->createMock(CategoryHelper::class);
        $categoryHelper->expects($this->once())->method('setCategory')->willReturnSelf();
        $categoryHelper->expects($this->once())->method('setConfiguration')->willReturnSelf();
        $categoryHelper->expects($this->once())->method('getModerators')->with([1])->willReturn([]);

        $notification = $this->createMock(Notification::class);
        $notification->expects($this->once())->method('sendNewFaqAdded')->with([], $this->anything());

        $language = $this->createStub(Language::class);
        $language->method('setLanguageFromConfiguration')->willReturn('en');
        $language->method('setLanguageWithDetection')->willReturn('en');

        $controller = new FaqController(
            $faq,
            $this->createStub(FaqHelper::class),
            $question,
            $stopWords,
            $userSession,
            $language,
            $categoryHelper,
            $notification,
        );
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $this->createSession());

        $request = Request::create('/api/faq/create', 'POST', content: json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'question' => 'Question from open item?',
            'answer' => 'Answer',
            'keywords' => 'test',
            'rubrik' => [1],
            'openQuestionID' => 77,
            'captcha' => 'ignored-for-logged-in-user',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
    }
}
