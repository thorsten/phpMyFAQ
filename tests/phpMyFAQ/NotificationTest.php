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
use phpMyFAQ\Entity\Comment;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Entity\QuestionEntity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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

    /**
     * Builds a Notification whose configuration has notifications disabled. Every method that
     * composes a mail needs the FAQ title, the default URL or the admin address first, so the
     * expectations registered after construction prove that the method returned before
     * composing the message. The constructor itself legitimately calls some of these.
     *
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    private function notificationWithDisabledNotifications(): Notification
    {
        /** @var Configuration&MockObject $configuration */
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getNoReplyEmail')->willReturn('noreply@example.com');
        $configuration->method('getTitle')->willReturn('phpMyFAQ Test');
        $configuration->method('getAdminEmail')->willReturn('admin@example.com');
        $configuration
            ->method('get')
            ->willReturnMap([
                ['main.enableNotifications', false],
                ['main.administrationMail',  'admin@example.com'],
                ['main.languageDetection',   true],
                ['mail.remoteSMTP',          false],
            ]);

        $notification = new Notification($configuration);

        $configuration->expects($this->never())->method('getTitle');
        $configuration->expects($this->never())->method('getDefaultUrl');
        $configuration->expects($this->never())->method('getAdminEmail');

        return $notification;
    }

    public function testIsEnabledReflectsConfiguration(): void
    {
        $this->assertFalse($this->notificationWithDisabledNotifications()->isEnabled());

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getNoReplyEmail')->willReturn('noreply@example.com');
        $configuration->method('getTitle')->willReturn('phpMyFAQ Test');
        $configuration->method('get')->willReturnMap([['main.enableNotifications', true]]);

        $this->assertTrue((new Notification($configuration))->isEnabled());
    }

    /**
     * Regression for https://github.com/thorsten/phpMyFAQ/issues/4711
     */
    public function testFaqCommentNotificationHonoursDisabledSwitch(): void
    {
        $faq = $this->createStub(Faq::class);
        $faq->faqRecord = ['id' => 1, 'lang' => 'en', 'title' => 'Question', 'email' => 'author@example.com'];

        $comment = new Comment();
        $comment->setUsername('Commenter')->setEmail('commenter@example.com')->setComment('Hello');

        $this->notificationWithDisabledNotifications()->sendFaqCommentNotification($faq, $comment);
    }

    /**
     * Regression for https://github.com/thorsten/phpMyFAQ/issues/4711
     */
    public function testNewsCommentNotificationHonoursDisabledSwitch(): void
    {
        $comment = new Comment();
        $comment->setUsername('Commenter')->setEmail('commenter@example.com')->setComment('Hello');

        $this->notificationWithDisabledNotifications()->sendNewsCommentNotification([
            'id' => 1,
            'lang' => 'en',
            'header' => 'News',
            'authorEmail' => 'author@example.com',
        ], $comment);
    }

    /**
     * Regression for https://github.com/thorsten/phpMyFAQ/issues/4711
     */
    public function testQuestionSuccessMailHonoursDisabledSwitch(): void
    {
        $questionEntity = (new QuestionEntity())
            ->setUsername('Asker')
            ->setEmail('asker@example.com')
            ->setCategoryId(1)
            ->setQuestion('Why?');

        $this->notificationWithDisabledNotifications()->sendQuestionSuccessMail($questionEntity, [1 => [
            'name' => 'Category',
        ]]);
    }

    public function testNewFaqAddedHonoursDisabledSwitch(): void
    {
        $faqEntity = (new FaqEntity())
            ->setId(1)
            ->setLanguage('en');

        $this->notificationWithDisabledNotifications()->sendNewFaqAdded(['someone@example.com'], $faqEntity);
    }

    public function testOpenQuestionAnsweredHonoursDisabledSwitch(): void
    {
        $this->notificationWithDisabledNotifications()->sendOpenQuestionAnswered(
            'user@example.com',
            'User',
            'https://example.test',
        );
    }
}
