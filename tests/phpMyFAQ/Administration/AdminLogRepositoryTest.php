<?php

declare(strict_types=1);

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\AdminLog as AdminLogEntity;
use phpMyFAQ\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(AdminLogRepository::class)]
#[UsesNamespace('phpMyFAQ')]
final class AdminLogRepositoryTest extends TestCase
{
    private string $databaseFile;

    private AdminLogRepository $repository;

    private User $user;

    protected function setUp(): void
    {
        $this->databaseFile = tempnam(sys_get_temp_dir(), 'phpmyfaq-adminlog-repository-');
        copy(PMF_TEST_DIR . '/test.db', $this->databaseFile);

        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');
        $configuration = new Configuration($dbHandle);

        $this->repository = new AdminLogRepository($configuration);

        $this->user = $this->createStub(User::class);
        $this->user->method('getUserId')->willReturn(5);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->databaseFile)) {
            @unlink($this->databaseFile);
        }
    }

    private function createRequest(int $time, string $ip = '192.0.2.10'): Request
    {
        return Request::create('/admin', 'GET', server: ['REMOTE_ADDR' => $ip, 'REQUEST_TIME' => $time]);
    }

    public function testEmptyLogHasNoEntriesAndNoLastHash(): void
    {
        $this->assertSame(0, $this->repository->getNumberOfEntries());
        $this->assertSame([], $this->repository->getAll());
        $this->assertNull($this->repository->getLastHash());
    }

    public function testAddStoresEntryWithRequestMetadataAndHash(): void
    {
        $this->assertTrue($this->repository->add($this->user, 'Login succeeded', $this->createRequest(1_700_000_000)));

        $entries = $this->repository->getAll();

        $this->assertCount(1, $entries);
        $this->assertSame(1, $this->repository->getNumberOfEntries());

        $entry = array_values($entries)[0];
        $this->assertInstanceOf(AdminLogEntity::class, $entry);
        $this->assertSame(array_key_first($entries), $entry->getId());
        $this->assertSame(1_700_000_000, $entry->getTime());
        $this->assertSame(5, $entry->getUserId());
        $this->assertSame('192.0.2.10', $entry->getIp());
        $this->assertSame('Login succeeded', $entry->getText());
        $this->assertNotNull($entry->getHash());
        $this->assertNull($entry->getPreviousHash());
        $this->assertTrue($entry->verifyIntegrity());
    }

    public function testEntriesFormAHashChain(): void
    {
        $this->repository->add($this->user, 'first', $this->createRequest(1_700_000_000));
        $firstHash = $this->repository->getLastHash();
        $this->assertNotNull($firstHash);

        $this->repository->add($this->user, 'second', $this->createRequest(1_700_000_001), $firstHash);
        $secondHash = $this->repository->getLastHash();

        $this->assertNotNull($secondHash);
        $this->assertNotSame($firstHash, $secondHash);

        $entries = array_values($this->repository->getAll());
        $this->assertSame(['first', 'second'], [$entries[0]->getText(), $entries[1]->getText()]);
        $this->assertSame($firstHash, $entries[0]->getHash());
        $this->assertSame($firstHash, $entries[1]->getPreviousHash());
        $this->assertSame($secondHash, $entries[1]->getHash());
        $this->assertTrue($entries[1]->verifyIntegrity());
    }

    public function testControlCharactersCannotForgeAdditionalLines(): void
    {
        $this->repository->add($this->user, "Login failed for user\r\nadmin\tlogin ok\x00", $this->createRequest(1_700_000_000));

        $entry = array_values($this->repository->getAll())[0];

        $this->assertSame('Login failed for user admin login ok ', $entry->getText());
        $this->assertTrue($entry->verifyIntegrity());
    }

    public function testQuotesInLogTextAreEscaped(): void
    {
        $this->repository->add($this->user, "Deleted FAQ 'O'Brien's guide'", $this->createRequest(1_700_000_000));

        $this->assertSame("Deleted FAQ 'O'Brien's guide'", array_values($this->repository->getAll())[0]->getText());
    }

    public function testDeleteOlderThanRemovesOnlyOlderEntries(): void
    {
        $this->repository->add($this->user, 'old', $this->createRequest(1_000));
        $this->repository->add($this->user, 'boundary', $this->createRequest(2_000));
        $this->repository->add($this->user, 'new', $this->createRequest(3_000));

        $this->assertTrue($this->repository->deleteOlderThan(2_000));

        $texts = array_map(static fn(AdminLogEntity $entry): string => $entry->getText(), array_values($this->repository->getAll()));
        $this->assertSame(['boundary', 'new'], $texts);
        $this->assertSame(2, $this->repository->getNumberOfEntries());
    }

    public function testGetLastHashReturnsHashOfNewestEntry(): void
    {
        $this->repository->add($this->user, 'first', $this->createRequest(1_700_000_000));
        $this->repository->add($this->user, 'second', $this->createRequest(1_700_000_001));

        $entries = array_values($this->repository->getAll());

        $this->assertSame($entries[1]->getHash(), $this->repository->getLastHash());
    }
}
