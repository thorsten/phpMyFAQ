<?php

/**
 * Test class for PermissionTranslationTwigExtension.
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
 * @since     2024-04-27
 */

namespace phpMyFAQ\Twig\Extensions;

use phpMyFAQ\Translation;
use PHPUnit\Framework\TestCase;
use Twig\Extension\AbstractExtension;

class PermissionTranslationTwigExtensionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

    public function testExtendsAbstractExtension(): void
    {
        $this->assertInstanceOf(AbstractExtension::class, new PermissionTranslationTwigExtension());
    }

    public function testGetPermissionTranslationReturnsTranslationForKnownPermission(): void
    {
        // The translation key format is 'permission::<string>'
        // If the key exists in the translation file, it should return the translation
        $result = PermissionTranslationTwigExtension::getPermissionTranslation('editconfig');
        $this->assertIsString($result);
    }

    public function testGetPermissionTranslationReturnsEmptyStringForUnknownPermission(): void
    {
        $result = PermissionTranslationTwigExtension::getPermissionTranslation('nonExistentPermission12345');
        $this->assertSame('', $result);
    }

    public function testGetPermissionTranslationWithEmptyString(): void
    {
        $result = PermissionTranslationTwigExtension::getPermissionTranslation('');
        $this->assertIsString($result);
    }
}
