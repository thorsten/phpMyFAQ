<?php

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Mysqli;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{

    /**
     * @throws Exception
     */
    public function testFactoryReturnsInstanceOfDatabaseDriver(): void
    {
        $type = 'mysqli';
        $driver = Database::factory($type);
        $this->assertInstanceOf(Mysqli::class, $driver);
    }
}
