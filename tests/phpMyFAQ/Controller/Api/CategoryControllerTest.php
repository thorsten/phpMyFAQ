<?php

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Category\Permission as CategoryPermission;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\CategoryEntity;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\BasicPermission;
use phpMyFAQ\System;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class CategoryControllerTest extends TestCase
{
    private const int PUBLIC_ID = 9201;

    private const int INACTIVE_ID = 9202;

    private const int PRIVATE_ID = 9203;

    private const int PRIVATE_GROUP_ID = 999;

    private Configuration $configuration;

    private \phpMyFAQ\Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
        $this->configuration->set('main.currentVersion', System::getVersion());
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);

        $this->category = new \phpMyFAQ\Category($this->configuration);
        $this->category->create($this->buildCategory(self::PUBLIC_ID, 'PUBLIC_CONTROL', true));
        $this->category->create($this->buildCategory(self::INACTIVE_ID, 'INACTIVE_PROBE', false));
        $this->category->create($this->buildCategory(self::PRIVATE_ID, 'PRIVATE_PROBE', true));

        $permission = new CategoryPermission($this->configuration);
        $permission->add(CategoryPermission::GROUP, [self::PUBLIC_ID, self::INACTIVE_ID], [-1]);
        $permission->add(CategoryPermission::GROUP, [self::PRIVATE_ID], [self::PRIVATE_GROUP_ID]);
        $permission->add(CategoryPermission::USER, [self::PUBLIC_ID, self::INACTIVE_ID, self::PRIVATE_ID], [-1]);
    }

    protected function tearDown(): void
    {
        $permission = new CategoryPermission($this->configuration);
        $permission->delete(CategoryPermission::GROUP, [self::PUBLIC_ID, self::INACTIVE_ID, self::PRIVATE_ID]);
        $permission->delete(CategoryPermission::USER, [self::PUBLIC_ID, self::INACTIVE_ID, self::PRIVATE_ID]);

        foreach ([self::PUBLIC_ID, self::INACTIVE_ID, self::PRIVATE_ID] as $id) {
            $this->category->delete($id, 'en');
        }

        parent::tearDown();
    }

    public function testListHidesInactiveAndGroupRestrictedCategoriesFromAnonymousVisitors(): void
    {
        $response = $this->buildController()->list();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $ids = array_column(json_decode((string) $response->getContent(), true), 'id');

        $this->assertContains(self::PUBLIC_ID, $ids);
        $this->assertNotContains(self::INACTIVE_ID, $ids);
        $this->assertNotContains(self::PRIVATE_ID, $ids);
        $this->assertStringNotContainsString('INACTIVE_PROBE', (string) $response->getContent());
        $this->assertStringNotContainsString('PRIVATE_PROBE', (string) $response->getContent());
    }

    private function buildController(): CategoryController
    {
        $reflection = new ReflectionClass(CategoryController::class);
        $controller = $reflection->newInstanceWithoutConstructor();

        $language = $this->createMock(Language::class);
        $language->method('setLanguageByAcceptLanguage')->willReturn('en');

        // An anonymous visitor: no user ID and only the default group -1.
        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('getUserId')->willReturn(-1);
        $currentUser->perm = $this->createMock(BasicPermission::class);

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('get')
            ->willReturnCallback(static fn(string $id) => match ($id) {
                'phpmyfaq.language' => $language,
                default => null,
            });

        $parent = $reflection->getParentClass();
        $parent->getProperty('container')->setValue($controller, $container);
        $parent->getProperty('configuration')->setValue($controller, $this->configuration);
        $parent->getProperty('currentUser')->setValue($controller, $currentUser);

        return $controller;
    }

    private function buildCategory(int $id, string $name, bool $active): CategoryEntity
    {
        return (new CategoryEntity())
            ->setId($id)
            ->setLang('en')
            ->setParentId(0)
            ->setName($name)
            ->setDescription('Description ' . $name)
            ->setUserId(1)
            ->setGroupId(-1)
            ->setActive($active)
            ->setShowHome(true)
            ->setImage('');
    }
}
