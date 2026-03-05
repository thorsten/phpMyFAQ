<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\QuestionEntity;
use phpMyFAQ\Language;
use phpMyFAQ\Notification;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(QuestionController::class)]
#[UsesNamespace('phpMyFAQ')]
final class QuestionControllerFlowTest extends TestCase
{
    private Configuration $configuration;
    private Sqlite3 $dbHandle;
    private string $databasePath;
    private ?Configuration $previousConfiguration = null;

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

        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();
        $configurationProperty->setValue(null, null);

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-api-question-controller-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $this->configuration = new Configuration($this->dbHandle);

        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $this->dbHandle);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');
        Database::setTablePrefix('');

        $language = new Language($this->configuration, new Session(new MockArraySessionStorage()));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);

        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'api.apiClientToken' => 'test-token',
            'records.enableVisibilityQuestions' => '0',
        ]);

        $_SERVER['HTTP_X_PMF_TOKEN'] = 'test-token';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_X_PMF_TOKEN']);

        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        $this->dbHandle->close();
        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, null);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, '');
        @unlink($this->databasePath);

        parent::tearDown();
    }

    /**
     * @throws \Exception
     */
    public function testCreateStoresQuestionWhenTokenIsValid(): void
    {
        $before = $this->getQuestionCount();

        $notification = $this->createMock(Notification::class);
        $notification
            ->expects($this->once())
            ->method('sendQuestionSuccessMail')
            ->with($this->callback(static function (QuestionEntity $entity): bool {
                return (
                    $entity->getCategoryId() === 1
                    && $entity->getQuestion() === 'Is this a test question?'
                    && $entity->getUsername() === 'Test Author'
                    && $entity->getEmail() === 'test@example.com'
                    && $entity->isVisible() === false
                );
            }), $this->isArray());

        $request = new Request([], [], [], [], [], [], json_encode([
            'category-id' => 1,
            'question' => 'Is this a test question?',
            'author' => 'Test Author',
            'email' => 'test@example.com',
        ], JSON_THROW_ON_ERROR));

        $controller = new QuestionController($notification);
        $controller->setContainer($this->createContainer());

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame(['stored' => true], $payload);
        self::assertSame($before + 1, $this->getQuestionCount());
    }

    /**
     * @throws \Exception
     */
    public function testCreateMarksQuestionVisibleWhenVisibilitySettingIsEnabled(): void
    {
        $this->overrideConfigurationValues(['records.enableVisibilityQuestions' => '1']);

        $notification = $this->createMock(Notification::class);
        $notification
            ->expects($this->once())
            ->method('sendQuestionSuccessMail')
            ->with($this->callback(static function (QuestionEntity $entity): bool {
                return $entity->isVisible() === true;
            }), $this->isArray());

        $request = new Request([], [], [], [], [], [], json_encode([
            'category-id' => 1,
            'question' => 'Visible question?',
            'author' => 'Visibility Tester',
            'email' => 'visible@example.com',
        ], JSON_THROW_ON_ERROR));

        $controller = new QuestionController($notification);
        $controller->setContainer($this->createContainer());

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame(['stored' => true], $payload);
    }

    /**
     * @throws \Exception
     */
    public function testCreateThrowsForInvalidToken(): void
    {
        $_SERVER['HTTP_X_PMF_TOKEN'] = 'invalid-token';

        $request = new Request([], [], [], [], [], [], json_encode([
            'category-id' => 1,
            'question' => 'Token test',
            'author' => 'Token Tester',
            'email' => 'token@example.com',
        ], JSON_THROW_ON_ERROR));

        $controller = new QuestionController($this->createStub(Notification::class));
        $controller->setContainer($this->createContainer());

        $this->expectException(UnauthorizedHttpException::class);
        $controller->create($request);
    }

    private function createContainer(): ContainerInterface
    {
        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(false);

        $session = new Session(new MockArraySessionStorage());

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(function (string $id) use ($currentUser, $session) {
                return match ($id) {
                    'phpmyfaq.configuration' => $this->configuration,
                    'phpmyfaq.user.current_user' => $currentUser,
                    'session' => $session,
                    default => null,
                };
            });

        return $container;
    }

    private function getQuestionCount(): int
    {
        $result = $this->dbHandle->query('SELECT COUNT(*) AS count FROM faqquestions');
        self::assertNotFalse($result);
        $row = $this->dbHandle->fetchArray($result);
        self::assertIsArray($row);

        return (int) $row['count'];
    }

    private function overrideConfigurationValues(array $values): void
    {
        $reflection = new \ReflectionClass(Configuration::class);
        $configProperty = $reflection->getProperty('config');
        $config = $configProperty->getValue($this->configuration);
        self::assertIsArray($config);
        $configProperty->setValue($this->configuration, array_merge($config, $values));
    }
}
