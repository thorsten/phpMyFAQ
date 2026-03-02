<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\CategoryEntity;
use phpMyFAQ\Faq;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\UserSession;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(CategoryController::class)]
#[UsesNamespace('phpMyFAQ')]
final class CategoryControllerTest extends TestCase
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-front-category-controller-');
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
    }

    protected function tearDown(): void
    {
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
     * @throws \ReflectionException
     */
    public function testBuildParentNavigationLinkReturnsEmptyStringForRootCategory(): void
    {
        $controller = $this->createController();
        $category = $this->createMock(Category::class);
        $entity = new CategoryEntity()
            ->setId(0)
            ->setParentId(0)
            ->setName('Top');

        $result = $this->invokePrivateMethod($controller, 'buildParentNavigationLink', [$category, $entity]);

        self::assertSame('', $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testBuildParentNavigationLinkBuildsCategoryParentLink(): void
    {
        $controller = $this->createController();
        $category = $this->createMock(Category::class);
        $category->expects(self::once())->method('getCategoryName')->with(2)->willReturn('Parent Category');

        $entity = new CategoryEntity()
            ->setId(5)
            ->setParentId(2)
            ->setName('Child');

        $result = $this->invokePrivateMethod($controller, 'buildParentNavigationLink', [$category, $entity]);

        self::assertStringContainsString('category/2/parent-category.html', $result);
        self::assertStringContainsString('Parent Category', $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testBuildParentNavigationLinkFallsBackToShowCategoriesForTopLevelParent(): void
    {
        $controller = $this->createController();
        $category = $this->createMock(Category::class);
        $category->expects(self::once())->method('getCategoryName')->with(0)->willReturn('');

        $entity = new CategoryEntity()
            ->setId(5)
            ->setParentId(0)
            ->setName('Child');

        $result = $this->invokePrivateMethod($controller, 'buildParentNavigationLink', [$category, $entity]);

        self::assertStringContainsString('show-categories.html', $result);
        self::assertStringContainsString('arrow-90deg-up', $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetCategoryImageUrlReturnsNullForMissingImage(): void
    {
        $controller = $this->createController();
        $entity = new CategoryEntity()->setImage('');

        $result = $this->invokePrivateMethod($controller, 'getCategoryImageUrl', [$entity]);

        self::assertNull($result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetCategoryImageUrlReturnsAbsoluteImagePath(): void
    {
        $controller = $this->createController();
        $entity = new CategoryEntity()->setImage('category.png');

        $result = $this->invokePrivateMethod($controller, 'getCategoryImageUrl', [$entity]);

        self::assertStringEndsWith('/content/user/images/category.png', $result);
    }

    private function createController(): CategoryController
    {
        return new CategoryController(
            $this->createMock(UserSession::class),
            $this->createMock(Category::class),
            $this->createMock(Faq::class),
        );
    }

    /**
     * @throws \ReflectionException
     */
    private function invokePrivateMethod(object $object, string $methodName, array $arguments): mixed
    {
        $reflectionMethod = new \ReflectionMethod($object, $methodName);

        return $reflectionMethod->invokeArgs($object, $arguments);
    }
}
