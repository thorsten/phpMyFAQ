<?php

/**
 * Test class for UserNameTwigExtension.
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
 * @since     2024-04-21
 */

namespace phpMyFAQ\Twig\Extensions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Extension\AbstractExtension;

class UserNameTwigExtensionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Strings::init();
        $this->ensureConfiguration();
    }

    private function ensureConfiguration(): void
    {
        $reflection = new ReflectionClass(Configuration::class);
        $prop = $reflection->getProperty('configuration');
        if ($prop->getValue() !== null) {
            return;
        }

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
        $this->assertInstanceOf(AbstractExtension::class, new UserNameTwigExtension());
    }

    public function testGetUserNameReturnsStringForExistingUser(): void
    {
        $result = UserNameTwigExtension::getUserName(1);
        $this->assertIsString($result);
    }

    public function testGetUserNameReturnsStringForNonExistentUser(): void
    {
        $result = UserNameTwigExtension::getUserName(99999);
        $this->assertIsString($result);
    }

    public function testGetRealNameReturnsStringForExistingUser(): void
    {
        $result = UserNameTwigExtension::getRealName(1);
        $this->assertIsString($result);
    }

    public function testGetRealNameReturnsStringForNonExistentUser(): void
    {
        $result = UserNameTwigExtension::getRealName(99999);
        $this->assertIsString($result);
    }
}
