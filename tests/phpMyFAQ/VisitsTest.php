<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;

class VisitsTest extends TestCase
{
    /** @var Configuration */
    private Configuration $configuration;

    private Visits $visits;

    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setLanguagesDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
        $this->configuration->set('main.currentVersion', System::getVersion());

        $language = new Language($this->configuration);
        $language->setLanguage(false, 'en');
        $this->configuration->setLanguage($language);

        $this->visits = new Visits($this->configuration);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $query = sprintf('DELETE FROM %sfaqvisits', Database::getTablePrefix());
        $this->configuration->getDb()->query($query);
    }

    public function testAdd()
    {
        $this->assertTrue($this->visits->add(1));
    }

    public function testLogViews()
    {
        $id = 1;
        $this->visits->logViews($id);

        $query = sprintf(
            "SELECT COUNT(*) AS count FROM %sfaqvisits WHERE id = %d",
            Database::getTablePrefix(),
            $id
        );

        $result = $this->configuration->getDb()->query($query);
        $row = $this->configuration->getDb()->fetchObject($result);

        $this->assertEquals($id, $row->count);
    }

    public function testGetAllData()
    {
        $id = 1;
        $this->visits->logViews($id);
        $this->visits->logViews($id);

        $result = $this->visits->getAllData();

        $this->assertEquals(
            [
                [
                    'id' => $id,
                    'lang' => 'en',
                    'visits' => 2,
                    'last_visit' => $_SERVER['REQUEST_TIME'],
                ]
            ],
            $result
        );
    }

    public function testResetAll()
    {
        $id = 1;
        $this->visits->logViews($id);
        $this->visits->logViews($id);

        $result = $this->visits->resetAll();

        $this->assertTrue($result);

        $result = $this->visits->getAllData();

        $this->assertEquals(
            [
                [
                    'id' => $id,
                    'lang' => 'en',
                    'visits' => 1,
                    'last_visit' => $_SERVER['REQUEST_TIME'],
                ]
            ],
            $result
        );
    }
}
