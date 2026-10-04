<?php

declare(strict_types=1);

namespace phpMyFAQ\Attachment;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\TestDatabaseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Stores, reads and deletes attachments through the real factory, storage and metadata
 * code against a private database copy and temporary directories.
 */
#[CoversClass(File::class)]
#[UsesNamespace('phpMyFAQ')]
final class AttachmentFileRoundTripTest extends TestCase
{
    use TestDatabaseTrait;

    private const string KEY_32 = '0123456789abcdef0123456789abcdef';

    private Configuration $configuration;

    private string $storageRoot;

    private string $sourceFile;

    /** @var array<string, mixed> */
    private array $previousFactoryState = [];

    private mixed $previousDatabaseDriver = null;

    private mixed $previousDatabaseType = null;

    /** The hash-named directory tree an encrypted attachment leaves below the attachments root. */
    private ?string $encryptedDirectory = null;

    protected function setUp(): void
    {
        $this->configuration = $this->createTestConfiguration();

        $this->previousDatabaseDriver = new ReflectionProperty(Database::class, 'databaseDriver')->getValue();
        $this->previousDatabaseType = new ReflectionProperty(Database::class, 'dbType')->getValue();
        new ReflectionProperty(Database::class, 'databaseDriver')->setValue(null, $this->configuration->getDb());
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, 'sqlite3');

        foreach (['defaultKey', 'storageType', 'encryptionEnabled'] as $name) {
            $this->previousFactoryState[$name] = new ReflectionProperty(AttachmentFactory::class, $name)->getValue();
        }

        $this->storageRoot = sys_get_temp_dir() . '/pmf-attachment-' . bin2hex(random_bytes(6));
        $this->configuration->set('storage.type', 'filesystem');
        $this->configuration->set('storage.filesystem.root', $this->storageRoot);

        $sourceFile = tempnam(sys_get_temp_dir(), 'pmf-attachment-src-');
        self::assertNotFalse($sourceFile);
        file_put_contents($sourceFile, 'attachment payload');
        $this->sourceFile = $sourceFile;
    }

    protected function tearDown(): void
    {
        foreach ($this->previousFactoryState as $name => $value) {
            new ReflectionProperty(AttachmentFactory::class, $name)->setValue(null, $value);
        }

        new ReflectionProperty(Database::class, 'databaseDriver')->setValue(null, $this->previousDatabaseDriver);
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, $this->previousDatabaseType);

        new Filesystem()->remove(array_filter([$this->storageRoot, $this->sourceFile, $this->encryptedDirectory]));
    }

    /**
     * @return list<string>
     */
    private function storedFiles(): array
    {
        if (!is_dir($this->storageRoot)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->storageRoot, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function metaRowCount(int $attachmentId): int
    {
        $db = $this->configuration->getDb();
        $row = $db->fetchArray($db->query(sprintf(
            'SELECT COUNT(*) AS n FROM %sfaqattachment WHERE id = %d',
            Database::getTablePrefix(),
            $attachmentId,
        )));

        return (int) ($row['n'] ?? 0);
    }

    public function testPlainAttachmentIsStoredReadAndDeleted(): void
    {
        AttachmentFactory::init('', false);

        $attachment = AttachmentFactory::create();
        $attachment->setRecordId(1);
        $attachment->setRecordLang('en');

        $this->assertTrue($attachment->save($this->sourceFile, 'manual.txt'));
        $attachmentId = $attachment->getId();
        $this->assertGreaterThan(0, $attachmentId);
        $this->assertSame('manual.txt', $attachment->getFilename());
        $this->assertFalse($attachment->isEncrypted());
        $this->assertCount(1, $this->storedFiles());
        $this->assertSame(1, $this->metaRowCount($attachmentId));

        $this->assertSame('attachment payload', $attachment->get());

        ob_start();
        $attachment->rawOut();
        $this->assertSame('attachment payload', ob_get_clean());

        $reloaded = AttachmentFactory::create($attachmentId);
        $this->assertTrue($reloaded->hasMeta());
        $this->assertSame('attachment payload', $reloaded->get());

        $this->assertTrue($reloaded->delete());
        $this->assertSame([], $this->storedFiles());
        $this->assertSame(0, $this->metaRowCount($attachmentId));
    }

    public function testEncryptedAttachmentIsStoredReadAndDeleted(): void
    {
        AttachmentFactory::init(self::KEY_32, true);

        $attachment = AttachmentFactory::create();
        $attachment->setRecordId(1);
        $attachment->setRecordLang('en');

        $this->assertTrue($attachment->save($this->sourceFile, 'secret.txt'));
        $attachmentId = $attachment->getId();
        $this->assertTrue($attachment->isEncrypted());

        // Encrypted files live below the attachments root (the temp dir when none is configured)
        // in directories named after the virtual hash; remember the top one for the cleanup.
        $virtualHash = (string) new ReflectionProperty(AbstractAttachment::class, 'virtualHash')->getValue($attachment);
        $attachmentsRoot = defined('PMF_ATTACHMENTS_DIR') ? (string) constant('PMF_ATTACHMENTS_DIR') : sys_get_temp_dir();
        $this->encryptedDirectory = rtrim($attachmentsRoot, '/') . '/' . substr($virtualHash, 0, 5);
        $this->assertDirectoryExists($this->encryptedDirectory);
        $this->assertSame([], $this->storedFiles(), 'Encrypted files bypass the storage abstraction.');
        $this->assertSame(1, $this->metaRowCount($attachmentId));

        $this->assertSame('attachment payload', $attachment->get());

        ob_start();
        $attachment->rawOut();
        $this->assertSame('attachment payload', ob_get_clean());

        $reloaded = AttachmentFactory::create($attachmentId);
        $this->assertTrue($reloaded->isEncrypted());
        $this->assertSame('attachment payload', $reloaded->get());

        $this->assertTrue($reloaded->delete());
        $this->assertSame(0, $this->metaRowCount($attachmentId));
    }

    public function testASecondSaveOfTheSameContentReusesTheStoredFile(): void
    {
        AttachmentFactory::init('', false);

        $first = AttachmentFactory::create();
        $first->setRecordId(1);
        $first->setRecordLang('en');
        $this->assertTrue($first->save($this->sourceFile, 'manual.txt'));

        $second = AttachmentFactory::create();
        $second->setRecordId(2);
        $second->setRecordLang('en');
        $this->assertTrue($second->save($this->sourceFile, 'manual.txt'));

        $this->assertCount(1, $this->storedFiles(), 'Identical content is stored once.');

        // Deleting one record keeps the file for the other one.
        $this->assertTrue($first->delete());
        $this->assertCount(1, $this->storedFiles());
        $this->assertSame('attachment payload', $second->get());

        $this->assertTrue($second->delete());
        $this->assertSame([], $this->storedFiles());
    }
}
