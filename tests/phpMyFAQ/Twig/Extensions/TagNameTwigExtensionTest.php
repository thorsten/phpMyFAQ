<?php

/**
 * Test class for TagNameTwigExtension.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-05-18
 */

namespace phpMyFAQ\Twig\Extensions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\TestDatabaseTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Extension\AbstractExtension;

class TagNameTwigExtensionTest extends TestCase
{
    use TestDatabaseTrait;

    protected function setUp(): void
    {
        parent::setUp();
        Strings::init();
        $this->ensureConfiguration();
    }

    private function ensureConfiguration(): void
    {
        // Always install a fresh configuration singleton so the static Twig filter never
        // resolves one that an earlier test left behind.
        $configuration = $this->createTestConfiguration();
        $configuration->set('main.currentVersion', System::getVersion());

        $language = new Language($configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $configuration->setLanguage($language);
    }

    public function testExtendsAbstractExtension(): void
    {
        $this->assertInstanceOf(AbstractExtension::class, new TagNameTwigExtension());
    }

    public function testGetTagNameReturnsEmptyStringForNonExistentTag(): void
    {
        $result = TagNameTwigExtension::getTagName(99999);
        $this->assertSame('', $result);
    }

    public function testGetTagNameReturnsStringForZeroId(): void
    {
        $result = TagNameTwigExtension::getTagName(0);
        $this->assertIsString($result);
    }
}
