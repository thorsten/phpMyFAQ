<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Faq;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Helper\FaqHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Notification;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Question;
use phpMyFAQ\Question\QuestionHistoryRepository;
use phpMyFAQ\StopWords;
use phpMyFAQ\Strings;
use phpMyFAQ\TestDatabaseTrait;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\UserSession;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Throwable;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(FaqController::class)]
#[UsesNamespace('phpMyFAQ')]
class FaqControllerTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;
    private Faq $faq;
    private FaqHelper $faqHelper;
    private Question $question;
    private StopWords $stopWords;
    private UserSession $userSession;
    private Language $language;
    private CategoryHelper $categoryHelper;
    private Notification $notification;
    private QuestionHistoryRepository $questionHistory;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->configuration = $this->createTestConfiguration();

        $this->faq = $this->createStub(Faq::class);
        $this->faqHelper = $this->createStub(FaqHelper::class);
        $this->question = $this->createStub(Question::class);
        $this->stopWords = $this->createStub(StopWords::class);
        $this->userSession = $this->createStub(UserSession::class);
        $this->language = $this->createStub(Language::class);
        $this->categoryHelper = $this->createStub(CategoryHelper::class);
        $this->notification = $this->createStub(Notification::class);
        $this->questionHistory = $this->createStub(QuestionHistoryRepository::class);
    }

    private function createController(): FaqController
    {
        return new FaqController(
            $this->faq,
            $this->faqHelper,
            $this->question,
            $this->stopWords,
            $this->userSession,
            $this->language,
            $this->categoryHelper,
            $this->notification,
            $this->questionHistory,
        );
    }

    /**
     * @throws \JsonException
     */
    public function testCreateWithInvalidJsonThrowsException(): void
    {
        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testCreateWithMissingNameThrowsException(): void
    {
        $requestData = json_encode([
            'email' => 'test@example.com',
            'question' => 'Test question?',
            'answer' => 'Test answer',
            'keywords' => 'test',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testCreateWithInvalidEmailThrowsException(): void
    {
        $requestData = json_encode([
            'name' => 'Test User',
            'email' => 'invalid-email',
            'question' => 'Test question?',
            'answer' => 'Test answer',
            'keywords' => 'test',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testCreateWithEmptyQuestionThrowsException(): void
    {
        $requestData = json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'question' => '',
            'answer' => 'Test answer',
            'keywords' => 'test',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testCreateWithMissingAnswerThrowsException(): void
    {
        $requestData = json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'question' => 'Test question?',
            'keywords' => 'test',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws \Exception
     */
    public function testCreateDetectsTheLanguageWhenDetectionIsEnabled(): void
    {
        self::assertTrue($this->configuration->set('main.languageDetection', 'true'));
        self::assertTrue($this->configuration->set('records.allowNewFaqsForGuests', 'false'));

        $language = $this->createMock(Language::class);
        $language->expects($this->once())->method('setLanguageWithDetection')->willReturn('en');
        $language->expects($this->never())->method('setLanguageFromConfiguration');
        $this->language = $language;

        $controller = $this->createController();
        $this->injectControllerState($controller, $this->createUser(loggedIn: false, mayAddFaqs: false));

        $response = $controller->create($this->createFaqRequest());

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    /**
     * @throws \JsonException
     */
    public function testCreateUsesTheConfiguredLanguageWhenDetectionIsDisabled(): void
    {
        self::assertTrue($this->configuration->set('main.languageDetection', 'false'));
        self::assertTrue($this->configuration->set('records.allowNewFaqsForGuests', 'false'));

        $language = $this->createMock(Language::class);
        $language->expects($this->once())->method('setLanguageFromConfiguration')->with('en')->willReturn('en');
        $language->expects($this->never())->method('setLanguageWithDetection');
        $this->language = $language;

        $controller = $this->createController();
        $this->injectControllerState($controller, $this->createUser(loggedIn: false, mayAddFaqs: false));

        $response = $controller->create($this->createFaqRequest());

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    /**
     * Without a category in the payload the FAQ is filed under the first existing category.
     *
     * @throws \Exception
     */
    public function testCreateFallsBackToTheFirstExistingCategoryWhenNoneIsGiven(): void
    {
        self::assertTrue($this->configuration->set('records.defaultActivation', 'true'));
        $this->configuration
            ->getDb()
            ->query("INSERT INTO faqcategories (id, lang, parent_id, name, description, user_id, group_id, active, image, show_home)
             VALUES (7, 'en', 0, 'Fallback category', '', 1, -1, 1, '', 0)");

        $this->stopWords = $this->createStub(StopWords::class);
        $this->stopWords->method('checkBannedWord')->willReturn(true);

        $faq = $this->createMock(Faq::class);
        $faq
            ->expects($this->once())
            ->method('create')
            ->willReturnCallback(static fn(FaqEntity $faqEntity): FaqEntity => $faqEntity->setId(4711));
        $this->faq = $faq;

        $this->faqHelper = $this->createMock(FaqHelper::class);
        $this->faqHelper
            ->expects($this->once())
            ->method('createFaqUrl')
            ->with(self::isInstanceOf(FaqEntity::class), 7)
            ->willReturn('https://faq.example.com/content/7/4711/en/q.html');

        $controller = $this->createController();
        $this->injectControllerState($controller, $this->createUser(loggedIn: true, mayAddFaqs: true));

        $response = $controller->create($this->createFaqRequest());

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('https://faq.example.com/content/7/4711/en/q.html', $this->decodePayload($response)['link']);

        $result = $this->configuration
            ->getDb()
            ->query('SELECT category_id FROM faqcategoryrelations WHERE record_id = 4711');
        self::assertSame(7, (int) $this->configuration->getDb()->fetchArray($result)['category_id']);
    }

    /**
     * An answer that is only "0" is stored as an empty answer, not as the string "0".
     *
     * @throws \Exception
     */
    public function testCreateStoresAZeroAnswerAsAnEmptyAnswer(): void
    {
        $this->stopWords = $this->createStub(StopWords::class);
        $this->stopWords->method('checkBannedWord')->willReturn(true);

        $storedFaq = null;
        $faq = $this->createMock(Faq::class);
        $faq
            ->expects($this->once())
            ->method('create')
            ->willReturnCallback(static function (FaqEntity $faqEntity) use (&$storedFaq): FaqEntity {
                $storedFaq = $faqEntity;
                return $faqEntity->setId(4712);
            });
        $this->faq = $faq;

        $controller = $this->createController();
        $this->injectControllerState($controller, $this->createUser(loggedIn: true, mayAddFaqs: true));

        $response = $controller->create($this->createFaqRequest(['rubrik' => '1', 'answer' => '0']));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertInstanceOf(FaqEntity::class, $storedFaq);
        self::assertSame('', $storedFaq->getAnswer());
    }

    /**
     * @throws \Exception
     */
    public function testCreateFailsWhenNoCategoryExistsForTheFaq(): void
    {
        $controller = $this->createController();
        $this->injectControllerState($controller, $this->createUser(loggedIn: true, mayAddFaqs: true));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No categories available');

        $controller->create($this->createFaqRequest());
    }

    /**
     * @throws \Exception
     */
    public function testCreateRejectsGuestsWithAnInvalidCaptcha(): void
    {
        self::assertTrue($this->configuration->set('records.allowNewFaqsForGuests', 'true'));
        self::assertTrue($this->configuration->set('spam.enableCaptchaCode', 'true'));

        $controller = $this->createController();
        $this->injectControllerState($controller, $this->createUser(loggedIn: false, mayAddFaqs: false));

        $response = $controller->create($this->createFaqRequest(['rubrik' => '1', 'captcha' => 'wrong']));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(['error' => Translation::get('msgCaptcha')], $this->decodePayload($response));
    }

    /**
     * @throws \Exception
     */
    public function testCreateRejectsAnAnswerContainingBannedWords(): void
    {
        $this->stopWords = $this->createStub(StopWords::class);
        $this->stopWords
            ->method('checkBannedWord')
            ->willReturnCallback(static fn(string $content): bool => !str_contains($content, 'spam'));

        $controller = $this->createController();
        $this->injectControllerState($controller, $this->createUser(loggedIn: true, mayAddFaqs: true));

        $response = $controller->create($this->createFaqRequest(['rubrik' => '1', 'answer' => 'Pure spam']));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(['error' => Translation::get('errSaveEntries')], $this->decodePayload($response));
    }

    /**
     * @throws \Exception
     */
    public function testCreateCleansWysiwygContentAndFailsWhenTheFaqGetsNoId(): void
    {
        self::assertTrue($this->configuration->set('main.enableWysiwygEditorFrontend', 'true'));

        $faqHelper = $this->createMock(FaqHelper::class);
        $faqHelper->expects($this->once())->method('cleanUpContent')->with('Rich answer')->willReturn('Rich answer');
        $this->faqHelper = $faqHelper;
        $this->stopWords = $this->createStub(StopWords::class);
        $this->stopWords->method('checkBannedWord')->willReturn(true);

        $controller = $this->createController();
        $this->injectControllerState($controller, $this->createUser(loggedIn: true, mayAddFaqs: true));

        $response = $controller->create($this->createFaqRequest([
            'rubrik' => '1',
            'answer' => '<p>Rich answer</p>',
        ]));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(['error' => Translation::get('errSaveEntries')], $this->decodePayload($response));
    }

    /**
     * The answered open question cannot be recorded in the history because the language is unknown,
     * group permissions are added for a non-basic permission level and a failing notification is only logged.
     *
     * @throws \Exception
     */
    public function testCreateStoresTheFaqEvenWhenHistoryAndNotificationFail(): void
    {
        self::assertTrue($this->configuration->set('security.permLevel', 'medium'));
        self::assertTrue($this->configuration->set('records.enableDeleteQuestion', 'true'));
        self::assertTrue($this->configuration->set('records.defaultActivation', 'true'));

        $this->stopWords = $this->createStub(StopWords::class);
        $this->stopWords->method('checkBannedWord')->willReturn(true);

        $faq = $this->createMock(Faq::class);
        $faq
            ->expects($this->once())
            ->method('create')
            ->willReturnCallback(static fn(FaqEntity $faqEntity): FaqEntity => $faqEntity->setId(4711));
        $this->faq = $faq;

        $this->question = $this->createMock(Question::class);
        $this->question->expects($this->once())->method('get')->with(17)->willReturn(['id' => 17, 'is_visible' => 'Y']);
        $this->question->expects($this->once())->method('delete')->with(17);

        $this->questionHistory = $this->createMock(QuestionHistoryRepository::class);
        $this->questionHistory->expects($this->never())->method('add');

        $this->notification = $this->createMock(Notification::class);
        $this->notification
            ->expects($this->once())
            ->method('sendNewFaqAdded')
            ->willThrowException(new Exception('Mailer is down'));

        $this->faqHelper = $this->createStub(FaqHelper::class);
        $this->faqHelper->method('createFaqUrl')->willReturn('https://faq.example.com/content/1/4711/en/q.html');

        $controller = $this->createController();
        $this->injectControllerState($controller, $this->createUser(loggedIn: true, mayAddFaqs: true));

        $response = $controller->create($this->createFaqRequest(['rubrik' => ['1'], 'openQuestionID' => 17]));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(
            [
                'success' => Translation::get('msgNewContentThanks'),
                'link' => 'https://faq.example.com/content/1/4711/en/q.html',
                'info' => Translation::get('msgRedirect'),
            ],
            $this->decodePayload($response),
        );
    }

    private function createUser(bool $loggedIn, bool $mayAddFaqs): CurrentUser
    {
        $permission = $this->createStub(PermissionInterface::class);
        $permission->method('hasPermission')->willReturn($mayAddFaqs);

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn($loggedIn);
        $currentUser->method('getUserId')->willReturn($loggedIn ? 1 : -1);
        $currentUser->method('getLogin')->willReturn('');

        return $currentUser;
    }

    private function injectControllerState(FaqController $controller, CurrentUser $currentUser): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $language = new Language($this->configuration, $session);
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);

        new \ReflectionProperty($controller, 'currentUser')->setValue($controller, $currentUser);
        new \ReflectionProperty($controller, 'session')->setValue($controller, $session);
        new \ReflectionProperty($controller, 'configuration')->setValue($controller, $this->configuration);
    }

    /**
     * @param array<string, mixed> $overrides
     * @throws \JsonException
     */
    private function createFaqRequest(array $overrides = []): Request
    {
        $payload = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'question' => 'Test question?',
            'answer' => 'Test answer',
            'keywords' => 'test',
            ...$overrides,
        ];

        return new Request([], [], [], [], [], [], json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, mixed>
     * @throws \JsonException
     */
    private function decodePayload(Response $response): array
    {
        return json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
