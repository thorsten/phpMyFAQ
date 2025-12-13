<?php

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\System;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class ChangelogTest extends TestCase
{
    protected Configuration $configuration;

    protected Changelog $changelog;

    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');

        $this->configuration = new Configuration($dbHandle);
        $this->configuration->set('main.currentVersion', System::getVersion());

        $this->changelog = new Changelog($this->configuration);
    }

    public function testAdd(): void
    {
        $id = 123;
        $userId = 456;
        $text = 'test text';
        $lang = 'en';
        $revisionId = 789;

        $result = $this->changelog->add($id, $userId, $text, $lang, $revisionId);

        $this->assertTrue($result);

        $query = sprintf(
            "SELECT COUNT(*) AS count FROM %sfaqchanges WHERE beitrag = %d",
            Database::getTablePrefix(),
            $id
        );

        $result = $this->configuration->getDb()->query($query);
        $row = $this->configuration->getDb()->fetchObject($result);

        $this->assertEquals(1, $row->count);
    }

    public function testGetByFaqId(): void
    {
        $id = 456;
        $userId = 789;
        $text = 'test text';
        $lang = 'en';
        $revisionId = 123;

        $this->changelog->add($id, $userId, $text, $lang, $revisionId);

        $result = $this->changelog->getByFaqId($id);

        $this->assertEquals(
            [
                0 => [
                    'revision_id' => $revisionId,
                    'user' => $userId,
                    'date' => $_SERVER['REQUEST_TIME'],
                    'changelog' => $text,
                ]
            ],
            $result
        );
    }
}
