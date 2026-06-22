<?php

/**
 * Test class for FaqTwigExtension.
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
 * @since     2024-05-01
 */

namespace phpMyFAQ\Twig\Extensions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Extension\AbstractExtension;

class FaqTwigExtensionTest extends TestCase
{
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
        (new ReflectionClass(Configuration::class))->getProperty('configuration')->setValue(null, null);

        Database::setTablePrefix('');

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($dbHandle);
        $configuration->set('main.currentVersion', System::getVersion());

        $language = new Language($configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $configuration->setLanguage($language);
    }

    public function testExtendsAbstractExtension(): void
    {
        $this->assertInstanceOf(AbstractExtension::class, new FaqTwigExtension());
    }

    public function testGetFaqQuestionReturnsStringForNonExistentFaq(): void
    {
        $result = FaqTwigExtension::getFaqQuestion(99999);
        $this->assertIsString($result);
    }

    public function testGetFaqQuestionReturnsStringForZeroId(): void
    {
        $result = FaqTwigExtension::getFaqQuestion(0);
        $this->assertIsString($result);
    }
}
