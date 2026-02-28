<?php

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\AdminLog as AdminLogEntity;
use phpMyFAQ\System;
use phpMyFAQ\User;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AdminLogTest extends TestCase
{
    /** @var Configuration */
    private Configuration $configuration;

    /** @var AdminLog */
    private AdminLog $adminLog;

    private int $now;
    private string $databaseFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseFile = tempnam(sys_get_temp_dir(), 'phpmyfaq-admin-log-test-');
        copy(PMF_TEST_DIR . '/test.db', $this->databaseFile);

        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');
        $this->configuration = new Configuration($dbHandle);
        $this->configuration->set('main.currentVersion', System::getVersion());
        $this->configuration->set('main.enableAdminLog', true);

        $this->adminLog = new AdminLog($this->configuration);

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->now = time();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (isset($this->databaseFile) && file_exists($this->databaseFile)) {
            @unlink($this->databaseFile);
        }
    }

    public function testDelete(): void
    {
        $_SERVER['REQUEST_TIME'] = $this->now - (31 * 86400);
        $this->adminLog->log(new User($this->configuration), 'foo');
        $this->adminLog->log(new User($this->configuration), 'bar');
        $this->assertEquals(2, $this->adminLog->getNumberOfEntries());

        $_SERVER['REQUEST_TIME'] = $this->now;
        $this->assertTrue($this->adminLog->delete());
        $this->assertEquals(0, $this->adminLog->getNumberOfEntries());
    }

    public function testGetNumberOfEntries(): void
    {
        $this->adminLog->log(new User($this->configuration), 'foo');
        $this->assertEquals(1, $this->adminLog->getNumberOfEntries());

        $this->adminLog->log(new User($this->configuration), 'bar');
        $this->assertEquals(2, $this->adminLog->getNumberOfEntries());
    }

    /**
     * @throws Exception
     */
    public function testGetAll(): void
    {
        $_SERVER['REQUEST_TIME'] = $this->now;
        $this->adminLog->log(new User($this->configuration), 'foo');
        $this->adminLog->log(new User($this->configuration), 'bar');

        $result = $this->adminLog->getAll();

        $this->assertCount(2, $result);

        // Results are returned in reverse chronological order (newest first) with ID as key
        $entries = array_values($result);
        $this->assertInstanceOf(AdminLogEntity::class, $entries[0]);
        $this->assertInstanceOf(AdminLogEntity::class, $entries[1]);

        $this->assertEquals('bar', $entries[1]->getText());
        $this->assertEquals('foo', $entries[0]->getText());
        $this->assertEquals(-1, $entries[0]->getUserId());
        $this->assertEquals('127.0.0.1', $entries[0]->getIp());
    }

    public function testGetAllReturnsEmptyArrayWhenNoEntries(): void
    {
        $result = $this->adminLog->getAll();
        $this->assertCount(0, $result);
        $this->assertIsArray($result);
    }

    public function testLogReturnsTrueWhenEnabled(): void
    {
        $this->configuration->set('main.enableAdminLog', true);
        $result = $this->adminLog->log(new User($this->configuration), 'test entry');
        $this->assertTrue($result);
    }

    public function testLogReturnsFalseWhenDisabled(): void
    {
        $this->configuration->set('main.enableAdminLog', false);
        $result = $this->adminLog->log(new User($this->configuration), 'should not log');
        $this->assertFalse($result);
        $this->assertEquals(0, $this->adminLog->getNumberOfEntries());
    }

    public function testLogCreatesHashChain(): void
    {
        $_SERVER['REQUEST_TIME'] = $this->now;
        $this->configuration->set('main.enableAdminLog', true);

        $this->adminLog->log(new User($this->configuration), 'first');
        $this->adminLog->log(new User($this->configuration), 'second');

        $entries = array_values($this->adminLog->getAll());

        // First entry should have null previous hash
        $this->assertNull($entries[0]->getPreviousHash());
        $this->assertNotNull($entries[0]->getHash());

        // Second entry should reference first entry's hash
        $this->assertEquals($entries[0]->getHash(), $entries[1]->getPreviousHash());
        $this->assertNotNull($entries[1]->getHash());
    }

    public function testVerifyChainIntegrityWithEmptyLog(): void
    {
        $result = $this->adminLog->verifyChainIntegrity();

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['verified']);
    }

    public function testVerifyChainIntegrityWithValidChain(): void
    {
        $_SERVER['REQUEST_TIME'] = $this->now;
        $this->configuration->set('main.enableAdminLog', true);

        $this->adminLog->log(new User($this->configuration), 'entry one');
        $this->adminLog->log(new User($this->configuration), 'entry two');
        $this->adminLog->log(new User($this->configuration), 'entry three');

        $result = $this->adminLog->verifyChainIntegrity();

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
        $this->assertEquals(3, $result['total']);
        $this->assertEquals(3, $result['verified']);
    }

    public function testVerifyChainIntegrityDetectsTamperedData(): void
    {
        $_SERVER['REQUEST_TIME'] = $this->now;
        $this->configuration->set('main.enableAdminLog', true);

        $this->adminLog->log(new User($this->configuration), 'original text');

        // Tamper with the entry directly in the database
        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');
        $dbHandle->query("UPDATE faqadminlog SET text = 'tampered text'");

        // Re-read and verify - need fresh AdminLog to pick up changed data
        $tamperedLog = new AdminLog($this->configuration);
        $result = $tamperedLog->verifyChainIntegrity();

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('Hash verification failed', $result['errors'][0]);
    }

    public function testVerifyChainIntegrityDetectsBrokenChain(): void
    {
        $_SERVER['REQUEST_TIME'] = $this->now;
        $this->configuration->set('main.enableAdminLog', true);

        $this->adminLog->log(new User($this->configuration), 'first');
        $this->adminLog->log(new User($this->configuration), 'second');

        // Break the chain by modifying the previous_hash of the second entry
        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');

        // Get second entry and recalculate hash with wrong previous_hash to keep hash valid for its own data
        $entries = array_values($this->adminLog->getAll());
        $secondEntry = $entries[1];

        // Update previous_hash to break the chain, and recalculate hash so verifyIntegrity passes
        $secondEntry->setPreviousHash('0000000000000000000000000000000000000000000000000000000000000000');
        $newHash = $secondEntry->calculateHash();

        $dbHandle->query(sprintf(
            "UPDATE faqadminlog SET previous_hash = '%s', hash = '%s' WHERE id = %d",
            '0000000000000000000000000000000000000000000000000000000000000000',
            $newHash,
            $secondEntry->getId(),
        ));

        $brokenLog = new AdminLog($this->configuration);
        $result = $brokenLog->verifyChainIntegrity();

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('Chain broken', $result['errors'][0]);
    }

    public function testCalculateHash(): void
    {
        $entity = new AdminLogEntity();
        $entity->setTime(1700000000);
        $entity->setUserId(1);
        $entity->setIp('192.168.1.1');
        $entity->setText('test action');
        $entity->setPreviousHash(null);

        $hash = $this->adminLog->calculateHash($entity);

        $this->assertNotEmpty($hash);
        $this->assertEquals(64, strlen($hash));
        $this->assertEquals($entity->calculateHash(), $hash);
    }

    public function testCalculateHashIsDeterministic(): void
    {
        $entity = new AdminLogEntity();
        $entity->setTime(1700000000);
        $entity->setUserId(42);
        $entity->setIp('10.0.0.1');
        $entity->setText('some action');
        $entity->setPreviousHash('abc123');

        $hash1 = $this->adminLog->calculateHash($entity);
        $hash2 = $this->adminLog->calculateHash($entity);

        $this->assertEquals($hash1, $hash2);
    }

    public function testCalculateHashDiffersWithDifferentData(): void
    {
        $entity1 = new AdminLogEntity();
        $entity1->setTime(1700000000);
        $entity1->setUserId(1);
        $entity1->setIp('127.0.0.1');
        $entity1->setText('action A');

        $entity2 = new AdminLogEntity();
        $entity2->setTime(1700000000);
        $entity2->setUserId(1);
        $entity2->setIp('127.0.0.1');
        $entity2->setText('action B');

        $this->assertNotEquals($this->adminLog->calculateHash($entity1), $this->adminLog->calculateHash($entity2));
    }

    public function testDeleteKeepsRecentEntries(): void
    {
        $_SERVER['REQUEST_TIME'] = $this->now;
        $this->configuration->set('main.enableAdminLog', true);

        // Add a recent entry
        $this->adminLog->log(new User($this->configuration), 'recent entry');
        $this->assertEquals(1, $this->adminLog->getNumberOfEntries());

        // Delete should not remove entries less than 30 days old
        $this->assertTrue($this->adminLog->delete());
        $this->assertEquals(1, $this->adminLog->getNumberOfEntries());
    }

    public function testLogWithEmptyText(): void
    {
        $this->configuration->set('main.enableAdminLog', true);
        $result = $this->adminLog->log(new User($this->configuration), '');
        $this->assertTrue($result);

        $entries = array_values($this->adminLog->getAll());
        $this->assertCount(1, $entries);
        $this->assertEquals('', $entries[0]->getText());
    }

    public function testVerifyChainIntegrityWithSingleEntry(): void
    {
        $_SERVER['REQUEST_TIME'] = $this->now;
        $this->configuration->set('main.enableAdminLog', true);

        $this->adminLog->log(new User($this->configuration), 'only entry');

        $result = $this->adminLog->verifyChainIntegrity();

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
        $this->assertEquals(1, $result['total']);
        $this->assertEquals(1, $result['verified']);
    }
}
