<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;

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
        $_SERVER['REQUEST_TIME'] = $this->now - 31 * 86400;
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

    public function testGetAll(): void
    {
        $_SERVER['REQUEST_TIME'] = $this->now;
        $this->adminLog->log(new User($this->configuration), 'foo');
        $this->adminLog->log(new User($this->configuration), 'bar');

        $result = $this->adminLog->getAll();
        $this->assertEquals(
            [
                2 => [
                    'time' => $this->now,
                    'usr' => -1,
                    'text' => 'bar',
                    'ip' => '127.0.0.1'
                ],
                1 => [
                    'time' => $this->now,
                    'usr' => -1,
                    'text' => 'foo',
                    'ip' => '127.0.0.1'
                ]
            ],
            $result
        );
    }
}
