<?php

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

class VisitsTest extends TestCase
{
    /** @var Configuration */
    private Configuration $configuration;

    private Visits $visits;

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
        $this->configuration->set('main.currentVersion', System::getVersion());

        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
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

        $query = sprintf('SELECT COUNT(*) AS count FROM %sfaqvisits WHERE id = %d', Database::getTablePrefix(), $id);

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

        // Assert the basic structure and key values
        $this->assertCount(1, $result);
        $this->assertEquals($id, $result[0]['id']);
        $this->assertEquals('en', $result[0]['lang']);
        $this->assertEquals(2, $result[0]['visits']);

        // Assert last_visit is either 0 (local) or a valid timestamp (CI)
        $this->assertIsInt($result[0]['last_visit']);
        $this->assertGreaterThanOrEqual(0, $result[0]['last_visit']);
    }

    public function testResetAll()
    {
        $id = 1;
        $this->visits->logViews($id);
        $this->visits->logViews($id);

        $result = $this->visits->resetAll();

        $this->assertTrue($result);

        $result = $this->visits->getAllData();

        // Assert the basic structure and key values after reset
        $this->assertCount(1, $result);
        $this->assertEquals($id, $result[0]['id']);
        $this->assertEquals('en', $result[0]['lang']);
        $this->assertEquals(1, $result[0]['visits']);

        // Assert last_visit is either 0 (local) or a valid timestamp (CI)
        $this->assertIsInt($result[0]['last_visit']);
        $this->assertGreaterThanOrEqual(0, $result[0]['last_visit']);
    }
}
