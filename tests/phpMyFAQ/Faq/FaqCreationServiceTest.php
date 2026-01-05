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
class FaqCreationServiceTest extends TestCase
{
    private Configuration $configuration;
    private CurrentUser|MockObject $currentUser;
    private array $currentGroups;
    private FaqCreationService $faqService;

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
        $this->configuration->set('records.allowNewFaqsForGuests', true);

        // Create mock current user
        $this->currentUser = $this->createMock(CurrentUser::class);
        $this->currentUser->method('getUserId')->willReturn(1);

        $this->currentGroups = [1];

        $this->faqService = new FaqCreationService($this->configuration, $this->currentUser, $this->currentGroups);
    }

    /**
     * @throws Exception
     */
    public function testConstructor(): void
    {
        $service = new FaqCreationService($this->configuration, $this->currentUser, $this->currentGroups);
        static::assertInstanceOf(FaqCreationService::class, $service);
    }

    /**
     * @throws Exception
     */
    public function testPrepareAddFaqDataWithoutQuestion(): void
    {
        $result = $this->faqService->prepareAddFaqData(null, -1);

        static::assertIsArray($result);
        static::assertArrayHasKey('question', $result);
        static::assertArrayHasKey('readonly', $result);
        static::assertArrayHasKey('displayFullForm', $result);
        static::assertArrayHasKey('selectedQuestion', $result);
        static::assertArrayHasKey('selectedCategory', $result);
        static::assertArrayHasKey('categories', $result);
        static::assertArrayHasKey('formData', $result);
        static::assertArrayHasKey('noCategories', $result);

        static::assertSame('', $result['question']);
        static::assertSame('', $result['readonly']);
        static::assertFalse($result['displayFullForm']);
        static::assertNull($result['selectedQuestion']);
        static::assertSame(-1, $result['selectedCategory']);
    }

    /**
     * @throws Exception
     */
    public function testPrepareAddFaqDataWithSelectedCategory(): void
    {
        $result = $this->faqService->prepareAddFaqData(null, 5);

        static::assertSame(5, $result['selectedCategory']);
    }

    /**
     * @throws Exception
     */
    public function testPrepareAddFaqDataReturnsCategories(): void
    {
        $result = $this->faqService->prepareAddFaqData(null, -1);

        static::assertIsArray($result['categories']);
    }

    /**
     * @throws Exception
     */
    public function testPrepareAddFaqDataReturnsFormData(): void
    {
        $result = $this->faqService->prepareAddFaqData(null, -1);

        static::assertIsArray($result['formData']);
    }

    /**
     * @throws Exception
     */
    public function testPrepareAddFaqDataNoCategoriesFlag(): void
    {
        $result = $this->faqService->prepareAddFaqData(null, -1);

        static::assertIsBool($result['noCategories']);
    }

    /**
     * @throws Exception
     */
    public function testCanUserAddFaqForGuest(): void
    {
        // Mock guest user (user ID = -1)
        $guestUser = $this->createMock(CurrentUser::class);
        $guestUser->method('getUserId')->willReturn(-1);

        $service = new FaqCreationService($this->configuration, $guestUser, $this->currentGroups);

        static::assertTrue($service->canUserAddFaq());
    }

    /**
     * @throws Exception
     */
    public function testCanUserAddFaqForGuestWhenDisabled(): void
    {
        // Disable guest FAQ creation
        $this->configuration->set('records.allowNewFaqsForGuests', false);

        // Mock guest user (user ID = -1)
        $guestUser = $this->createMock(CurrentUser::class);
        $guestUser->method('getUserId')->willReturn(-1);

        $service = new FaqCreationService($this->configuration, $guestUser, $this->currentGroups);

        static::assertFalse($service->canUserAddFaq());
    }

    /**
     * @throws Exception
     */
    public function testCanUserAddFaqForLoggedInUserWithPermission(): void
    {
        // Create a user mock with permission
        $userWithPerm = $this->createMock(CurrentUser::class);
        $userWithPerm->method('getUserId')->willReturn(1);

        $permMock = $this->createMock(\phpMyFAQ\Permission\PermissionInterface::class);
        $permMock
            ->expects(static::once())
            ->method('hasPermission')
            ->with(1, \phpMyFAQ\Enums\PermissionType::FAQ_ADD->value)
            ->willReturn(true);

        $userWithPerm->perm = $permMock;

        $service = new FaqCreationService($this->configuration, $userWithPerm, $this->currentGroups);

        static::assertTrue($service->canUserAddFaq());
    }

    /**
     * @throws Exception
     */
    public function testCanUserAddFaqForLoggedInUserWithoutPermission(): void
    {
        // Create a user mock without permission
        $userWithoutPerm = $this->createMock(CurrentUser::class);
        $userWithoutPerm->method('getUserId')->willReturn(1);

        $permMock = $this->createMock(\phpMyFAQ\Permission\PermissionInterface::class);
        $permMock
            ->expects(static::once())
            ->method('hasPermission')
            ->with(1, \phpMyFAQ\Enums\PermissionType::FAQ_ADD->value)
            ->willReturn(false);

        $userWithoutPerm->perm = $permMock;

        $service = new FaqCreationService($this->configuration, $userWithoutPerm, $this->currentGroups);

        static::assertFalse($service->canUserAddFaq());
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

        $result = $this->faqService->getDefaultUserEmail();

        static::assertSame('test@example.com', $result);
    }

    /**
     * @throws Exception
     */
    public function testGetDefaultUserEmailForGuest(): void
    {
        $guestUser = $this->createMock(CurrentUser::class);
        $guestUser->method('getUserId')->willReturn(-1);

        $service = new FaqCreationService($this->configuration, $guestUser, $this->currentGroups);

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

        $result = $this->faqService->getDefaultUserName();

        static::assertSame('John Doe', $result);
    }

    /**
     * @throws Exception
     */
    public function testGetDefaultUserNameForGuest(): void
    {
        $guestUser = $this->createMock(CurrentUser::class);
        $guestUser->method('getUserId')->willReturn(-1);

        $service = new FaqCreationService($this->configuration, $guestUser, $this->currentGroups);

        $result = $service->getDefaultUserName();

        static::assertSame('', $result);
    }

    /**
     * @throws Exception
     */
    public function testGetCategory(): void
    {
        $result = $this->faqService->getCategory();

        static::assertInstanceOf(\phpMyFAQ\Category::class, $result);
    }

    /**
     * @throws Exception
     */
    public function testPrepareAddFaqDataAllFieldsArePopulated(): void
    {
        $result = $this->faqService->prepareAddFaqData(null, 10);

        // Verify all expected keys are present
        $expectedKeys = [
            'question',
            'readonly',
            'displayFullForm',
            'selectedQuestion',
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
    public function testPrepareAddFaqDataReadonlyIsEmptyByDefault(): void
    {
        $result = $this->faqService->prepareAddFaqData(null, -1);

        static::assertSame('', $result['readonly']);
    }

    /**
     * @throws Exception
     */
    public function testCanUserAddFaqReturnsBooleanValue(): void
    {
        // Create a user mock with permission
        $userWithPerm = $this->createMock(CurrentUser::class);
        $userWithPerm->method('getUserId')->willReturn(1);

        $permMock = $this->createMock(\phpMyFAQ\Permission\PermissionInterface::class);
        $permMock->method('hasPermission')->willReturn(true);

        $userWithPerm->perm = $permMock;

        $service = new FaqCreationService($this->configuration, $userWithPerm, $this->currentGroups);

        $result = $service->canUserAddFaq();

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

        $service = new FaqCreationService($this->configuration, $userMock, $this->currentGroups);

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

        $service = new FaqCreationService($this->configuration, $userMock, $this->currentGroups);

        $result = $service->getDefaultUserName();

        static::assertIsString($result);
    }
}
