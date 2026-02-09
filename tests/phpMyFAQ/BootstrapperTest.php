<?php

namespace phpMyFAQ;

use phpMyFAQ\Configuration\DatabaseConfiguration;
use PHPUnit\Framework\TestCase;

class BootstrapperTest extends TestCase
{
    public function testGettersReturnNullBeforeRun(): void
    {
        $bootstrapper = new Bootstrapper();

        $this->assertNull($bootstrapper->getFaqConfig());
        $this->assertNull($bootstrapper->getDb());
        $this->assertNull($bootstrapper->getRequest());
    }

    public function testDatabaseConfigurationIncludesSchemaField(): void
    {
        $config = new DatabaseConfiguration(dirname(__FILE__, 2) . '/content/core/config/database.php');

        $this->assertNull($config->getSchema());
    }
}
