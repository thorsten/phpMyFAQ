<?php

/**
 * Test class for TranslateTwigExtension.
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

use phpMyFAQ\Translation;
use PHPUnit\Framework\TestCase;
use Twig\Extension\AbstractExtension;

class TranslateTwigExtensionTest extends TestCase
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
        $this->assertInstanceOf(AbstractExtension::class, new TranslateTwigExtension());
    }

    public function testTranslateReturnsTranslationForKnownKey(): void
    {
        $result = TranslateTwigExtension::translate('msgQuestion');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertNotEquals('msgQuestion', $result);
    }

    public function testTranslateReturnsKeyForUnknownKey(): void
    {
        $result = TranslateTwigExtension::translate('nonExistentKey12345');
        $this->assertSame('nonExistentKey12345', $result);
    }

    public function testTranslateReturnsKeyForEmptyTranslation(): void
    {
        $result = TranslateTwigExtension::translate('some.completely.unknown.key');
        $this->assertSame('some.completely.unknown.key', $result);
    }
}
