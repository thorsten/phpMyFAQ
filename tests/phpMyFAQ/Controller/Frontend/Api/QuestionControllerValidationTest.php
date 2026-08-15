<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Helper\QuestionHelper;
use phpMyFAQ\Notification;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Question;
use phpMyFAQ\Search;
use phpMyFAQ\Session\Token;
use phpMyFAQ\StopWords;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(QuestionController::class)]
#[UsesNamespace('phpMyFAQ')]
final class QuestionControllerValidationTest extends ApiControllerTestCase
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

    private function seedFaqUserPermission(int $recordId = 1, int $userId = 1): void
    {
        $query = sprintf(
            'INSERT OR REPLACE INTO faqdata_user (record_id, user_id) VALUES (%d, %d)',
            $recordId,
            $userId,
        );

        $this->configuration->getDb()->query($query);
    }

    private function createController(
        ?StopWords $stopWords = null,
        ?QuestionHelper $questionHelper = null,
        ?Search $search = null,
        ?Question $question = null,
        ?Notification $notification = null,
    ): QuestionController {
        return new QuestionController(
            $stopWords ?? $this->createStub(StopWords::class),
            $questionHelper ?? $this->createStub(QuestionHelper::class),
            $search ?? $this->createStub(Search::class),
            $question ?? $this->createStub(Question::class),
            $notification ?? $this->createStub(Notification::class),
        );
    }

    /**
     * @return array{0: \Symfony\Component\HttpFoundation\Session\Session, 1: string}
     */
    private function createValidCsrfSession(): array
    {
        $session = $this->createSession();
        $csrfToken = Token::getInstance($session)->getTokenString('ask-question');
        $_COOKIE[sprintf('%s-%s', Token::PMF_SESSION_NAME, substr(md5('ask-question'), 0, 10))] = $csrfToken;

        return [$session, $csrfToken];
    }

    private function createGuestUserStub(): CurrentUser
    {
        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(false);
        $currentUser->method('getUserId')->willReturn(-1);

        return $currentUser;
    }

    public function testCreateThrowsExceptionWhenNameIsMissing(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['main.enableAskQuestions' => '1']);

        $controller = $this->createController();
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing name');

        $controller->create(Request::create('/api/question/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'email' => 'test@example.com',
            'lang' => 'en',
            'question' => 'How does this work?',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenEmailIsMissing(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['main.enableAskQuestions' => '1']);

        $controller = $this->createController();
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing email');

        $controller->create(Request::create('/api/question/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'name' => 'Test User',
            'lang' => 'en',
            'question' => 'How does this work?',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenLanguageIsMissing(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['main.enableAskQuestions' => '1']);

        $controller = $this->createController();
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing language');

        $controller->create(Request::create('/api/question/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'question' => 'How does this work?',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenQuestionIsMissing(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['main.enableAskQuestions' => '1']);

        $controller = $this->createController();
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing or empty question');

        $controller->create(Request::create('/api/question/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'lang' => 'en',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenQuestionIsEmpty(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['main.enableAskQuestions' => '1']);

        $controller = $this->createController();
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing or empty question');

        $controller->create(Request::create('/api/question/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'lang' => 'en',
            'question' => '',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenEmailIsInvalid(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['main.enableAskQuestions' => '1']);

        $controller = $this->createController();
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid email address');

        $controller->create(Request::create('/api/question/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'name' => 'Test User',
            'email' => 'invalid-email',
            'lang' => 'en',
            'question' => 'How does this work?',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenCategoryParameterIsProvided(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['main.enableAskQuestions' => '1']);

        $controller = $this->createController();
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Category validation failed');

        $controller->create(Request::create('/api/question/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'lang' => 'en',
            'question' => 'How does this work?',
            'category' => 1,
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenSaveParameterIsProvided(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['main.enableAskQuestions' => '1']);

        $controller = $this->createController();
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Save parameter not allowed');

        $controller->create(Request::create('/api/question/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'lang' => 'en',
            'question' => 'How does this work?',
            'save' => 1,
        ], JSON_THROW_ON_ERROR)));
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
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $this->injectControllerState($controller, $currentUser, $session);

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
            'main.enableAskQuestions' => '1',
        ]);

        $controller = $this->createController();
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $this->injectControllerState($controller, $currentUser, $session);

        $request = Request::create('/api/question/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
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

    public function testCreateStoresQuestionImmediatelyWhenSmartAnsweringIsDisabled(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'records.allowQuestionsForGuests' => '1',
            'main.enableSmartAnswering' => '0',
            'main.enableAskQuestions' => '1',
        ]);
        $this->seedCategory();

        $stopWords = $this->createStub(StopWords::class);
        $stopWords->method('checkBannedWord')->willReturn(true);

        $question = $this->createMock(Question::class);
        $question
            ->expects($this->once())
            ->method('add')
            ->with($this->callback(static function ($entity): bool {
                return (
                    $entity->getUsername() === 'Test User'
                    && $entity->getEmail() === 'test@example.com'
                    && $entity->getQuestion() === 'How does this work?'
                );
            }));

        $notification = $this->createMock(Notification::class);
        $notification->expects($this->once())->method('sendQuestionSuccessMail');

        $controller = $this->createController(stopWords: $stopWords, question: $question, notification: $notification);
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $this->injectControllerState($controller, $currentUser, $session);

        $request = Request::create('/api/question/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'lang' => 'en',
            'question' => 'How does this work?',
            'captcha' => 'ignored-for-logged-in-user',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
    }

    public function testCreateReturnsSmartAnswerWhenSearchFindsMatches(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'records.allowQuestionsForGuests' => '1',
            'main.enableSmartAnswering' => '1',
            'main.enableAskQuestions' => '1',
            'security.permLevel' => 'basic',
        ]);
        $this->seedCategory();
        $this->seedFaqUserPermission(1, 1);
        $this->seedFaqUserPermission();

        $stopWords = $this->createMock(StopWords::class);
        $stopWords->method('checkBannedWord')->willReturn(true);
        $stopWords->expects($this->once())->method('clean')->with('How does this work?')->willReturn(['How']);

        $search = $this->createMock(Search::class);
        $search->expects($this->once())->method('setCategory');
        $search->expects($this->once())->method('setCategoryId')->with($this->greaterThan(0));
        $search
            ->expects($this->once())
            ->method('search')
            ->with('How', false)
            ->willReturn([(object) [
                'id' => 1,
                'category_id' => 1,
                'lang' => 'en',
                'question' => 'Existing FAQ',
                'answer' => 'Existing answer',
                'score' => 50,
            ]]);

        $questionHelper = $this->createMock(QuestionHelper::class);
        $questionHelper->expects($this->once())->method('setConfiguration')->willReturnSelf();
        $questionHelper->expects($this->once())->method('setCategory')->willReturnSelf();
        $questionHelper
            ->expects($this->once())
            ->method('generateSmartAnswer')
            ->willReturn('<ul><li>Existing FAQ</li></ul>');

        $question = $this->createMock(Question::class);
        $question->expects($this->never())->method('add');

        $notification = $this->createMock(Notification::class);
        $notification->expects($this->never())->method('sendQuestionSuccessMail');

        $controller = $this->createController(
            stopWords: $stopWords,
            questionHelper: $questionHelper,
            search: $search,
            question: $question,
            notification: $notification,
        );
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $this->injectControllerState($controller, $currentUser, $session);

        $request = Request::create('/api/question/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'lang' => 'en',
            'question' => 'How does this work?',
            'captcha' => 'ignored-for-logged-in-user',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('<ul><li>Existing FAQ</li></ul>', $payload['result']);
        self::assertTrue($session->get('pmf-ask-question-captcha-verified'));
    }

    public function testCreateStoresQuestionWhenSmartAnsweringFindsNoMatches(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'records.allowQuestionsForGuests' => '1',
            'main.enableSmartAnswering' => '1',
            'main.enableAskQuestions' => '1',
            'security.permLevel' => 'basic',
        ]);
        $this->seedCategory();

        $stopWords = $this->createMock(StopWords::class);
        $stopWords->method('checkBannedWord')->willReturn(true);
        $stopWords->expects($this->once())->method('clean')->with('How does this work?')->willReturn(['How']);

        $search = $this->createMock(Search::class);
        $search->expects($this->once())->method('setCategory');
        $search->expects($this->once())->method('setCategoryId')->with($this->greaterThan(0));
        $search->expects($this->once())->method('search')->with('How', false)->willReturn([]);

        $questionHelper = $this->createMock(QuestionHelper::class);
        $questionHelper->expects($this->once())->method('setConfiguration')->willReturnSelf();
        $questionHelper->expects($this->once())->method('setCategory')->willReturnSelf();
        $questionHelper->expects($this->never())->method('generateSmartAnswer');

        $question = $this->createMock(Question::class);
        $question->expects($this->once())->method('add');

        $notification = $this->createMock(Notification::class);
        $notification->expects($this->once())->method('sendQuestionSuccessMail');

        $controller = $this->createController(
            stopWords: $stopWords,
            questionHelper: $questionHelper,
            search: $search,
            question: $question,
            notification: $notification,
        );
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $this->injectControllerState($controller, $currentUser, $session);

        $request = Request::create('/api/question/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'lang' => 'en',
            'question' => 'How does this work?',
            'captcha' => 'ignored-for-logged-in-user',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
    }

    public function testCreateReturnsBadRequestWhenStopWordValidationFails(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'records.allowQuestionsForGuests' => '1',
            'main.enableSmartAnswering' => '0',
            'main.enableAskQuestions' => '1',
        ]);
        $this->seedCategory();

        $stopWords = $this->createStub(StopWords::class);
        $stopWords->method('checkBannedWord')->willReturn(false);

        $question = $this->createMock(Question::class);
        $question->expects($this->never())->method('add');

        $notification = $this->createMock(Notification::class);
        $notification->expects($this->never())->method('sendQuestionSuccessMail');

        $controller = $this->createController(stopWords: $stopWords, question: $question, notification: $notification);
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $this->injectControllerState($controller, $currentUser, $session);

        $request = Request::create('/api/question/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'lang' => 'en',
            'question' => 'Blocked content',
            'captcha' => 'ignored-for-logged-in-user',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
    }

    public function testCreateThrowsExceptionWhenCsrfTokenIsMissing(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['main.enableAskQuestions' => '1']);

        $controller = $this->createController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing CSRF token');

        $controller->create(Request::create('/api/question/create', 'POST', content: json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'lang' => 'en',
            'question' => 'How does this work?',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenCsrfTokenIsInvalid(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['main.enableAskQuestions' => '1']);

        $controller = $this->createController();
        [$session] = $this->createValidCsrfSession();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid CSRF token');

        $controller->create(Request::create('/api/question/create', 'POST', content: json_encode([
            'pmf-csrf-token' => 'invalid-token',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'lang' => 'en',
            'question' => 'How does this work?',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateWithStoreNowRequiresCaptchaWithoutSmartAnswerPhase(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'records.allowQuestionsForGuests' => '1',
            'main.enableSmartAnswering' => '0',
            'main.enableAskQuestions' => '1',
            'spam.enableCaptchaCode' => 'true',
        ]);
        $this->seedCategory();

        $question = $this->createMock(Question::class);
        $question->expects($this->never())->method('add');

        $notification = $this->createMock(Notification::class);
        $notification->expects($this->never())->method('sendQuestionSuccessMail');

        $controller = $this->createController(question: $question, notification: $notification);
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $this->injectControllerState($controller, $this->createGuestUserStub(), $session);

        $request = Request::create('/api/question/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'name' => 'Anonymous Spammer',
            'email' => 'spam@example.com',
            'lang' => 'en',
            'question' => 'Unsolicited question',
            'store' => 'now',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get(key: 'msgCaptcha'), $payload['error']);
    }

    public function testCreateWithStoreNowStoresQuestionOnlyOnceAfterSmartAnswerPhase(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'records.allowQuestionsForGuests' => '1',
            'main.enableSmartAnswering' => '0',
            'main.enableAskQuestions' => '1',
            'spam.enableCaptchaCode' => 'true',
        ]);
        $this->seedCategory();

        $stopWords = $this->createStub(StopWords::class);
        $stopWords->method('checkBannedWord')->willReturn(true);

        $question = $this->createMock(Question::class);
        $question->expects($this->once())->method('add');

        $notification = $this->createMock(Notification::class);
        $notification->expects($this->once())->method('sendQuestionSuccessMail');

        $controller = $this->createController(stopWords: $stopWords, question: $question, notification: $notification);
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $this->injectControllerState($controller, $this->createGuestUserStub(), $session);

        // The smart-answer phase arms this one-time flag after a successful captcha check
        $session->set('pmf-ask-question-captcha-verified', true);

        $request = Request::create('/api/question/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'lang' => 'en',
            'question' => 'How does this work?',
            'store' => 'now',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
        self::assertNull($session->get('pmf-ask-question-captcha-verified'));

        // Replaying the same request must fail: the one-time flag was consumed
        $replayResponse = $controller->create($request);
        $replayPayload = json_decode((string) $replayResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $replayResponse->getStatusCode());
        self::assertSame(Translation::get(key: 'msgCaptcha'), $replayPayload['error']);
    }
}
