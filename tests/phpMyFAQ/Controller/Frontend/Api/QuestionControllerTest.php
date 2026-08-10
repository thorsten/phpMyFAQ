<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Helper\QuestionHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Notification;
use phpMyFAQ\Question;
use phpMyFAQ\Search;
use phpMyFAQ\StopWords;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(QuestionController::class)]
#[UsesNamespace('phpMyFAQ')]
class QuestionControllerTest extends TestCase
{
    private Configuration $configuration;
    private StopWords $stopWords;
    private QuestionHelper $questionHelper;
    private Search $search;
    private Question $question;
    private Notification $notification;

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

        $this->configuration = $this->createConfiguration();
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);

        // The tests below exercise the request-body validation in create(), not the
        // isAddingQuestionsAllowed() authorization guard, so the ask-question feature
        // must be enabled or every request would be rejected before validation runs.
        $this->configuration->getAll();
        $this->overrideConfigurationValue('main.enableAskQuestions', 'true');

        $this->stopWords = $this->createStub(StopWords::class);
        $this->questionHelper = $this->createStub(QuestionHelper::class);
        $this->search = $this->createStub(Search::class);
        $this->question = $this->createStub(Question::class);
        $this->notification = $this->createStub(Notification::class);
    }

    private function createConfiguration(): Configuration
    {
        try {
            return Configuration::getConfigurationInstance();
        } catch (\TypeError) {
            $db = new Sqlite3();
            $db->connect(PMF_TEST_DIR . '/test.db', '', '');

            return new Configuration($db);
        }
    }

    private function overrideConfigurationValue(string $name, string $value): void
    {
        $reflection = new ReflectionClass(Configuration::class);
        $configProperty = $reflection->getProperty('config');
        $currentConfig = $configProperty->getValue($this->configuration);
        $configProperty->setValue($this->configuration, [...$currentConfig, $name => $value]);
    }

    private function createController(): QuestionController
    {
        return new QuestionController(
            $this->stopWords,
            $this->questionHelper,
            $this->search,
            $this->question,
            $this->notification,
        );
    }

    /**
     * @throws Exception
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
            'lang' => 'en',
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
            'lang' => 'en',
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
            'lang' => 'en',
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
    public function testCreateWithMissingLanguageThrowsException(): void
    {
        $requestData = json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'question' => 'Test question?',
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
    public function testCreateWithCategoryThrowsException(): void
    {
        $requestData = json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'question' => 'Test question?',
            'lang' => 'en',
            'category' => 1,
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
    public function testCreateWithSaveParameterThrowsException(): void
    {
        $requestData = json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'question' => 'Test question?',
            'lang' => 'en',
            'save' => 1,
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @return array<string, array{int, bool, bool, bool}>
     */
    public static function authorizationProvider(): array
    {
        // [userId, enableAskQuestions, allowQuestionsForGuests, expectedAllowed]
        return [
            'feature disabled blocks guests' => [-1, false, true, false],
            'feature disabled blocks logged-in users' => [1, false, true, false],
            'guest denied when guest questions disabled' => [-1, true, false, false],
            'guest allowed when guest questions enabled' => [-1, true, true, true],
            'logged-in user allowed regardless of guest setting' => [1, true, false, true],
        ];
    }

    #[DataProvider('authorizationProvider')]
    public function testIsAddingQuestionsAllowed(
        int $userId,
        bool $enableAskQuestions,
        bool $allowQuestionsForGuests,
        bool $expected
    ): void {
        $configurationMock = $this->createMock(Configuration::class);
        $configurationMock
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'main.enableAskQuestions' => $enableAskQuestions,
                'records.allowQuestionsForGuests' => $allowQuestionsForGuests,
                default => null,
            });

        $currentUserMock = $this->createMock(CurrentUser::class);
        $currentUserMock->method('getUserId')->willReturn($userId);

        $reflection = new ReflectionClass(QuestionController::class);
        $controller = $reflection->newInstanceWithoutConstructor();

        $configProp = $reflection->getParentClass()->getProperty('configuration');
        $configProp->setValue($controller, $configurationMock);

        $currentUserProp = $reflection->getParentClass()->getProperty('currentUser');
        $currentUserProp->setValue($controller, $currentUserMock);

        $method = new ReflectionMethod(QuestionController::class, 'isAddingQuestionsAllowed');

        $this->assertSame($expected, $method->invoke($controller));
    }
}
