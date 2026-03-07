<?php

namespace phpMyFAQ;

use Monolog\Handler\NullHandler;
use Monolog\Logger;
use phpMyFAQ\Auth\AuthDatabase;
use phpMyFAQ\Category\CategoryCache;
use phpMyFAQ\Category\CategoryPermissionContext;
use phpMyFAQ\Category\CategoryRepository;
use phpMyFAQ\Category\Permission\CategoryPermissionService;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Entity\Comment;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Entity\QuestionEntity;
use phpMyFAQ\Link\Strategy\GenericPathStrategy;
use phpMyFAQ\Link\Strategy\StrategyRegistry;
use phpMyFAQ\Link\Util\TitleSlugifier;
use phpMyFAQ\Mail\Builtin;
use phpMyFAQ\Permission\BasicPermission;
use phpMyFAQ\Permission\BasicPermissionRepository;
use phpMyFAQ\Push\WebPushService;
use phpMyFAQ\Strings\AbstractString;
use phpMyFAQ\Strings\Mbstring;
use phpMyFAQ\User\UserData;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(Notification::class)]
#[UsesClass(Strings::class)]
#[UsesClass(Translation::class)]
#[UsesClass(FaqEntity::class)]
#[UsesClass(Comment::class)]
#[UsesClass(QuestionEntity::class)]
#[UsesClass(Link::class)]
#[UsesClass(GenericPathStrategy::class)]
#[UsesClass(StrategyRegistry::class)]
#[UsesClass(TitleSlugifier::class)]
#[UsesClass(AbstractString::class)]
#[UsesClass(Mbstring::class)]
#[UsesClass(User::class)]
#[UsesClass(Auth::class)]
#[UsesClass(AuthDatabase::class)]
#[UsesClass(Database::class)]
#[UsesClass(Encryption::class)]
#[UsesClass(Permission::class)]
#[UsesClass(BasicPermission::class)]
#[UsesClass(BasicPermissionRepository::class)]
#[UsesClass(Mail::class)]
#[UsesClass(Builtin::class)]
#[UsesClass(UserData::class)]
#[UsesClass(Utils::class)]
#[UsesClass(Category::class)]
#[UsesClass(CategoryCache::class)]
#[UsesClass(CategoryPermissionContext::class)]
#[UsesClass(CategoryRepository::class)]
#[UsesClass(CategoryPermissionService::class)]
class NotificationTest extends TestCase
{
    private Configuration&MockObject $configuration;
    private Mail&MockObject $mail;
    private Faq&MockObject $faq;
    private Category&MockObject $category;
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

        $this->configuration = $this->createMock(Configuration::class);
        $this->mail = $this->createMock(Mail::class);
        $this->faq = $this->createMock(Faq::class);
        $this->category = $this->createMock(Category::class);

        $this->configuration->method('getNoReplyEmail')->willReturn('noreply@example.com');
        $this->configuration->method('getTitle')->willReturn('phpMyFAQ Test');
        $this->configuration->method('getAdminEmail')->willReturn('admin@example.com');
        $this->configuration->method('getDefaultUrl')->willReturn('https://example.com/');

        $this->configuration
            ->method('get')
            ->willReturnCallback(function (string $item) {
                return match ($item) {
                    'main.administrationMail' => 'admin@example.com',
                    'main.languageDetection' => true,
                    'main.enableNotifications' => true,
                    'mail.remoteSMTP' => false,
                    'security.permLevel' => 'basic',
                    'security.loginWithEmailAddress' => false,
                    'mail.noReplySenderAddress' => 'noreply@example.com',
                    default => null,
                };
            });

        $dbDriver = $this->createMock(DatabaseDriver::class);
        $dbDriver->method('query')->willReturn(false);
        $dbDriver->method('fetchObject')->willReturn(null);
        $this->configuration->method('getDb')->willReturn($dbDriver);

        $logger = $this->createMock(Logger::class);
        $this->configuration->method('getLogger')->willReturn($logger);

        $this->mail->method('setReplyTo')->willReturn(true);
        $this->mail->method('addTo')->willReturn(true);
        $this->mail->method('addCc')->willReturn(true);

