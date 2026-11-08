<?php

namespace phpMyFAQ\Queue\Handler;

use Elastic\Elasticsearch\Client;
use Elastic\Transport\NodePool\Node;
use Elastic\Transport\NodePool\NodePoolInterface;
use Elastic\Transport\Transport;
use GuzzleHttp\Psr7\Response;
use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\ElasticsearchConfiguration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\FaqStatus;
use phpMyFAQ\Faq;
use phpMyFAQ\Instance\Search\Elasticsearch;
use phpMyFAQ\Language;
use phpMyFAQ\Mail;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Queue\Message\ExportMessage;
use phpMyFAQ\Queue\Message\IndexFaqMessage;
use phpMyFAQ\Queue\Message\SendMailMessage;
use phpMyFAQ\TestDatabaseTrait;
use phpMyFAQ\User;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\NullLogger;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class HandlersTest extends TestCase
{
    use TestDatabaseTrait;

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
            'status' => FaqStatus::Published->value,
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

    public function testIndexFaqHandlerRemovesUnpublishedFaqFromIndex(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('isElasticsearchActive')->willReturn(true);

        $faq = $this->createMock(Faq::class);
        $faq->faqRecord = [
            'id' => 42,
            'status' => FaqStatus::Review->value,
            'content' => 'Answer',
            'lang' => 'de',
            'solution_id' => 99,
            'title' => 'Question',
            'keywords' => 'foo,bar',
        ];
        $faq->expects($this->once())->method('getFaq')->with(42);

        $elasticsearch = $this->createMock(Elasticsearch::class);
        $elasticsearch->expects($this->once())->method('delete')->with(99);
        $elasticsearch->expects($this->never())->method('index');

        $handler = new IndexFaqHandler(
            $configuration,
            static fn(): Faq => $faq,
            null,
            static fn(): Elasticsearch => $elasticsearch,
        );

        $handler(new IndexFaqMessage(42));
    }

    public function testIndexFaqHandlerLooksUpTheCategoryItselfWithoutACategoryFactory(): void
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

        $faq = $this->createMock(Faq::class);
        $faq->faqRecord = [
            'id' => 1,
            'status' => FaqStatus::Published->value,
            'content' => 'Answer',
            'lang' => 'en',
            'solution_id' => 1000,
            'title' => 'Question',
            'keywords' => '',
        ];
        $faq->expects($this->once())->method('getFaq')->with(1);

        $expectedCategoryId = new Category($configuration)->getCategoryIdFromFaq(1);

        $elasticsearch = $this->createMock(Elasticsearch::class);
        $elasticsearch
            ->expects($this->once())
            ->method('index')
            ->with($this->callback(
                static fn(array $document): bool => $document['category_id'] === $expectedCategoryId,
            ));

        $handler = new IndexFaqHandler(
            $configuration,
            static fn(): Faq => $faq,
            null,
            static fn(): Elasticsearch => $elasticsearch,
        );

        $handler(new IndexFaqMessage(1, 'en'));
    }

    public function testIndexFaqHandlerBuildsTheElasticsearchClientItselfWithoutAFactory(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(
                static fn(RequestInterface $request): bool => $request->getMethod() === 'DELETE'
                && str_contains($request->getUri()->getPath(), '/phpmyfaq_queue/_doc/99'),
            ))
            ->willReturn(
                new Response(
                    200,
                    ['Content-Type' => 'application/json', 'X-Elastic-Product' => 'Elasticsearch'],
                    '{"result":"deleted"}',
                ),
            );

        $elasticsearchConfiguration = $this->createStub(ElasticsearchConfiguration::class);
        $elasticsearchConfiguration->method('getIndex')->willReturn('phpmyfaq_queue');

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('isElasticsearchActive')->willReturn(true);
        $configuration->method('getElasticsearch')->willReturn($this->createElasticClient($httpClient));
        $configuration->method('getElasticsearchConfig')->willReturn($elasticsearchConfiguration);
        $configuration->method('getDefaultLanguage')->willReturn('en');

        $faq = $this->createMock(Faq::class);
        $faq->faqRecord = [
            'id' => 42,
            'status' => FaqStatus::Review->value,
            'content' => 'Answer',
            'lang' => 'de',
            'solution_id' => 99,
            'title' => 'Question',
            'keywords' => '',
        ];
        $faq->expects($this->once())->method('getFaq')->with(42);

        $handler = new IndexFaqHandler($configuration, static fn(): Faq => $faq);

        $handler(new IndexFaqMessage(42));
    }

    /**
     * The Elasticsearch client is final: build it via reflection around a mocked PSR-18 transport.
     */
    private function createElasticClient(HttpClientInterface $httpClient): Client
    {
        $clientReflection = new ReflectionClass(Client::class);
        $client = $clientReflection->newInstanceWithoutConstructor();

        $nodePool = $this->createStub(NodePoolInterface::class);
        $nodePool->method('nextNode')->willReturn(new Node('http://localhost:9200'));

        $clientReflection->getProperty('transport')->setValue(
            $client,
            new Transport($httpClient, $nodePool, new NullLogger()),
        );
        $clientReflection->getProperty('logger')->setValue($client, new NullLogger());
        $clientReflection->getProperty('responseException')->setValue($client, true);
        $clientReflection->getProperty('elasticMetaHeader')->setValue($client, false);
        $clientReflection->getProperty('async')->setValue($client, false);

        return $client;
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

    public function testExportHandlerFallsBackToTheBuiltInExportersWhenTheFactoryReturnsNoExporter(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $permission = $this->createMock(PermissionInterface::class);
        $permission->expects($this->once())->method('hasPermission')->with(5, 'export')->willReturn(true);

        $user = $this->createMock(User::class);
        $user->perm = $permission;
        $user->expects($this->once())->method('getUserById')->with(5)->willReturn(true);

        $faq = $this->createMock(Faq::class);
        $category = $this->createMock(Category::class);

        $handler = new ExportHandler(
            $configuration,
            static fn(): User => $user,
            static fn(): Faq => $faq,
            static fn(): Category => $category,
            static fn(Faq $faq, Category $category, string $format): mixed => null,
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Export not implemented!');
        $handler(new ExportMessage('xml', 5));
    }

    public function testExportHandlerWritesExportsBelowContentCore(): void
    {
        $this->assertSame(PMF_ROOT_DIR . '/content/core/exports', ExportHandler::getExportDirectory());

        $configuration = $this->createStub(Configuration::class);
        $permission = $this->createMock(PermissionInterface::class);
        $permission->expects($this->once())->method('hasPermission')->with(5, 'export')->willReturn(true);

        $user = $this->createMock(User::class);
        $user->perm = $permission;
        $user->expects($this->once())->method('getUserById')->with(5)->willReturn(true);
        $user->expects($this->once())->method('getUserData')->with('email')->willReturn('');

        $faq = $this->createMock(Faq::class);
        $category = $this->createMock(Category::class);
        $exporter = new class {
            public function generate(int $categoryId = 0, bool $downwards = true, string $language = ''): string
            {
                return '{"export":true}';
            }
        };

        $handler = new ExportHandler(
            $configuration,
            static fn(): User => $user,
            static fn(): Faq => $faq,
            static fn(): Category => $category,
            static fn(Faq $faq, Category $category, string $format): object => $exporter,
        );

        $before = glob(ExportHandler::getExportDirectory() . '/export-5-*.json') ?: [];
        $handler(new ExportMessage('json', 5));
        $written = array_diff(glob(ExportHandler::getExportDirectory() . '/export-5-*.json') ?: [], $before);

        try {
            $this->assertCount(1, $written);
            $this->assertStringEqualsFile((string) reset($written), '{"export":true}');
            $this->assertFileDoesNotExist(PMF_ROOT_DIR . '/content/user/exports');
        } finally {
            foreach ($written as $file) {
                unlink($file);
            }
        }
    }

    public function testExportHandlerLooksUpTheCategoryItselfWithoutACategoryFactory(): void
    {
        $configuration = new Configuration($this->createSqliteDatabase());
        $language = new Language($configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $configuration->setLanguage($language);

        $permission = $this->createStub(PermissionInterface::class);
        $permission->method('hasPermission')->willReturn(true);

        $user = $this->createStub(User::class);
        $user->perm = $permission;
        $user->method('getUserById')->willReturn(true);
        $user->method('getUserData')->willReturn('');

        $receivedCategory = null;
        $exporter = new class {
            public function generate(int $categoryId = 0, bool $downwards = true, string $language = ''): string
            {
                return '{"export":"default-category"}';
            }
        };

        $handler = new ExportHandler(
            $configuration,
            static fn(): User => $user,
            fn(): Faq => $this->createStub(Faq::class),
            null,
            static function (Faq $faq, Category $category, string $format) use ($exporter, &$receivedCategory): object {
                $receivedCategory = $category;

                return $exporter;
            },
        );

        $written = $this->runExportAndCollectFiles($handler, 'json', 11);

        try {
            $this->assertInstanceOf(Category::class, $receivedCategory);
            $this->assertCount(1, $written);
        } finally {
            foreach ($written as $file) {
                unlink($file);
            }
        }
    }

    public function testExportHandlerFailsWhenTheExportDirectoryCannotBeCreated(): void
    {
        $exportDir = ExportHandler::getExportDirectory();
        $backupDir = $exportDir . '.pmf-test-backup';
        if (is_dir($exportDir) && !rename($exportDir, $backupDir)) {
            $this->markTestSkipped('Cannot move the export directory aside.');
        }

        // A regular file in the way of the directory makes mkdir() fail without any permission tricks.
        file_put_contents($exportDir, 'not a directory');
        [$handler] = $this->createExportHandlerForMailDelivery('');

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Unable to create export directory: ' . $exportDir);

            $this->invokeSilencingWarnings($handler, new ExportMessage('json', 7));
        } finally {
            unlink($exportDir);
            if (is_dir($backupDir)) {
                rename($backupDir, $exportDir);
            }
        }
    }

    public function testExportHandlerFailsWhenTheExportFileCannotBeWritten(): void
    {
        $exportDir = ExportHandler::getExportDirectory();
        if (!is_dir($exportDir) && !mkdir($exportDir, 0o775, true) && !is_dir($exportDir)) {
            $this->markTestSkipped('Cannot create the export directory.');
        }

        // Occupy the file name the handler is about to use (this second and the next one) with a
        // directory, so file_put_contents() fails independently of file-mode enforcement.
        $now = time();
        $blockers = [];
        foreach ([$now, $now + 1] as $timestamp) {
            $blocker = sprintf('%s/export-7-%s.json', $exportDir, date('Y-m-d-H-i-s', $timestamp));
            if (!is_dir($blocker)) {
                mkdir($blocker);
            }

            $blockers[] = $blocker;
        }

        [$handler] = $this->createExportHandlerForMailDelivery('');

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Unable to write export file: ' . $exportDir . '/export-7-');

            $this->invokeSilencingWarnings($handler, new ExportMessage('json', 7));
        } finally {
            foreach ($blockers as $blocker) {
                rmdir($blocker);
            }
        }
    }

    /**
     * mkdir() and file_put_contents() raise E_WARNING on failure; PHPUnit would otherwise
     * report that warning instead of the RuntimeException the handler throws.
     */
    private function invokeSilencingWarnings(ExportHandler $handler, ExportMessage $message): void
    {
        set_error_handler(static fn(int $severity): bool => $severity === E_WARNING);

        try {
            $handler($message);
        } finally {
            restore_error_handler();
        }
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
        $database = $this->connectToTestDatabaseCopy(new Sqlite3());

        return $database;
    }

    /**
     * @return array{0: ExportHandler, 1: Mail&\PHPUnit\Framework\MockObject\MockObject}
     */
    private function createExportHandlerForMailDelivery(string $email): array
    {
        $configuration = $this->createStub(Configuration::class);
        $permission = $this->createStub(PermissionInterface::class);
        $permission->method('hasPermission')->willReturn(true);

        $user = $this->createStub(User::class);
        $user->perm = $permission;
        $user->method('getUserById')->willReturn(true);
        $user->method('getUserData')->willReturn($email);

        $exporter = new class {
            public function generate(int $categoryId = 0, bool $downwards = true, string $language = ''): string
            {
                return '%PDF-1.7 fake';
            }
        };

        $mail = $this->createMock(Mail::class);
        $faq = $this->createStub(Faq::class);
        $category = $this->createStub(Category::class);

        $handler = new ExportHandler(
            $configuration,
            static fn(): User => $user,
            static fn(): Faq => $faq,
            static fn(): Category => $category,
            static fn(Faq $faq, Category $category, string $format): object => $exporter,
            static fn(): Mail => $mail,
        );

        return [$handler, $mail];
    }

    /**
     * @return list<string>
     */
    private function runExportAndCollectFiles(ExportHandler $handler, string $format, int $userId = 7): array
    {
        $pattern = sprintf('%s/export-%d-*.%s', ExportHandler::getExportDirectory(), $userId, $format);
        $before = glob($pattern) ?: [];
        $handler(new ExportMessage($format, $userId));

        return array_values(array_diff(glob($pattern) ?: [], $before));
    }

    public function testExportHandlerMailsTheFileNameToTheRequestingUser(): void
    {
        [$handler, $mail] = $this->createExportHandlerForMailDelivery('export@example.org');
        $mail->expects($this->once())->method('addTo')->with('export@example.org');
        $mail->expects($this->once())->method('send')->willReturn(1);

        $written = $this->runExportAndCollectFiles($handler, 'pdf');

        try {
            $this->assertCount(1, $written);
            $this->assertSame('Your phpMyFAQ export is ready', $mail->subject);
            $this->assertStringContainsString('Your PDF export has been generated', $mail->message);
            $this->assertStringContainsString(basename($written[0]), $mail->message);
        } finally {
            foreach ($written as $file) {
                unlink($file);
            }
        }
    }

    public function testExportHandlerKeepsTheFileWhenTheNotificationCannotBeSent(): void
    {
        [$handler, $mail] = $this->createExportHandlerForMailDelivery('export@example.org');
        $mail->method('send')->willThrowException(new \RuntimeException('SMTP down'));

        $previousLog = ini_set('error_log', '/dev/null');
        try {
            $written = $this->runExportAndCollectFiles($handler, 'pdf');
        } finally {
            ini_set('error_log', (string) $previousLog);
        }

        try {
            $this->assertCount(1, $written);
            $this->assertStringEqualsFile($written[0], '%PDF-1.7 fake');
        } finally {
            foreach ($written as $file) {
                unlink($file);
            }
        }
    }
}
