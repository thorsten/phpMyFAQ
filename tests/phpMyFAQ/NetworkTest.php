<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;

class NetworkTest extends TestCase
{
    private Network $network;

    private Configuration $configuration;
    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
        $this->network = new Network($this->configuration);
    }
    public function testIsBannedWithEmptyList(): void
    {
        $this->assertFalse($this->network->isBanned('127.0.0.1'));
    }

    public function testIsBannedWithFilledList(): void
    {
        $this->configuration->set('security.bannedIPs', '127.0.0.2');
        $this->assertFalse($this->network->isBanned('127.0.0.1'));
        $this->assertTrue($this->network->isBanned('127.0.0.2'));
    }
}
