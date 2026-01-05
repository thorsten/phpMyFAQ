<?php

declare(strict_types=1);

namespace phpMyFAQ\Faq;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class QuestionServiceTest extends TestCase
{
    private Configuration $configuration;
    private CurrentUser|MockObject $currentUser;
    private array $currentGroups;
    private QuestionService $questionService;

    /**
     * @throws Exception
     * @throws \phpMyFAQ\Core\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init('en');

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        // Create configuration with real database
        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);

        $language = new Language($this->configuration, $this->createStub(Session::class));
        $this->configuration->setLanguage($language);

        // Set configuration values
        $this->configuration->set('records.allowQuestionsForGuests', true);

        // Create mock current user
        $this->currentUser = $this->createMock(CurrentUser::class);
        $this->currentUser->method('getUserId')->willReturn(1);

        $this->currentGroups = [1];

        $this->questionService = new QuestionService($this->configuration, $this->currentUser, $this->currentGroups);
    }

    /**
     * @throws Exception
     */
    public function testConstructor(): void
    {
        $service = new QuestionService($this->configuration, $this->currentUser, $this->currentGroups);
        static::assertInstanceOf(QuestionService::class, $service);
    }

    /**
     * @throws Exception
     */
    public function testPrepareAskQuestionDataWithDefaultCategory(): void
    {
        $result = $this->questionService->prepareAskQuestionData(0);

        static::assertIsArray($result);
        static::assertArrayHasKey('selectedCategory', $result);
        static::assertArrayHasKey('categories', $result);
        static::assertArrayHasKey('formData', $result);
        static::assertArrayHasKey('noCategories', $result);

        static::assertSame(0, $result['selectedCategory']);
    }

    /**
     * @throws Exception
     */
    public function testPrepareAskQuestionDataWithSelectedCategory(): void
    {
        $result = $this->questionService->prepareAskQuestionData(5);

        static::assertSame(5, $result['selectedCategory']);
    }

    /**
     * @throws Exception
     */
    public function testPrepareAskQuestionDataReturnsCategories(): void
    {
        $result = $this->questionService->prepareAskQuestionData(0);

        static::assertIsArray($result['categories']);
    }

    /**
     * @throws Exception
     */
    public function testPrepareAskQuestionDataReturnsFormData(): void
    {
        $result = $this->questionService->prepareAskQuestionData(0);

        static::assertIsArray($result['formData']);
    }

    /**
     * @throws Exception
     */
    public function testPrepareAskQuestionDataNoCategoriesFlag(): void
    {
        $result = $this->questionService->prepareAskQuestionData(0);

        static::assertIsBool($result['noCategories']);
    }

    /**
     * @throws Exception
     */
    public function testCanUserAskQuestionForGuest(): void
    {
        // Mock guest user (user ID = -1)
        $guestUser = $this->createMock(CurrentUser::class);
        $guestUser->method('getUserId')->willReturn(-1);

        $service = new QuestionService($this->configuration, $guestUser, $this->currentGroups);

        static::assertTrue($service->canUserAskQuestion());
    }

    /**
     * @throws Exception
     */
    public function testCanUserAskQuestionForGuestWhenDisabled(): void
    {
        // Disable guest question asking
        $this->configuration->set('records.allowQuestionsForGuests', false);

        // Mock guest user (user ID = -1)
        $guestUser = $this->createMock(CurrentUser::class);
        $guestUser->method('getUserId')->willReturn(-1);

        $service = new QuestionService($this->configuration, $guestUser, $this->currentGroups);

        static::assertFalse($service->canUserAskQuestion());
    }

    /**
     * @throws Exception
     */
    public function testCanUserAskQuestionForLoggedInUser(): void
    {
        // Create a logged-in user mock
        $loggedInUser = $this->createMock(CurrentUser::class);
        $loggedInUser->method('getUserId')->willReturn(42);

        $service = new QuestionService($this->configuration, $loggedInUser, $this->currentGroups);

        static::assertTrue($service->canUserAskQuestion());
    }

    /**
     * @throws Exception
     */
    public function testGetDefaultUserEmailForLoggedInUser(): void
    {
        $this->currentUser
            ->method('getUserData')
            ->with('email')
            ->willReturn('test@example.com');

        $result = $this->questionService->getDefaultUserEmail();

        static::assertSame('test@example.com', $result);
    }

    /**
     * @throws Exception
     */
    public function testGetDefaultUserEmailForGuest(): void
    {
        $guestUser = $this->createMock(CurrentUser::class);
        $guestUser->method('getUserId')->willReturn(-1);

        $service = new QuestionService($this->configuration, $guestUser, $this->currentGroups);

        $result = $service->getDefaultUserEmail();

        static::assertSame('', $result);
    }

    /**
     * @throws Exception
     */
    public function testGetDefaultUserNameForLoggedInUser(): void
    {
        $this->currentUser
            ->method('getUserData')
            ->with('display_name')
            ->willReturn('John Doe');

        $result = $this->questionService->getDefaultUserName();

        static::assertSame('John Doe', $result);
    }

    /**
     * @throws Exception
     */
    public function testGetDefaultUserNameForGuest(): void
    {
        $guestUser = $this->createMock(CurrentUser::class);
        $guestUser->method('getUserId')->willReturn(-1);

        $service = new QuestionService($this->configuration, $guestUser, $this->currentGroups);

        $result = $service->getDefaultUserName();

        static::assertSame('', $result);
    }

    /**
     * @throws Exception
     */
    public function testGetCategory(): void
    {
        $result = $this->questionService->getCategory();

        static::assertInstanceOf(\phpMyFAQ\Category::class, $result);
    }

    /**
     * @throws Exception
     */
    public function testPrepareAskQuestionDataAllFieldsArePopulated(): void
    {
        $result = $this->questionService->prepareAskQuestionData(10);

        // Verify all expected keys are present
        $expectedKeys = [
            'selectedCategory',
            'categories',
            'formData',
            'noCategories',
        ];

        foreach ($expectedKeys as $key) {
            static::assertArrayHasKey($key, $result, "Missing key: {$key}");
        }
    }

    /**
     * @throws Exception
     */
    public function testCanUserAskQuestionReturnsBooleanValue(): void
    {
        $result = $this->questionService->canUserAskQuestion();

        static::assertIsBool($result);
    }

    /**
     * @throws Exception
     */
    public function testGetDefaultUserEmailReturnsString(): void
    {
        $userMock = $this->createMock(CurrentUser::class);
        $userMock->method('getUserId')->willReturn(1);
        $userMock->method('getUserData')->with('email')->willReturn('test@example.com');

        $service = new QuestionService($this->configuration, $userMock, $this->currentGroups);

        $result = $service->getDefaultUserEmail();

        static::assertIsString($result);
    }

    /**
     * @throws Exception
     */
    public function testGetDefaultUserNameReturnsString(): void
    {
        $userMock = $this->createMock(CurrentUser::class);
        $userMock->method('getUserId')->willReturn(1);
        $userMock->method('getUserData')->with('display_name')->willReturn('John Doe');

        $service = new QuestionService($this->configuration, $userMock, $this->currentGroups);

        $result = $service->getDefaultUserName();

        static::assertIsString($result);
    }

    /**
     * @throws Exception
     */
    public function testPrepareAskQuestionDataWithNegativeCategoryId(): void
    {
        $result = $this->questionService->prepareAskQuestionData(-1);

        static::assertSame(-1, $result['selectedCategory']);
        static::assertIsArray($result['categories']);
        static::assertIsArray($result['formData']);
    }
}
