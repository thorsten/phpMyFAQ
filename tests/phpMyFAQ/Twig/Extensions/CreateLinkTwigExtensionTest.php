<?php

/**
 * Test class for CreateLinkTwigExtension.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-08-18
 */

namespace phpMyFAQ\Twig\Extensions;

use phpMyFAQ\Category;
use phpMyFAQ\Category\Permission;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\CategoryEntity;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\TestDatabaseTrait;
use phpMyFAQ\Translation;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Extension\AbstractExtension;

class CreateLinkTwigExtensionTest extends TestCase
{
    use TestDatabaseTrait;

    protected function setUp(): void
    {
        parent::setUp();
        Strings::init();
        $this->ensureConfiguration();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en');
    }

    protected function tearDown(): void
    {
        Translation::resetInstance();
        parent::tearDown();
    }

    private function ensureConfiguration(): void
    {
        // Always establish a fresh connection to the shared test database. A leaked
        // Configuration singleton from an earlier test can hold a stale SQLite handle
        // that fails with "disk I/O error" once another test reopens test.db, so reset
        // the singleton and reconnect to keep these tests independent of execution order.
        new ReflectionClass(Configuration::class)
            ->getProperty('configuration')
            ->setValue(null, null);

        Database::setTablePrefix('');

        $dbHandle = $this->connectToTestDatabaseCopy(new Sqlite3());
        $configuration = new Configuration($dbHandle);
        $configuration->set('main.currentVersion', System::getVersion());

        $language = new Language($configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $configuration->setLanguage($language);
    }

    public function testExtendsAbstractExtension(): void
    {
        $this->assertInstanceOf(AbstractExtension::class, new CreateLinkTwigExtension());
    }

    public function testCategoryLinkIsEmptyForHiddenCategory(): void
    {
        // getCategoryData() ignores permissions, so an unknown or hidden category must not be linked.
        $this->assertSame('', CreateLinkTwigExtension::categoryLink(99999));
    }

    public function testCategoryLinkContainsHtmlExtension(): void
    {
        $configuration = Configuration::getConfigurationInstance();
        $category = new Category($configuration);
        $categoryEntity = new CategoryEntity();
        $categoryEntity
            ->setId(9401)
            ->setLang('en')
            ->setParentId(0)
            ->setName('Linked category')
            ->setDescription('Description')
            ->setUserId(1)
            ->setGroupId(1)
            ->setActive(true)
            ->setShowHome(true)
            ->setImage('');
        $category->create($categoryEntity);

        $permission = new Permission($configuration);
        $permission->add(Permission::GROUP, [9401], [-1]);
        $permission->add(Permission::USER, [9401], [-1]);

        $result = CreateLinkTwigExtension::categoryLink(9401);

        $this->assertStringContainsString('category/9401/linked-category.html', $result);
    }

    public function testFaqLinkReturnsString(): void
    {
        $result = CreateLinkTwigExtension::faqLink(1, 99999, 'en');
        $this->assertIsString($result);
        $this->assertStringContainsString('content', $result);
    }

    public function testFaqLinkContainsLanguage(): void
    {
        $result = CreateLinkTwigExtension::faqLink(1, 1, 'en');
        $this->assertStringContainsString('en', $result);
    }

    public function testFaqLinkContainsHtmlExtension(): void
    {
        $result = CreateLinkTwigExtension::faqLink(1, 1, 'de');
        $this->assertStringContainsString('.html', $result);
    }
}
