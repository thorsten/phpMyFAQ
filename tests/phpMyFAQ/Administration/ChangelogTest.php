<?php

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Database\PdoSqlite;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ChangelogTest extends TestCase
{
    protected Configuration $configuration;

    protected Changelog $changelog;
    private DatabaseDriver $dbHandle;
    private string $dbPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure REQUEST_TIME is defined — CLI/PHPUnit runs may not populate $_SERVER.
        if (!isset($_SERVER['REQUEST_TIME'])) {
            $_SERVER['REQUEST_TIME'] = time();
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'pmf-changelog-');
        $this->assertNotFalse($tempFile);
        $this->dbPath = $tempFile;
        $this->assertTrue(copy(PMF_TEST_DIR . '/test.db', $this->dbPath));

        $this->dbHandle = new PdoSqlite();
        $this->dbHandle->connect($this->dbPath, '', '');

        $this->configuration = new Configuration($this->dbHandle);

        $this->changelog = new Changelog($this->configuration);
    }

    protected function tearDown(): void
    {
        $this->dbHandle->close();
        if (isset($this->dbPath) && is_file($this->dbPath)) {
            unlink($this->dbPath);
        }

        parent::tearDown();
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
            'SELECT COUNT(*) AS count FROM %sfaqchanges WHERE beitrag = %d',
            Database::getTablePrefix(),
            $id,
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
                ],
            ],
            $result,
        );
    }

    public function testAddEscapesLanguageAndText(): void
    {
        $id = 987;
        $userId = 654;
        $text = "changed title'; DROP TABLE faqchanges; --";
        $lang = "en' OR 1=1 -- ";

        $result = $this->changelog->add($id, $userId, $text, $lang, 1);

        $this->assertTrue($result);
        $this->assertStringContainsString("en'' OR 1=1 -- ", $this->configuration->getDb()->log());
        $this->assertStringContainsString(
            "changed title''; DROP TABLE faqchanges; --",
            $this->configuration->getDb()->log(),
        );

        $query = sprintf('SELECT lang, what FROM %sfaqchanges WHERE beitrag = %d', Database::getTablePrefix(), $id);
        $dbResult = $this->configuration->getDb()->query($query);
        $row = $this->configuration->getDb()->fetchObject($dbResult);

        $this->assertSame($lang, $row->lang);
        $this->assertSame($text, $row->what);
    }
}
