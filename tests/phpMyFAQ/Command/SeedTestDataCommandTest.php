<?php

declare(strict_types=1);

namespace phpMyFAQ\Command;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Environment;
use phpMyFAQ\Language;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

#[CoversClass(SeedTestDataCommand::class)]
#[UsesClass(\phpMyFAQ\Category::class)]
#[UsesClass(\phpMyFAQ\Category\CategoryCache::class)]
#[UsesClass(\phpMyFAQ\Category\CategoryPermissionContext::class)]
#[UsesClass(\phpMyFAQ\Category\CategoryRepository::class)]
#[UsesClass(\phpMyFAQ\Category\Permission\CategoryPermissionService::class)]
#[UsesClass(\phpMyFAQ\Configuration::class)]
#[UsesClass(\phpMyFAQ\Configuration\ConfigurationRepository::class)]
#[UsesClass(\phpMyFAQ\Configuration\LayoutSettings::class)]
#[UsesClass(\phpMyFAQ\Configuration\LdapSettings::class)]
#[UsesClass(\phpMyFAQ\Configuration\MailSettings::class)]
#[UsesClass(\phpMyFAQ\Configuration\SearchSettings::class)]
#[UsesClass(\phpMyFAQ\Configuration\SecuritySettings::class)]
#[UsesClass(\phpMyFAQ\Configuration\Storage\ConfigurationStorageSettings::class)]
#[UsesClass(\phpMyFAQ\Configuration\Storage\ConfigurationStorageSettingsResolver::class)]
#[UsesClass(\phpMyFAQ\Configuration\Storage\DatabaseConfigurationStore::class)]
#[UsesClass(\phpMyFAQ\Configuration\Storage\HybridConfigurationStore::class)]
#[UsesClass(\phpMyFAQ\Configuration\UrlSettings::class)]
#[UsesClass(\phpMyFAQ\Database::class)]
#[UsesClass(\phpMyFAQ\Database\Sqlite3::class)]
#[UsesClass(\phpMyFAQ\Entity\CategoryEntity::class)]
#[UsesClass(\phpMyFAQ\Entity\FaqEntity::class)]
#[UsesClass(\phpMyFAQ\Entity\NewsMessage::class)]
#[UsesClass(\phpMyFAQ\Environment::class)]
#[UsesClass(\phpMyFAQ\Faq::class)]
#[UsesClass(\phpMyFAQ\Filter::class)]
#[UsesClass(\phpMyFAQ\Glossary::class)]
#[UsesClass(\phpMyFAQ\Glossary\GlossaryRepository::class)]
#[UsesClass(\phpMyFAQ\Language::class)]
#[UsesClass(\phpMyFAQ\Language\LanguageCodes::class)]
#[UsesClass(\phpMyFAQ\Language\LanguageDetector::class)]
#[UsesClass(\phpMyFAQ\Language\Plurals::class)]
#[UsesClass(\phpMyFAQ\News::class)]
#[UsesClass(\phpMyFAQ\News\NewsRepository::class)]
#[UsesClass(\phpMyFAQ\Plugin\PluginManager::class)]
#[UsesClass(\phpMyFAQ\Strings::class)]
#[UsesClass(\phpMyFAQ\Strings\Mbstring::class)]
#[UsesClass(\phpMyFAQ\System::class)]
#[UsesClass(\phpMyFAQ\Tags::class)]
#[UsesClass(\phpMyFAQ\Tenant\TenantContext::class)]
#[UsesClass(\phpMyFAQ\Tenant\TenantContextResolver::class)]
#[UsesClass(\phpMyFAQ\Tenant\TenantQuotaEnforcer::class)]
#[UsesClass(\phpMyFAQ\Tenant\TenantQuotas::class)]
#[UsesClass(\phpMyFAQ\Translation::class)]
#[AllowMockObjectsWithoutExpectations]
class SeedTestDataCommandTest extends TestCase
{
    private SeedTestDataCommand $command;

    private CommandTester $commandTester;

    private Configuration $configuration;

    private string $databasePath;

    private string $previousEnvironment;

    protected function setUp(): void
    {
        parent::setUp();

        // The command refuses to run outside APP_ENV=demo. Force demo for the
        // duration of each test and restore the previous value in tearDown.
        $environmentReflection = new ReflectionClass(Environment::class);
        $environmentProperty = $environmentReflection->getProperty('environment');
        $this->previousEnvironment = (string) $environmentProperty->getValue();
        $environmentProperty->setValue(null, 'demo');

        // Work on a per-test copy of the shared SQLite fixture so that the
        // seed/purge cycles in these tests cannot pollute the database used
        // by other test classes.
        $this->databasePath = tempnam(sys_get_temp_dir(), 'pmf_seed_test_');
        copy(PMF_TEST_DIR . '/test.db', $this->databasePath);

        // Reset the Configuration singleton so our fresh instance (and DB handle)
        // becomes the one SeedTestDataCommand uses via getConfigurationInstance().
        $reflection = new ReflectionClass(Configuration::class);
        $property = $reflection->getProperty('configuration');
        $property->setValue(null, null);

        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databasePath, '', '');
        $this->configuration = new Configuration($dbHandle);

