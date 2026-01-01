<?php

/**
 * Notification Test.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    GitHub Copilot
 * @copyright 2009-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-01-04
 */

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

/**
 * Class NotificationTest
 */
#[AllowMockObjectsWithoutExpectations]
class NotificationTest extends TestCase
{
    private Configuration $configuration;
    private Notification $notification;

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
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

        $this->configuration = $this->createStub(Configuration::class);

        // Mock configuration methods
        $this->configuration->method('getNoReplyEmail')->willReturn('noreply@example.com');

        $this->configuration->method('getTitle')->willReturn('phpMyFAQ Test');

        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['main.administrationMail', 'admin@example.com'],
                ['main.languageDetection',  true],
                ['mail.remoteSMTP',         false],
            ]);

        $this->notification = new Notification($this->configuration);
    }

    /**
     * @throws Exception
     */
    public function testConstructorCreatesInstance(): void
    {
        $notification = new Notification($this->configuration);

        $this->assertInstanceOf(Notification::class, $notification);
    }
}
