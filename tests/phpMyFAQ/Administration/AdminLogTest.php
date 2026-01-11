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

    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
        $this->configuration->set('main.currentVersion', System::getVersion());

        $this->adminLog = new AdminLog($this->configuration);

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->now = time();
    }

    protected function tearDown(): void
    {
        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $dbHandle->query('DELETE FROM faqadminlog');
        parent::tearDown();
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
}
