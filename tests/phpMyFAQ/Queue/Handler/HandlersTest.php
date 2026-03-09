<?php

namespace phpMyFAQ\Queue\Handler;

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Faq;
use phpMyFAQ\Instance\Search\Elasticsearch;
use phpMyFAQ\Language;
use phpMyFAQ\Mail;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Queue\Message\ExportMessage;
use phpMyFAQ\Queue\Message\IndexFaqMessage;
use phpMyFAQ\Queue\Message\SendMailMessage;
use phpMyFAQ\User;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class HandlersTest extends TestCase
{
    public function testSendMailHandlerAcceptsConfiguration(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $handler = new SendMailHandler($configuration);

        $this->assertInstanceOf(SendMailHandler::class, $handler);
    }

    public function testSendMailHandlerSendsPreparedEnvelopeWhenMetadataContainsOne(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $mail = $this->createMock(Mail::class);
        $mail
            ->expects($this->once())
            ->method('sendPreparedEnvelope')
            ->with('alice@example.org', ['X-Test' => '1'], 'Body');

        $handler = new SendMailHandler($configuration, static fn(): Mail => $mail);
        $handler(new SendMailMessage('unused@example.org', 'Ignored', 'Ignored', [
            'envelope' => [
                'recipients' => 'alice@example.org',
                'headers' => ['X-Test' => '1'],
                'body' => 'Body',
            ],
        ]));
    }

    public function testSendMailHandlerSendsRegularMailWhenNoPreparedEnvelopeExists(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $mail = $this->createMock(Mail::class);
        $mail->expects($this->once())->method('addTo')->with('bob@example.org');
        $mail->expects($this->once())->method('send')->with(true);

        $handler = new SendMailHandler($configuration, static fn(): Mail => $mail);
        $handler(new SendMailMessage('bob@example.org', 'Subject', 'Message'));

        $this->assertSame('Subject', $mail->subject);
        $this->assertSame('Message', $mail->message);
    }

    public function testIndexFaqHandlerThrowsWhenElasticsearchNotConfigured(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('isElasticsearchActive')->willReturn(false);

        $handler = new IndexFaqHandler($configuration);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Elasticsearch is not configured');
        $handler(new IndexFaqMessage(1, 'en'));
    }

    public function testIndexFaqHandlerIndexesActiveFaq(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('isElasticsearchActive')->willReturn(true);

        $faq = $this->createMock(Faq::class);
        $faq->faqRecord = [
            'id' => 42,
            'active' => 'yes',
            'content' => 'Answer',
            'lang' => 'de',
            'solution_id' => 99,
            'title' => 'Question',
            'keywords' => 'foo,bar',
        ];
        $faq->expects($this->once())->method('getFaq')->with(42);

        $category = $this->createMock(Category::class);
        $category->expects($this->once())->method('getCategoryIdFromFaq')->with(42)->willReturn(7);

        $elasticsearch = $this->createMock(Elasticsearch::class);
        $elasticsearch
            ->expects($this->once())
            ->method('index')
            ->with([
                'id' => 42,
                'lang' => 'de',
                'solution_id' => 99,
                'question' => 'Question',
                'answer' => 'Answer',
                'keywords' => 'foo,bar',
                'category_id' => 7,
            ]);

        $handler = new IndexFaqHandler(
            $configuration,
            static fn(): Faq => $faq,
            static fn(): Category => $category,
            static fn(): Elasticsearch => $elasticsearch,
        );

        $handler(new IndexFaqMessage(42));
    }

    public function testExportHandlerAcceptsConfiguration(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $handler = new ExportHandler($configuration);

        $this->assertInstanceOf(ExportHandler::class, $handler);
    }

    public function testExportHandlerThrowsForUnknownUser(): void
    {
        $configuration = new Configuration($this->createSqliteDatabase());
        $language = new Language($configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $configuration->setLanguage($language);
        $handler = new ExportHandler($configuration);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Export requested by unknown user ID 999999');
        $handler(new ExportMessage('pdf', 999999));
    }

    public function testExportHandlerThrowsWhenUserLacksExportPermission(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $permission = $this->createMock(PermissionInterface::class);
        $permission->expects($this->once())->method('hasPermission')->with(5, 'export')->willReturn(false);

        $user = $this->createMock(User::class);
        $user->perm = $permission;
        $user->expects($this->once())->method('getUserById')->with(5)->willReturn(true);

        $handler = new ExportHandler($configuration, static fn(): User => $user);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User ID 5 does not have export permission');
        $handler(new ExportMessage('pdf', 5));
    }

    public function testExportHandlerThrowsWhenGeneratedContentIsEmpty(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $permission = $this->createMock(PermissionInterface::class);
        $permission->expects($this->once())->method('hasPermission')->with(5, 'export')->willReturn(true);

        $user = $this->createMock(User::class);
        $user->perm = $permission;
        $user->expects($this->once())->method('getUserById')->with(5)->willReturn(true);

        $faq = $this->createMock(Faq::class);
        $category = $this->createMock(Category::class);
        $exporter = new class {
            public function generate(int $categoryId = 0, bool $downwards = true, string $language = ''): string
            {
                return '';
            }
        };

        $handler = new ExportHandler(
            $configuration,
            static fn(): User => $user,
            static fn(): Faq => $faq,
            static fn(): Category => $category,
            static fn(Faq $faq, Category $category, string $format): object => $exporter,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Export generated empty content');
        $handler(new ExportMessage('pdf', 5));
    }

    public function testIndexFaqHandlerReturnsWithoutIndexingWhenFaqIsMissing(): void
    {
        $configuration = new class($this->createSqliteDatabase()) extends Configuration {
            public function isElasticsearchActive(): bool
            {
                return true;
            }
        };
        $language = new Language($configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $configuration->setLanguage($language);

        $handler = new IndexFaqHandler($configuration);

        $handler(new IndexFaqMessage(999999, 'en'));
        $this->assertTrue(true);
    }

    private function createSqliteDatabase(): Sqlite3
    {
        $database = new Sqlite3();
        $database->connect(PMF_TEST_DIR . '/test.db', '', '');

        return $database;
    }
}
