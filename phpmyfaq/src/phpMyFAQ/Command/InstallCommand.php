<?php

/**
 * Installs phpMyFAQ non-interactively from CLI options or environment variables.
 *
 * This wraps the programmatic installer (Installer::startInstall()) so a fresh
 * instance can be set up headlessly — for Docker provisioning, CI pipelines, and
 * the end-to-end test suite — without going through the web-based setup wizard.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-06-07
 */

declare(strict_types=1);

namespace phpMyFAQ\Command;

use phpMyFAQ\Database;
use phpMyFAQ\Setup\Installer;
use phpMyFAQ\System;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'phpmyfaq:install',
    description: 'Installs phpMyFAQ non-interactively (headless setup for Docker, CI and e2e tests)',
)]
class InstallCommand extends Command
{
    public function __construct(
        private readonly System $system,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'db-type',
                null,
                InputOption::VALUE_REQUIRED,
                'Database driver (mysqli, pdo_mysql, pgsql, sqlite3, ...)',
                $this->env('PMF_DB_TYPE', 'mysqli'),
            )
            ->addOption(
                'db-server',
                null,
                InputOption::VALUE_REQUIRED,
                'Database host, or absolute file path for SQLite',
                $this->env('PMF_DB_HOST', ''),
            )
            ->addOption(
                'db-port',
                null,
                InputOption::VALUE_REQUIRED,
                'Database port (ignored for SQLite)',
                $this->env('PMF_DB_PORT', '3306'),
            )
            ->addOption(
                'db-user',
                null,
                InputOption::VALUE_REQUIRED,
                'Database user (ignored for SQLite)',
                $this->env('PMF_DB_USER', ''),
            )
            ->addOption(
                'db-password',
                null,
                InputOption::VALUE_REQUIRED,
                'Database password (ignored for SQLite)',
                $this->env('PMF_DB_PASSWORD', ''),
            )
            ->addOption(
                'db-name',
                null,
                InputOption::VALUE_REQUIRED,
                'Database name (ignored for SQLite)',
                $this->env('PMF_DB_NAME', 'phpmyfaq'),
            )
            ->addOption(
                'admin-user',
                null,
                InputOption::VALUE_REQUIRED,
                'Admin login name',
                $this->env('PMF_ADMIN_USER', 'admin'),
            )
            ->addOption(
                'admin-password',
                null,
                InputOption::VALUE_REQUIRED,
                'Admin password (minimum 8 characters)',
                $this->env('PMF_ADMIN_PASSWORD', ''),
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Remove an existing config/database.php before installing (for fresh CI databases)',
            )
            ->addOption(
                'base-url',
                null,
                InputOption::VALUE_REQUIRED,
                'Public base URL stored as main.referenceURL (e.g. http://localhost:8765). '
                . 'Required for correct absolute links when installing headlessly.',
                $this->env('PMF_BASE_URL', ''),
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('phpMyFAQ Headless Installer');

        if ($input->getOption('force')) {
            Installer::cleanFailedInstallationFiles();
            $io->note('Removed any existing installation config files (--force).');
        }

        /** @var string $dbType */
        $dbType = (string) $input->getOption('db-type');
        $isSqlite = System::isSqlite($dbType);

        /** @var string $adminPassword */
        $adminPassword = (string) $input->getOption('admin-password');
        if (strlen($adminPassword) < 8) {
            $io->error(
                'The admin password must be at least 8 characters. Pass --admin-password or set PMF_ADMIN_PASSWORD.',
            );
            return Command::FAILURE;
        }

        $dbServer = (string) $input->getOption('db-server');
        if ($dbServer === '' && !$isSqlite) {
            $io->error('A database server is required. Pass --db-server or set PMF_DB_HOST.');
            return Command::FAILURE;
        }

        $setup = [
            'dbType' => $dbType,
            'dbServer' => $dbServer,
            'dbPort' => $isSqlite ? null : (int) $input->getOption('db-port'),
            'dbUser' => (string) $input->getOption('db-user'),
            'dbPassword' => (string) $input->getOption('db-password'),
            'dbDatabaseName' => (string) $input->getOption('db-name'),
            'loginname' => (string) $input->getOption('admin-user'),
            'password' => $adminPassword,
            'password_retyped' => $adminPassword,
        ];

        $baseUrl = (string) $input->getOption('base-url');

        try {
            $installer = new Installer($this->system);
            $installer->checkBasicStuff();
            $installer->startInstall($setup);

            // When installing headlessly there is no HTTP host to derive the public
            // URL from, so the stored main.referenceURL would be wrong (e.g. the CLI
            // script path). Override it with the explicit base URL when provided.
            // Use a fresh connection: the installer's own connection lifecycle does
            // not reliably flush a late write back to the database file.
            if ($baseUrl !== '') {
                $database = Database::factory($dbType);
                $database->connect(
                    $setup['dbServer'],
                    $setup['dbUser'],
                    $setup['dbPassword'],
                    $setup['dbDatabaseName'],
                    $setup['dbPort'],
                );
                $database->query(sprintf(
                    "UPDATE %sfaqconfig SET config_value = '%s' WHERE config_name = 'main.referenceURL'",
                    Database::getTablePrefix(),
                    $database->escape($baseUrl),
                ));
            }
        } catch (Throwable $throwable) {
            Installer::cleanFailedInstallationFiles();
            $io->error('Installation failed: ' . strip_tags($throwable->getMessage()));
            if ($output->isVerbose()) {
                $io->writeln($throwable->getTraceAsString());
            }

            return Command::FAILURE;
        }

        $io->success(sprintf(
            'phpMyFAQ installed (%s) with admin user "%s".',
            $isSqlite ? 'SQLite: ' . $dbServer : $dbType . '://' . $dbServer,
            (string) $input->getOption('admin-user'),
        ));

        return Command::SUCCESS;
    }

    private function env(string $name, string $default): string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);
        return is_string($value) && $value !== '' ? $value : $default;
    }
}