        $language = new Language($this->configuration, $this->createStub(SessionInterface::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);

        $this->command = new SeedTestDataCommand();
        $this->commandTester = new CommandTester($this->command);
    }

    protected function tearDown(): void
    {
        // Drop the isolated per-test database.
        if (is_file($this->databasePath)) {
            @unlink($this->databasePath);
        }

        // Clear the Configuration singleton to avoid leaking our (now invalid)
        // DB handle into subsequent tests.
        $reflection = new ReflectionClass(Configuration::class);
        $property = $reflection->getProperty('configuration');
        $property->setValue(null, null);

        // Restore the previous APP_ENV value.
        $environmentReflection = new ReflectionClass(Environment::class);
        $environmentProperty = $environmentReflection->getProperty('environment');
        $environmentProperty->setValue(null, $this->previousEnvironment);

        parent::tearDown();
    }

    public function testCommandConfiguration(): void
    {
        $this->assertSame('phpmyfaq:seed-testdata', $this->command->getName());
        $this->assertStringContainsString('bilingual', (string) $this->command->getDescription());

        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('locale'));
        $this->assertTrue($definition->hasOption('fresh'));
        $this->assertSame('de,en', $definition->getOption('locale')->getDefault());
    }

    public function testExecuteSeedsBothLocales(): void
    {
        $exitCode = $this->commandTester->execute(['--fresh' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode, $this->commandTester->getDisplay());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Test data seeded successfully', $output);

        // Categories: 5 per locale x 2 locales = 10 rows.
        $this->assertSame(10, $this->countSeededCategories());

        // FAQs: 15 per locale x 2 locales = 30 rows (marker-based).
        $this->assertSame(30, $this->countSeededFaqs());

        // Every seeded FAQ must have exactly one category relation row.
        $this->assertSame(30, $this->countSeededFaqCategoryRelations());

        // Glossary: 6 per locale x 2 = 12 rows.
        $this->assertSame(12, $this->countSeededGlossary());

        // News: 3 per locale x 2 = 6 rows.
        $this->assertSame(6, $this->countSeededNews());

        // Tags must be attached: at least one row in faqdata_tags per seeded FAQ.
        $this->assertGreaterThan(0, $this->countSeededFaqTagRelations());
    }

    public function testExecuteSeedsSingleLocale(): void
    {
        $exitCode = $this->commandTester->execute(['--fresh' => true, '--locale' => 'en']);

        $this->assertSame(Command::SUCCESS, $exitCode);

        $this->assertSame(5, $this->countSeededCategories());
        $this->assertSame(15, $this->countSeededFaqs());
        $this->assertSame(6, $this->countSeededGlossary());
        $this->assertSame(3, $this->countSeededNews());
    }

    public function testExecuteRejectsUnsupportedLocale(): void
    {
        $exitCode = $this->commandTester->execute(['--locale' => 'fr']);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('not supported', $this->commandTester->getDisplay());
    }

    public function testFreshOptionPurgesPreviousRun(): void
    {
        $this->commandTester->execute([]);
        $this->assertGreaterThan(0, $this->countSeededFaqs());

        $freshCommand = new SeedTestDataCommand();
        $freshTester = new CommandTester($freshCommand);
        $freshTester->execute(['--fresh' => true, '--locale' => 'en']);

        // After a fresh run with English only, the German rows must be gone.
        $this->assertSame(15, $this->countSeededFaqs());
        $this->assertSame(5, $this->countSeededCategories());
    }

    public function testExecuteIsIdempotentOnFresh(): void
    {
        $this->commandTester->execute(['--fresh' => true]);
        $firstCount = $this->countSeededFaqs();

        $secondCommand = new SeedTestDataCommand();
        $secondTester = new CommandTester($secondCommand);
        $secondTester->execute(['--fresh' => true]);

        $this->assertSame($firstCount, $this->countSeededFaqs());
    }

    public function testExecuteRejectsNonDemoEnvironment(): void
    {
        $environmentReflection = new ReflectionClass(Environment::class);
        $environmentProperty = $environmentReflection->getProperty('environment');
        $environmentProperty->setValue(null, 'production');

        $exitCode = $this->commandTester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('APP_ENV=demo', $this->commandTester->getDisplay());
    }

    public function testExecuteRejectsEmptyLocaleList(): void
    {
        $exitCode = $this->commandTester->execute(['--locale' => '   ']);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('No valid locales', $this->commandTester->getDisplay());
    }

    private function countSeededFaqs(): int
    {
        $prefix = Database::getTablePrefix();
        $author = $this->configuration->getDb()->escape(SeedTestDataCommand::AUTHOR);
        $email = $this->configuration->getDb()->escape(SeedTestDataCommand::EMAIL);

        return $this->countScalar(sprintf(
            "SELECT COUNT(*) AS c FROM %sfaqdata WHERE author = '%s' AND email = '%s'",
            $prefix,
            $author,
            $email,
        ));
    }

    private function countSeededFaqCategoryRelations(): int
    {
        $prefix = Database::getTablePrefix();
        $author = $this->configuration->getDb()->escape(SeedTestDataCommand::AUTHOR);
        $email = $this->configuration->getDb()->escape(SeedTestDataCommand::EMAIL);

        return $this->countScalar(sprintf(
            'SELECT COUNT(*) AS c FROM %sfaqcategoryrelations fcr '
            . 'INNER JOIN %sfaqdata fd ON fd.id = fcr.record_id AND fd.lang = fcr.record_lang '
            . "WHERE fd.author = '%s' AND fd.email = '%s'",
            $prefix,
            $prefix,
            $author,
            $email,
        ));
    }

    private function countSeededFaqTagRelations(): int
    {
        $prefix = Database::getTablePrefix();
        $author = $this->configuration->getDb()->escape(SeedTestDataCommand::AUTHOR);
        $email = $this->configuration->getDb()->escape(SeedTestDataCommand::EMAIL);

        return $this->countScalar(sprintf(
            'SELECT COUNT(*) AS c FROM %sfaqdata_tags fdt '
            . 'INNER JOIN %sfaqdata fd ON fd.id = fdt.record_id '
            . "WHERE fd.author = '%s' AND fd.email = '%s'",
            $prefix,
            $prefix,
            $author,
            $email,
        ));
    }

    private function countSeededCategories(): int
    {
        return $this->countByFixtureField('categories.json', 'faqcategories', 'name');
    }

    private function countSeededGlossary(): int
    {
        return $this->countByFixtureField('glossary.json', 'faqglossary', 'item');
    }

    private function countSeededNews(): int
    {
        $prefix = Database::getTablePrefix();
        $author = $this->configuration->getDb()->escape(SeedTestDataCommand::AUTHOR);
        $email = $this->configuration->getDb()->escape(SeedTestDataCommand::EMAIL);

        return $this->countScalar(sprintf(
            "SELECT COUNT(*) AS c FROM %sfaqnews WHERE author_name = '%s' AND author_email = '%s'",
            $prefix,
            $author,
            $email,
        ));
    }

    /**
     * Counts rows in the given table whose value in the given column (and matching lang column)
     * exactly matches one of the values declared in the fixture file.
     */
    private function countByFixtureField(string $fixtureFile, string $table, string $column): int
    {
        $fixturePath = __DIR__ . '/../../../phpmyfaq/src/phpMyFAQ/Command/Fixtures/testdata/' . $fixtureFile;
        $this->assertFileExists($fixturePath);

        /** @var array<int, array<string, mixed>> $entries */
        $entries = json_decode(
            (string) file_get_contents($fixturePath),
            associative: true,
            depth: 16,
            flags: JSON_THROW_ON_ERROR,
        );

        $prefix = Database::getTablePrefix();
        $total = 0;

        foreach ($entries as $entry) {
            $translations = $entry['translations'] ?? [];
            if (!is_array($translations)) {
                continue;
            }
            foreach ($translations as $lang => $translation) {
                $key = $fixtureFile === 'categories.json' ? 'name' : 'item';
                $value = $this->configuration->getDb()->escape((string) $translation[$key]);
                $langEscaped = $this->configuration->getDb()->escape((string) $lang);
                $total += $this->countScalar(sprintf(
                    "SELECT COUNT(*) AS c FROM %s%s WHERE %s = '%s' AND lang = '%s'",
                    $prefix,
                    $table,
                    $column,
                    $value,
                    $langEscaped,
                ));
            }
        }

        return $total;
    }

    private function countScalar(string $query): int
    {
        $result = $this->configuration->getDb()->query($query);
        $row = $this->configuration->getDb()->fetchArray($result);

        return is_array($row) ? (int) $row['c'] : 0;
    }
}
