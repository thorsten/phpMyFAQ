<?php

namespace phpMyFAQ\Configuration;

use PHPUnit\Framework\TestCase;

class DatabaseConfigurationTest extends TestCase
{
    public function testDBConfigProperties(): void
    {
        $config = new DatabaseConfiguration(dirname(__FILE__, 3) . '/content/core/config/database.php');

        $this->assertEquals(dirname(__FILE__, 3) . '/test.db', $config->getServer());
        $this->assertEquals(null, $config->getPort());
        $this->assertEquals('', $config->getUser());
        $this->assertEquals('', $config->getPassword());
        $this->assertEquals('', $config->getDatabase());
        $this->assertEquals('', $config->getPrefix());
        $this->assertEquals('pdo_sqlite', $config->getType());
    }
}
