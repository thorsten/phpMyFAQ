<?php

declare(strict_types=1);

namespace phpMyFAQ\Category\Language;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language as PmfLanguage;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class CategoryLanguageServiceTest extends TestCase
{
    /** @var Configuration&MockObject */
    private Configuration $configuration;
    /** @var PmfLanguage&MockObject */
    private PmfLanguage $language;

    private Sqlite3 $dbHandle;
    private string $databasePath;
    private ?Configuration $previousConfiguration = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configuration = $this->createStub(Configuration::class);
        $this->language = $this
            ->getMockBuilder(PmfLanguage::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLanguageAvailable'])
            ->getMock();
        $this->configuration->method('getLanguage')->willReturn($this->language);
    }

    private function setUpRealDb(): void
    {
        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();
        $configurationProperty->setValue(null, null);

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-cat-lang-svc-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');

        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $this->dbHandle);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');
        Database::setTablePrefix('');
    }

    private function tearDownRealDb(): void
    {
        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        $this->dbHandle->close();

        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, null);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, '');

        @unlink($this->databasePath);
    }

    public function testGetLanguagesInUseReturnsCodes(): void
    {
        $this->language
            ->expects($this->once())
            ->method('isLanguageAvailable')
            ->with(0, 'faqcategories')
            ->willReturn(['en', 'de']);

        $service = new CategoryLanguageService();
        $result = $service->getLanguagesInUse($this->configuration);

        $this->assertArrayHasKey('en', $result);
        $this->assertArrayHasKey('de', $result);
    }

    public function testGetExistingTranslationsKeysMatchExisting(): void
    {
        $this->language
            ->expects($this->once())
            ->method('isLanguageAvailable')
            ->with(123, 'faqcategories')
            ->willReturn(['en', 'de']);

        $service = new CategoryLanguageService();
        $result = $service->getExistingTranslations($this->configuration, 123);

        $this->assertArrayHasKey('en', $result);
        $this->assertArrayHasKey('de', $result);
    }

    public function testGetLanguagesToTranslateExcludesExisting(): void
    {
        $this->language
            ->expects($this->once())
            ->method('isLanguageAvailable')
            ->with(456, 'faqcategories')
            ->willReturn(['en']);

        $service = new CategoryLanguageService();
        $result = $service->getLanguagesToTranslate($this->configuration, 456);

        $this->assertArrayNotHasKey('en', $result);
        $this->assertIsArray($result);
    }

    public function testGetAllExistingTranslationsGroupsByCategory(): void
    {
        $this->setUpRealDb();
        try {
            $configuration = new Configuration($this->dbHandle);

            // Insert two translations for category 10, one for category 20
            $this->dbHandle->query(
                "INSERT INTO faqcategories (id, lang, parent_id, name, description, user_id, group_id, active, show_home, image)
                 VALUES (10, 'en', 0, 'Cat A EN', '', 1, -1, 1, 0, '')"
            );
            $this->dbHandle->query(
                "INSERT INTO faqcategories (id, lang, parent_id, name, description, user_id, group_id, active, show_home, image)
                 VALUES (10, 'de', 0, 'Cat A DE', '', 1, -1, 1, 0, '')"
            );
            $this->dbHandle->query(
                "INSERT INTO faqcategories (id, lang, parent_id, name, description, user_id, group_id, active, show_home, image)
                 VALUES (20, 'fr', 0, 'Cat B FR', '', 1, -1, 1, 0, '')"
            );

            $service = new CategoryLanguageService();
            $result = $service->getAllExistingTranslations($configuration);

            $this->assertArrayHasKey(10, $result);
            $this->assertContains('en', $result[10]);
            $this->assertContains('de', $result[10]);
            $this->assertArrayHasKey(20, $result);
            $this->assertContains('fr', $result[20]);
            $this->assertArrayNotHasKey(10, array_filter($result, static fn($codes) => in_array('fr', $codes)));
        } finally {
            $this->tearDownRealDb();
        }
    }
}
