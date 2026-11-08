<?php

declare(strict_types=1);

namespace phpMyFAQ\Command;

use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\Storage\FilesystemConfigurationCache;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Database\PdoSqlite;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Environment;
use phpMyFAQ\Language;
use phpMyFAQ\Plugin\PluginDiscovery;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

#[CoversClass(SeedTestDataCommand::class)]
#[UsesClass(\phpMyFAQ\Core\Json::class)]
#[UsesClass(\phpMyFAQ\Category::class)]
#[UsesClass(\phpMyFAQ\Category\CategoryCache::class)]
#[UsesClass(\phpMyFAQ\Category\CategoryPermissionContext::class)]
#[UsesClass(\phpMyFAQ\Category\CategoryRepository::class)]
#[UsesClass(\phpMyFAQ\Category\Permission::class)]
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
#[UsesClass(\phpMyFAQ\Faq\FaqRepository::class)]
#[UsesClass(\phpMyFAQ\Faq\Permission::class)]
#[UsesClass(\phpMyFAQ\Faq\ReadScope::class)]
#[UsesClass(\phpMyFAQ\Faq\StatusScope::class)]
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
#[UsesClass(FilesystemConfigurationCache::class)]
#[UsesClass(PdoSqlite::class)]
#[UsesClass(PluginDiscovery::class)]
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

    public function testExecuteReportsSeedingFailuresWithTraceWhenVerbose(): void
    {
        // A database that fails on the first purge query makes the seeding run throw.
        $database = $this->createStub(DatabaseDriver::class);
        $database->method('escape')->willReturnCallback(static fn(string $value): string => $value);
        $database->method('query')->willThrowException(new \RuntimeException('database is locked'));

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($database);

        $reflection = new ReflectionClass(Configuration::class);
        $reflection->getProperty('configuration')->setValue(null, $configuration);

        $exitCode = $this->commandTester->execute(['--fresh' => true], [
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
        ]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Seeding failed: database is locked', $output);
        $this->assertStringContainsString('SeedTestDataCommand', $output, 'The verbose output carries the trace');
    }

    public function testLoadFixtureRejectsAMissingFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Fixture file not found: ');

        $this->invokePrivate('loadFixture', 'does-not-exist.json');
    }

    public function testLoadFixtureRejectsAFileThatCannotBeRead(): void
    {
        $fixture = $this->createTemporaryFixture('[]');
        chmod($fixture, 0o000);

        // PHP warns about the refused read; the warning is not the behaviour under test.
        set_error_handler(static fn(): bool => true);
        try {
            $this->invokePrivate('loadFixture', $this->relativeToFixtureDirectory($fixture));
            $this->fail('An unreadable fixture must not be seeded.');
        } catch (\RuntimeException $runtimeException) {
            $this->assertStringStartsWith('Unable to read fixture file: ', $runtimeException->getMessage());
        } finally {
            restore_error_handler();
            chmod($fixture, 0o644);
            unlink($fixture);
        }
    }

    public function testLoadFixtureRejectsAFileWithoutAJsonArray(): void
    {
        $fixture = $this->createTemporaryFixture('"not a list of entries"');

        try {
            $this->invokePrivate('loadFixture', $this->relativeToFixtureDirectory($fixture));
            $this->fail('A fixture without a JSON array must not be seeded.');
        } catch (\RuntimeException $runtimeException) {
            $this->assertStringEndsWith('does not contain a JSON array.', $runtimeException->getMessage());
        } finally {
            unlink($fixture);
        }
    }

    public function testSeedCategoriesSkipsTranslationsThatAreNotObjects(): void
    {
        $this->useConfiguration($this->configuration);

        $categoryIdMap = $this->invokePrivate(
            'seedCategories',
            [
                ['slug' => 'broken', 'translations' => ['en' => 'Broken', 'de' => null]],
            ],
            ['de', 'en'],
        );

        $this->assertSame([], $categoryIdMap);
        $this->assertSame(
            0,
            $this->countScalar(sprintf(
                "SELECT COUNT(*) AS c FROM %sfaqcategories WHERE name = 'Broken'",
                Database::getTablePrefix(),
            )),
        );
    }

    public function testSeedFaqsSkipsUnknownCategoriesAndTranslationsThatAreNotObjects(): void
    {
        $this->useConfiguration($this->configuration);
        $translation = ['question' => 'Q?', 'answer' => 'A.'];

        $inserted = $this->invokePrivate(
            'seedFaqs',
            [
                ['category' => 'unknown', 'translations' => ['en' => $translation]],
                ['category' => 'known', 'translations' => ['en' => 'Q?', 'de' => null]],
            ],
            ['known' => 1],
            ['de', 'en'],
        );

        $this->assertSame(0, $inserted);
        $this->assertSame(0, $this->countSeededFaqs());
    }

    public function testSeedFaqsSkipsRecordsTheDatabaseDidNotNumber(): void
    {
        // A driver that hands out no id leaves the FAQ without a usable record id.
        $database = $this->createStub(DatabaseDriver::class);
        $database->method('escape')->willReturnCallback(static fn(string $value): string => $value);
        $database->method('nextId')->willReturn(0);
        $database->method('fetchArray')->willReturn(['max_solution_id' => 1000, 'c' => 0]);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($database);
        $configuration->method('get')->willReturn('basic');
        $this->useConfiguration($configuration);

        $inserted = $this->invokePrivate(
            'seedFaqs',
            [
                [
                    'category' => 'known',
                    'tags' => ['phpmyfaq'],
                    'translations' => ['en' => ['question' => 'Q?', 'answer' => 'A.']],
                ],
            ],
            ['known' => 1],
            ['en'],
        );

        $this->assertSame(0, $inserted);
    }

    public function testSeedGlossaryAndNewsSkipTranslationsThatAreNotObjects(): void
    {
        $this->useConfiguration($this->configuration);
        $entries = [['translations' => ['en' => 'flat', 'de' => null]]];

        $this->assertSame(0, $this->invokePrivate('seedGlossary', $entries, ['de', 'en']));
        $this->assertSame(0, $this->invokePrivate('seedNews', $entries, ['de', 'en']));
        $this->assertSame(0, $this->countSeededNews());
    }

    public function testPurgeSkipsRowsWithoutIdentifiers(): void
    {
        $queries = [];
        $database = $this->createStub(DatabaseDriver::class);
        $database->method('escape')->willReturnCallback(static fn(string $value): string => $value);
        $database
            ->method('query')
            ->willReturnCallback(static function (string $query) use (&$queries): bool {
                $queries[] = $query;
                return true;
            });
        // Rows without an id or language cannot be cleaned up and are skipped.
        $database
            ->method('fetchAll')
            ->willReturn([
                (object) ['id' => null, 'lang' => 'en'],
                (object) ['id' => null, 'lang' => null],
                (object) ['lang' => 'en'],
            ]);
        $database->method('affectedRows')->willReturn(0);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($database);
        $this->useConfiguration($configuration);

        $this->assertSame(0, $this->invokePrivate('purge'));

        $relationDeletes = array_filter(
            $queries,
            static fn(string $query): bool => (
                str_contains($query, 'DELETE FROM')
                && (str_contains($query, 'faqcategoryrelations') || str_contains($query, 'faqcategory_user'))
            ),
        );
        $this->assertSame([], $relationDeletes, 'Rows without identifiers trigger no related deletes');
    }

    private function invokePrivate(string $method, mixed ...$arguments): mixed
    {
        return new \ReflectionMethod(SeedTestDataCommand::class, $method)->invoke($this->command, ...$arguments);
    }

    private function useConfiguration(Configuration $configuration): void
    {
        new \ReflectionProperty(SeedTestDataCommand::class, 'configuration')->setValue($this->command, $configuration);
    }

    private function createTemporaryFixture(string $json): string
    {
        $fixture = sys_get_temp_dir() . '/pmf-seed-fixture-' . bin2hex(random_bytes(6)) . '.json';
        file_put_contents($fixture, $json);

        return $fixture;
    }

    /**
     * The command resolves fixture names below its own fixture directory, so a file elsewhere
     * is addressed by climbing out of that directory first.
     */
    private function relativeToFixtureDirectory(string $path): string
    {
        $fixtureDirectory =
            dirname((string) new ReflectionClass(SeedTestDataCommand::class)->getFileName()) . '/Fixtures/testdata';
        $depth = count(array_filter(explode('/', $fixtureDirectory)));

        return str_repeat('../', $depth) . ltrim($path, '/');
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
