<?php

namespace phpMyFAQ;

use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\SeoEntity;
use phpMyFAQ\Enums\SeoType;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[AllowMockObjectsWithoutExpectations]
class SeoTest extends TestCase
{
    private Seo $seo;
    private Sqlite3 $dbHandle;
    private string $databasePath;
    private ?Configuration $previousConfiguration = null;

    protected function setUp(): void
    {
        parent::setUp();

        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-seo-test-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $this->initializeDatabaseStatics($this->dbHandle);
        $configuration = new Configuration($this->dbHandle);
        $configuration->set('main.currentVersion', System::getVersion());
        $configuration->set('seo.metaTagsHome', 'index,follow');
        $configuration->set('seo.metaTagsFaqs', 'noindex,nofollow');
        $configuration->set('seo.metaTagsCategories', 'noarchive');
        $configuration->set('seo.metaTagsPages', 'none');

        $this->seo = new Seo($configuration);
    }

    protected function tearDown(): void
    {
        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        if (isset($this->dbHandle)) {
            $this->dbHandle->close();
        }

        if (isset($this->databasePath) && is_file($this->databasePath)) {
            unlink($this->databasePath);
        }

        parent::tearDown();
    }

    public function testCreate(): void
    {
        $seo = new SeoEntity();
        $seo
            ->setSeoType(SeoType::FAQ)
            ->setReferenceId(1)
            ->setReferenceLanguage('en')
            ->setTitle('Test Title')
            ->setDescription('Test Description');

        $result = $this->seo->create($seo);

        $this->assertTrue($result);
    }

    public function testGet(): void
    {
        $seo = new SeoEntity();
        $seo
            ->setSeoType(SeoType::FAQ)
            ->setReferenceId(1)
            ->setReferenceLanguage('en')
            ->setTitle('Test Title')
            ->setDescription('Test Description');
        $this->seo->create($seo);

        $result = $this->seo->get($seo);

        $this->assertInstanceOf(SeoEntity::class, $result);
        $this->assertEquals('Test Title', $result->getTitle());
    }

    public function testUpdate(): void
    {
        $seo = new SeoEntity();
        $seo
            ->setSeoType(SeoType::FAQ)
            ->setReferenceId(1)
            ->setReferenceLanguage('en')
            ->setTitle('Test Title')
            ->setDescription('Test Description');
        $this->seo->create($seo);

        $seo
            ->setSeoType(SeoType::FAQ)
            ->setReferenceId(1)
            ->setReferenceLanguage('en')
            ->setTitle('Updated Title')
            ->setDescription('Updated Description');

        $result = $this->seo->update($seo);

        $this->assertTrue($result);
        $this->assertEquals('Updated Title', $seo->getTitle());
    }

    public function testDelete(): void
    {
        $seo = new SeoEntity();
        $seo
            ->setSeoType(SeoType::FAQ)
            ->setReferenceId(1)
            ->setReferenceLanguage('en')
            ->setTitle('Test Title')
            ->setDescription('Test Description');
        $this->seo->create($seo);

        $result = $this->seo->delete($seo);

        $this->assertTrue($result);
    }

    public function testGetMetaRobots(): void
    {
        $this->assertSame('index,follow', $this->seo->getMetaRobots('main'));
        $this->assertSame('noindex,nofollow', $this->seo->getMetaRobots('faq'));
        $this->assertSame('noarchive', $this->seo->getMetaRobots('show'));
        $this->assertSame('none', $this->seo->getMetaRobots('unknown'));
    }

    private function initializeDatabaseStatics(Sqlite3 $dbHandle): void
    {
        $databaseReflection = new ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $dbHandle);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');
        Database::setTablePrefix('');
    }
}