        $this->notification = new Notification($this->configuration, null, $this->mail, $this->faq, $this->category);
    }

    public function testConstructorCreatesInstance(): void
    {
        $this->assertInstanceOf(Notification::class, $this->notification);
    }

    public function testSendOpenQuestionAnsweredWithNotificationsEnabled(): void
    {
        $this->mail
            ->expects($this->once())
            ->method('addTo')
            ->with('user@example.com', 'Test User');
        $this->mail->expects($this->once())->method('send');

        $this->notification->sendOpenQuestionAnswered(
            'user@example.com',
            'Test User',
            'https://example.com/faq/1/answer.html',
        );
    }

    public function testSendOpenQuestionAnsweredWithNotificationsDisabled(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getNoReplyEmail')->willReturn('noreply@example.com');
        $configuration->method('getTitle')->willReturn('phpMyFAQ Test');
        $configuration
            ->method('get')
            ->willReturnCallback(function (string $item) {
                return match ($item) {
                    'main.enableNotifications' => false,
                    'mail.remoteSMTP' => false,
                    default => null,
                };
            });

        $mail = $this->createMock(Mail::class);
        $mail->method('setReplyTo')->willReturn(true);
        $mail->expects($this->never())->method('send');

        $notification = new Notification($configuration, null, $mail, $this->faq, $this->category);
        $notification->sendOpenQuestionAnswered('user@example.com', 'Test User', 'https://example.com/faq.html');
    }

    public function testSendOpenQuestionAnsweredSetsCorrectSubject(): void
    {
        $this->mail->expects($this->once())->method('send');

        $this->notification->sendOpenQuestionAnswered(
            'user@example.com',
            'Test User',
            'https://example.com/faq/1/answer.html',
        );

        $this->assertStringContainsString('phpMyFAQ Test', $this->mail->subject);
    }

    public function testSendNewFaqAdded(): void
    {
        $faqEntity = new FaqEntity();
        $faqEntity->setId(42);
        $faqEntity->setLanguage('en');

        $this->faq
            ->method('getQuestion')
            ->with(42)
            ->willReturn('How to test?');
        $this->faq
            ->expects($this->once())
            ->method('getFaq')
            ->with(42, null, true);
        $this->faq->faqRecord = ['content' => 'Test content'];

        $this->mail
            ->expects($this->once())
            ->method('addTo')
            ->with('admin@example.com');
        $this->mail->expects($this->once())->method('send');

        $this->notification->sendNewFaqAdded(['extra@example.com'], $faqEntity);

        $this->assertStringContainsString('New FAQ was added', $this->mail->subject);
        $this->assertEquals('text/html', $this->mail->contentType);
    }

    public function testSendNewFaqAddedSkipsDuplicateAdminEmail(): void
    {
        $faqEntity = new FaqEntity();
        $faqEntity->setId(42);
        $faqEntity->setLanguage('en');

        $this->faq->method('getQuestion')->willReturn('How to test?');
        $this->faq->faqRecord = ['content' => 'Test content'];

        // admin@example.com is already in addTo, so addCc should NOT be called for it
        $this->mail->expects($this->never())->method('addCc');
        $this->mail->expects($this->once())->method('send');

        $this->notification->sendNewFaqAdded(['admin@example.com'], $faqEntity);
    }

    public function testSendNewFaqAddedAddsCcForExtraEmails(): void
    {
        $faqEntity = new FaqEntity();
        $faqEntity->setId(42);
        $faqEntity->setLanguage('en');

        $this->faq->method('getQuestion')->willReturn('How to test?');
        $this->faq->faqRecord = ['content' => 'Test content'];

        $this->mail->expects($this->exactly(2))->method('addCc');
        $this->mail->expects($this->once())->method('send');

        $this->notification->sendNewFaqAdded(['user1@example.com', 'user2@example.com'], $faqEntity);
    }

    public function testSendNewsCommentNotification(): void
    {
        $comment = new Comment();
        $comment->setUsername('John');
        $comment->setEmail('john@example.com');
        $comment->setComment('Great news article!');

        $newsData = [
            'id' => 1,
            'lang' => 'en',
            'header' => 'Test News',
            'authorEmail' => 'author@example.com',
        ];

        $this->mail
            ->expects($this->once())
            ->method('addTo')
            ->with('author@example.com');
        $this->mail
            ->expects($this->once())
            ->method('setReplyTo')
            ->with('john@example.com', 'John');
        $this->mail->expects($this->once())->method('send');

        $this->notification->sendNewsCommentNotification($newsData, $comment);

        $this->assertStringContainsString('New comment for "Test News"', $this->mail->subject);
    }

    public function testSendNewsCommentNotificationWithEmptyAuthorEmail(): void
    {
        $comment = new Comment();
        $comment->setUsername('John');
        $comment->setEmail('john@example.com');
        $comment->setComment('Great news article!');

        $newsData = [
            'id' => 1,
            'lang' => 'en',
            'header' => 'Test News',
            'authorEmail' => '',
        ];

        // addTo should not be called when authorEmail is empty
        $this->mail->expects($this->never())->method('addTo');
        $this->mail->expects($this->once())->method('send');

        $this->notification->sendNewsCommentNotification($newsData, $comment);
    }

    public function testSendFaqCommentNotification(): void
    {
        $faq = $this->createMock(Faq::class);
        $faq->faqRecord = [
            'id' => 10,
            'email' => 'faqauthor@example.com',
            'title' => 'Test FAQ Title',
            'lang' => 'en',
        ];

        $comment = new Comment();
        $comment->setUsername('Commenter');
        $comment->setEmail('commenter@example.com');
        $comment->setComment('This is a test comment on the FAQ.');

        $this->mail
            ->expects($this->once())
            ->method('setReplyTo')
            ->with('commenter@example.com', 'Commenter');
        $this->mail
            ->expects($this->once())
            ->method('addTo')
            ->with('faqauthor@example.com');
        $this->mail->expects($this->once())->method('send');

        $this->notification->sendFaqCommentNotification($faq, $comment);

        $this->assertStringContainsString('New comment for "Test FAQ Title"', $this->mail->subject);
    }

    public function testSendFaqCommentNotificationUsesAdminEmailWhenFaqEmailEmpty(): void
    {
        $faq = $this->createMock(Faq::class);
        $faq->faqRecord = [
            'id' => 10,
            'email' => '',
            'title' => 'Test FAQ Title',
            'lang' => 'en',
        ];

        $comment = new Comment();
        $comment->setUsername('Commenter');
        $comment->setEmail('commenter@example.com');
        $comment->setComment('This is a test comment.');

        $this->mail
            ->expects($this->once())
            ->method('addTo')
            ->with('admin@example.com');
        $this->mail->expects($this->once())->method('send');

        $this->notification->sendFaqCommentNotification($faq, $comment);
    }

    public function testSendQuestionSuccessMailWithWebPushDisabled(): void
    {
        $questionEntity = new QuestionEntity();
        $questionEntity->setUsername('Jane');
        $questionEntity->setEmail('jane@example.com');
        $questionEntity->setCategoryId(5);
        $questionEntity->setQuestion('How do I reset my password?');

        $categories = [5 => ['name' => 'User Account']];

        $this->category
            ->method('getOwner')
            ->with(5)
            ->willReturn(0);

        // With null webPushService, push should be skipped silently
        $this->notification->sendQuestionSuccessMail($questionEntity, $categories);

        // If we get here without exception, the method handled everything correctly
        $this->assertTrue(true);
    }

    public function testSendQuestionSuccessMailWithWebPushEnabled(): void
    {
        $webPushService = $this->createMock(WebPushService::class);
        $webPushService->method('isEnabled')->willReturn(true);
        $webPushService->expects($this->once())->method('sendToUsers');

        $mail = $this->createMock(Mail::class);
        $mail->method('setReplyTo')->willReturn(true);
        $mail->method('addTo')->willReturn(true);
        $mail->method('addCc')->willReturn(true);
        $mail->method('send')->willReturn(1);

        $notification = new Notification($this->configuration, $webPushService, $mail, $this->faq, $this->category);

        $questionEntity = new QuestionEntity();
        $questionEntity->setUsername('Jane');
        $questionEntity->setEmail('jane@example.com');
        $questionEntity->setCategoryId(5);
        $questionEntity->setQuestion('How do I reset my password?');

        $categories = [5 => ['name' => 'User Account']];

        $this->category
            ->method('getOwner')
            ->with(5)
            ->willReturn(10);

        $notification->sendQuestionSuccessMail($questionEntity, $categories);
    }

    public function testSendWebPushToUsersHandlesException(): void
    {
        $webPushService = $this->createMock(WebPushService::class);
        $webPushService->method('isEnabled')->willReturn(true);
        $webPushService->method('sendToUsers')->willThrowException(new \RuntimeException('Push failed'));

        $mail = $this->createMock(Mail::class);
        $mail->method('setReplyTo')->willReturn(true);
        $mail->method('addTo')->willReturn(true);
        $mail->method('send')->willReturn(1);

        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getNoReplyEmail')->willReturn('noreply@example.com');
        $configuration->method('getTitle')->willReturn('phpMyFAQ Test');
        $configuration->method('getAdminEmail')->willReturn('admin@example.com');
        $configuration->method('getDefaultUrl')->willReturn('https://example.com/');
        $configuration->method('getLogger')->willReturn($this->createLogger());
        $configuration
            ->method('get')
            ->willReturnCallback(function (string $item) {
                return match ($item) {
                    'main.enableNotifications' => true,
                    'security.permLevel' => 'basic',
                    'mail.remoteSMTP' => false,
                    default => null,
                };
            });

        $dbDriver = $this->createMock(DatabaseDriver::class);
        $dbDriver->method('query')->willReturn(false);
        $dbDriver->method('fetchObject')->willReturn(null);
        $configuration->method('getDb')->willReturn($dbDriver);

        $notification = new Notification($configuration, $webPushService, $mail, $this->faq, $this->category);

        $questionEntity = new QuestionEntity();
        $questionEntity->setUsername('Jane');
        $questionEntity->setEmail('jane@example.com');
        $questionEntity->setCategoryId(5);
        $questionEntity->setQuestion('How do I reset my password?');

        $categories = [5 => ['name' => 'User Account']];

        $this->category
            ->method('getOwner')
            ->with(5)
            ->willReturn(0);

        // Should not throw - exception is logged
        $notification->sendQuestionSuccessMail($questionEntity, $categories);
    }

    private function createLogger(): Logger
    {
        $logger = new Logger('phpmyfaq-test');
        $logger->pushHandler(new NullHandler());

        return $logger;
    }
}
