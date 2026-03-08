<?php

/**
 * Test class for PluginTwigExtension.
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
 * @since     2024-04-26
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

class PluginTwigExtensionTest extends TestCase
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
        $this->assertInstanceOf(AbstractExtension::class, new PluginTwigExtension());
    }

    public function testTriggerPluginEventReturnsString(): void
    {
        $result = PluginTwigExtension::triggerPluginEvent('test.event');
        $this->assertIsString($result);
    }

    public function testTriggerPluginEventWithDataReturnsString(): void
    {
        $result = PluginTwigExtension::triggerPluginEvent('test.event', ['key' => 'value']);
        $this->assertIsString($result);
    }

    public function testTriggerPluginEventWithNullDataReturnsString(): void
    {
        $result = PluginTwigExtension::triggerPluginEvent('test.event', null);
        $this->assertIsString($result);
    }
}
