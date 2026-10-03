<?php

declare(strict_types=1);

namespace phpMyFAQ\Setup;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\System;
use phpMyFAQ\TestDatabaseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

#[CoversClass(UpdateRunner::class)]
#[UsesNamespace('phpMyFAQ')]
final class UpdateRunnerDryRunTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;

    private BufferedOutput $output;

    private mixed $previousDatabaseDriver = null;

    private mixed $previousDatabaseType = null;

    protected function setUp(): void
    {
        $this->configuration = $this->createTestConfiguration();
        $this->configuration->set('main.currentVersion', '4.0.0');

        // The migrations resolve the dialect through the Database statics.
        $this->previousDatabaseDriver = new ReflectionProperty(Database::class, 'databaseDriver')->getValue();
        $this->previousDatabaseType = new ReflectionProperty(Database::class, 'dbType')->getValue();
        new ReflectionProperty(Database::class, 'databaseDriver')->setValue(null, $this->configuration->getDb());
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, 'sqlite3');

        $this->output = new BufferedOutput();
    }

    protected function tearDown(): void
    {
        new ReflectionProperty(Database::class, 'databaseDriver')->setValue(null, $this->previousDatabaseDriver);
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, $this->previousDatabaseType);
    }

    private function style(): SymfonyStyle
    {
        return new SymfonyStyle(new ArrayInput([]), $this->output);
    }

    public function testRunDryRunFromAnOldVersionRendersThePendingMigrations(): void
    {
        $runner = new UpdateRunner($this->configuration, new System());

        $this->assertSame(Command::SUCCESS, $runner->runDryRun($this->style(), '4.0.0'));

        $report = $this->output->fetch();
        $this->assertStringContainsString('phpMyFAQ Migration Dry-Run Report', $report);
        $this->assertStringContainsString('Current version: 4.0.0', $report);
        $this->assertStringContainsString('Target version: ' . System::getVersion(), $report);
        $this->assertStringContainsString('Migration: ', $report);
        $this->assertStringContainsString('Total Migrations:', $report);
        $this->assertStringContainsString('Operations by Type:', $report);
        $this->assertStringContainsString('This was a dry-run. No changes were made to the database.', $report);
        $this->assertStringNotContainsString('No migrations to apply', $report);
    }

    public function testRunDryRunDoesNotTouchTheStoredVersion(): void
    {
        new UpdateRunner($this->configuration, new System())->runDryRun($this->style(), '4.0.0');

        $reloaded = new Configuration($this->configuration->getDb());
        $this->assertSame('4.0.0', $reloaded->get('main.currentVersion'));
    }

    public function testRunDryRunFromTheCurrentVersionHasNothingToApply(): void
    {
        $runner = new UpdateRunner($this->configuration, new System());

        $this->assertSame(Command::SUCCESS, $runner->runDryRun($this->style(), System::getVersion()));

        $report = $this->output->fetch();
        $this->assertStringContainsString('No migrations to apply. Database is up to date.', $report);
        $this->assertStringNotContainsString('Migration: ', $report);
    }

    public function testDisplayDryRunReportRendersEveryOperationType(): void
    {
        $runner = new UpdateRunner($this->configuration, new System());
        $longQuery = 'UPDATE faqconfig SET config_value = ' . str_repeat('x', 120) . " WHERE config_name = 'a'";

        $report = [
            'migrations' => [
                '4.2.0-test' => [
                    'description' => 'Synthetic migration',
                    'operations' => [
                        ['type' => 'sql', 'description' => 'Rewrite config', 'query' => $longQuery],
                        ['type' => 'config_add', 'key' => 'new.flag', 'value' => true],
                        ['type' => 'config_delete', 'key' => 'old.flag'],
                        ['type' => 'config_rename', 'oldKey' => 'a.b', 'newKey' => 'a.c'],
                        ['type' => 'config_update', 'key' => 'main.title', 'value' => str_repeat('t', 60)],
                        ['type' => 'file_copy', 'source' => PMF_ROOT_DIR . '/a.txt', 'destination' => PMF_ROOT_DIR . '/b.txt'],
                        ['type' => 'directory_copy', 'source' => '/very/' . str_repeat('long/', 20) . 'dir', 'destination' => '/dst'],
                        ['type' => 'permission_grant', 'permissionName' => 'view_faqs', 'permissionDescription' => 'View FAQs'],
                        'not an operation',
                    ],
                ],
                'ignored' => 'not a migration',
            ],
            'summary' => [
                'migrationCount' => 1,
                'totalOperations' => 8,
                'operationsByType' => ['sql' => 1, 'config_add' => 1],
            ],
        ];

        new ReflectionMethod(UpdateRunner::class, 'displayDryRunReport')->invoke($runner, $this->style(), $report);

        $rendered = $this->output->fetch();
        $this->assertStringContainsString('Migration: 4.2.0-test', $rendered);
        $this->assertStringContainsString('Synthetic migration', $rendered);
        $this->assertStringContainsString('SQL Operations (1):', $rendered);
        $this->assertStringContainsString('UPDATE faqconfig SET config_value = xxx', $rendered);
        $this->assertStringContainsString('...', $rendered);
        $this->assertStringNotContainsString(str_repeat('x', 120), $rendered);
        $this->assertStringContainsString('Configuration Additions (1):', $rendered);
        $this->assertStringContainsString('new.flag', $rendered);
        $this->assertStringContainsString('true', $rendered);
        $this->assertStringContainsString('Configuration Deletions (1):', $rendered);
        $this->assertStringContainsString('Configuration Renames (1):', $rendered);
        $this->assertStringContainsString('a.c', $rendered);
        $this->assertStringContainsString('Configuration Updates (1):', $rendered);
        $this->assertStringContainsString("'" . str_repeat('t', 37) . "...'", $rendered);
        $this->assertStringContainsString('File Operations (2):', $rendered);
        $this->assertStringContainsString('/a.txt', $rendered);
        $this->assertStringNotContainsString(PMF_ROOT_DIR . '/a.txt', $rendered);
        $this->assertStringContainsString('Permission Grants (1):', $rendered);
        $this->assertStringContainsString('view_faqs', $rendered);
        $this->assertStringContainsString('Total Migrations: 1', $rendered);
        $this->assertStringContainsString('Total Operations: 8', $rendered);
        $this->assertStringContainsString('- sql: 1', $rendered);
    }
}
