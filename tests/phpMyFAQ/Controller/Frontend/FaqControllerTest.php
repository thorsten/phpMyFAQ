<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Bookmark;
use phpMyFAQ\Captcha\CaptchaInterface;
use phpMyFAQ\Captcha\Helper\CaptchaHelperInterface;
use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Date;
use phpMyFAQ\Entity\Comment;
use phpMyFAQ\Faq;
use phpMyFAQ\Language;
use phpMyFAQ\Mail;
use phpMyFAQ\Permission\BasicPermission;
use phpMyFAQ\Service\Gravatar;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\UserSession;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(FaqController::class)]
#[UsesNamespace('phpMyFAQ')]
final class FaqControllerTest extends TestCase
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

        Strings::init('en');
        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-faq-controller-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $this->configuration = new Configuration($this->dbHandle);
        $configurationProperty->setValue(null, $this->configuration);
        $this->initializeDatabaseStatics($this->dbHandle);

        $language = new Language($this->configuration, new Session(new MockArraySessionStorage()));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);
    }

    protected function tearDown(): void
    {
        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        if (isset($this->dbHandle)) {
            $this->dbHandle->close();
            $databaseReflection = new \ReflectionClass(Database::class);
            $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
            $databaseDriverProperty->setValue(null, null);
            $dbTypeProperty = $databaseReflection->getProperty('dbType');
            $dbTypeProperty->setValue(null, '');
        }

        if (isset($this->databasePath) && is_file($this->databasePath)) {
            unlink($this->databasePath);
        }

        parent::tearDown();
    }

    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function testPrepareCommentsDataFormatsAndEscapesCommentData(): void
    {
        $date = $this->createMock(Date::class);
        $date->expects(self::once())->method('format')->with('20260301120000')->willReturn('2026-03-01');

        $mail = $this->createMock(Mail::class);
        $mail
            ->expects(self::once())
            ->method('safeEmail')
            ->with('john@example.com')
            ->willReturn('john [at] example.com');

        $gravatar = $this->createMock(Gravatar::class);
        $gravatar
            ->expects(self::once())
            ->method('getImage')
            ->with('john@example.com', ['class' => 'img-thumbnail'])
            ->willReturn('<img src="avatar.png">');

        $controller = $this->createController($date, $mail, $gravatar);

        $comment = new Comment()
            ->setId(7)
            ->setEmail('john@example.com')
            ->setUsername('<John>')
            ->setDate('20260301120000')
            ->setComment('Visit https://example.com');

        $result = $this->invokePrivateMethod($controller, 'prepareCommentsData', [[$comment]]);

        self::assertSame(7, $result['comments'][0]['id']);
        self::assertSame('&lt;John&gt;', $result['comments'][0]['username']);
        self::assertSame('Visit example.com', $result['comments'][0]['comment']);
        self::assertSame('<img src="avatar.png">', $result['gravatarImages'][7]);
        self::assertSame('john [at] example.com', $result['safeEmails'][7]);
        self::assertSame('2026-03-01', $result['formattedDates'][7]);
    }

    /**
     * @throws \Exception
     */
    public function testShowReturnsNotFoundWhenFaqIsNotLinkedToCategory(): void
    {
        $faq = new Faq($this->configuration);
        $faq->getFaq(1);

        $category = $this
            ->getMockBuilder(Category::class)
            ->setConstructorArgs([$this->configuration, [-1]])
            ->onlyMethods(['categoryHasLinkToFaq'])
            ->getMock();
        $category->expects(self::once())->method('categoryHasLinkToFaq')->with(1, 999)->willReturn(false);

        $controller = $this->createController(
            $this->createMock(Date::class),
            $this->createMock(Mail::class),
            $this->createMock(Gravatar::class),
            $faq,
            $category,
        );

        $response = $controller->show(
            new \Symfony\Component\HttpFoundation\Request(
                [],
                [],
                ['categoryId' => '999', 'faqId' => '1', 'faqLang' => 'en', 'slug' => 'faq-title'],
            ),
        );

        self::assertSame(\Symfony\Component\HttpFoundation\Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testAddRedirectsGuestsToLoginWhenGuestFaqsAreDisabled(): void
    {
        $this->configuration->getAll();
        $this->setConfigurationValue('records.allowNewFaqsForGuests', 'false');

        $guestUser = $this->createCurrentUserStub(-1, false);
        $controller = $this->createController(
            $this->createMock(Date::class),
            $this->createMock(Mail::class),
            $this->createMock(Gravatar::class),
            currentUser: $guestUser,
        );

        $response = $controller->add(new \Symfony\Component\HttpFoundation\Request());

        self::assertSame(\Symfony\Component\HttpFoundation\Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame($this->configuration->getDefaultUrl() . 'login', $response->headers->get('Location'));
    }

    /**
     * @throws \Exception
     */
    public function testAddRedirectsLoggedInUsersHomeWhenFaqAddPermissionIsMissing(): void
    {
        $loggedInUser = $this->createCurrentUserStub(42, false);
        $controller = $this->createController(
            $this->createMock(Date::class),
            $this->createMock(Mail::class),
            $this->createMock(Gravatar::class),
            currentUser: $loggedInUser,
        );

        $response = $controller->add(new \Symfony\Component\HttpFoundation\Request());

        self::assertSame(\Symfony\Component\HttpFoundation\Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame($this->configuration->getDefaultUrl(), $response->headers->get('Location'));
    }

    /**
     * @throws \Exception
     */
    public function testContentRedirectReturnsNotFoundWhenFaqRowCannotBeFetched(): void
    {
        $faq = $this->createMock(Faq::class);
        $faq->expects(self::once())->method('getFaqResult')->with(42, 'en')->willReturn('faq-result');

        $databaseDriver = $this->createMock(DatabaseDriver::class);
        $databaseDriver->expects(self::once())->method('numRows')->with('faq-result')->willReturn(1);
        $databaseDriver->expects(self::once())->method('fetchObject')->with('faq-result')->willReturn(false);

        $this->setDatabaseDriver($databaseDriver);

        $controller = $this->createController(
            $this->createMock(Date::class),
            $this->createMock(Mail::class),
            $this->createMock(Gravatar::class),
            faq: $faq,
        );

        $response = $controller->contentRedirect(
            new \Symfony\Component\HttpFoundation\Request([], [], ['faqId' => '42', 'faqLang' => 'en']),
        );

        self::assertSame(\Symfony\Component\HttpFoundation\Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testContentRedirectReturnsNotFoundWhenFaqHasNoLinkedCategory(): void
    {
        $faq = $this->createMock(Faq::class);
        $faq->expects(self::once())->method('getFaqResult')->with(42, 'en')->willReturn('faq-result');

        $databaseDriver = $this->createMock(DatabaseDriver::class);
        $databaseDriver->expects(self::once())->method('numRows')->with('faq-result')->willReturn(1);
        $databaseDriver
            ->expects(self::once())
            ->method('fetchObject')
            ->with('faq-result')
            ->willReturn((object) ['thema' => 'Linked question']);

        $this->setDatabaseDriver($databaseDriver);

        $category = $this
            ->getMockBuilder(Category::class)
            ->setConstructorArgs([$this->configuration, [-1]])
            ->onlyMethods(['getCategoryIdFromFaq'])
            ->getMock();
        $category->expects(self::once())->method('getCategoryIdFromFaq')->with(42)->willReturn(0);

        $controller = $this->createController(
            $this->createMock(Date::class),
            $this->createMock(Mail::class),
            $this->createMock(Gravatar::class),
            faq: $faq,
            category: $category,
        );

        $response = $controller->contentRedirect(
            new \Symfony\Component\HttpFoundation\Request([], [], ['faqId' => '42', 'faqLang' => 'en']),
        );

        self::assertSame(\Symfony\Component\HttpFoundation\Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    private function createController(
        Date $date,
        Mail $mail,
        Gravatar $gravatar,
        ?Faq $faq = null,
        ?Category $category = null,
        ?CurrentUser $currentUser = null,
    ): FaqController {
        $currentUser ??= new CurrentUser($this->configuration);
        $faq ??= new Faq($this->configuration);
        $category ??= new Category($this->configuration, [-1]);
        $category->setLanguage('en');

        $controller = new FaqController(
            new UserSession($this->configuration),
            $this->createMock(CaptchaInterface::class),
            $this->createMock(CaptchaHelperInterface::class),
            $faq,
            $category,
            new Bookmark($this->configuration, $currentUser),
            $date,
            $mail,
            $gravatar,
        );

        $configurationProperty = new \ReflectionProperty($controller, 'configuration');
        $configurationProperty->setValue($controller, $this->configuration);

        $currentUserProperty = new \ReflectionProperty($controller, 'currentUser');
        $currentUserProperty->setValue($controller, $currentUser);

        return $controller;
    }

    /**
     * @throws \ReflectionException
     */
    private function invokePrivateMethod(object $object, string $methodName, array $arguments): mixed
    {
        $reflectionMethod = new \ReflectionMethod($object, $methodName);

        return $reflectionMethod->invokeArgs($object, $arguments);
    }

    private function initializeDatabaseStatics(Sqlite3 $dbHandle): void
    {
        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $dbHandle);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');
        Database::setTablePrefix('');
    }

    private function setDatabaseDriver(\phpMyFAQ\Database\DatabaseDriver $databaseDriver): void
    {
        $reflection = new \ReflectionClass(Configuration::class);
        $configProperty = $reflection->getProperty('config');
        $config = $configProperty->getValue($this->configuration);
        self::assertIsArray($config);
        $config['core.database'] = $databaseDriver;
        $configProperty->setValue($this->configuration, $config);
    }

    private function setConfigurationValue(string $key, mixed $value): void
    {
        $reflection = new \ReflectionClass(Configuration::class);
        $configProperty = $reflection->getProperty('config');
        $config = $configProperty->getValue($this->configuration);
        self::assertIsArray($config);
        $config[$key] = $value;
        $configProperty->setValue($this->configuration, $config);
    }

    private function createCurrentUserStub(int $userId, bool $canAddFaq): CurrentUser
    {
        $permission = $this->createMock(BasicPermission::class);
        $permission->method('hasPermission')->willReturn($canAddFaq);
        $permission->method('getUserGroups')->with($userId)->willReturn([-1]);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('getUserId')->willReturn($userId);
        $currentUser->method('isLoggedIn')->willReturn($userId > 0);
        $currentUser->method('getUserData')->willReturn('');

        return $currentUser;
    }
}
