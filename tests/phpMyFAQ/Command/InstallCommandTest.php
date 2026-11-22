<?php

/**
 * Tests for the InstallCommand.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 */

declare(strict_types=1);

namespace phpMyFAQ\Command;

use phpMyFAQ\Filter;
use phpMyFAQ\Setup\AbstractSetup;
use phpMyFAQ\Setup\Installer;
use phpMyFAQ\System;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(InstallCommand::class)]
#[UsesClass(System::class)]
#[UsesClass(Installer::class)]
#[UsesClass(AbstractSetup::class)]
#[UsesClass(Filter::class)]
class InstallCommandTest extends TestCase
{
    private InstallCommand $command;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new InstallCommand(new System());
        $this->commandTester = new CommandTester($this->command);
    }

    public function testCommandConfiguration(): void
    {
        $this->assertSame('phpmyfaq:install', $this->command->getName());

        $definition = $this->command->getDefinition();
        foreach ([
            'db-type',
            'db-server',
            'db-port',
            'db-user',
            'db-password',
            'db-name',
            'admin-user',
            'admin-password',
            'force',
        ] as $option) {
            $this->assertTrue($definition->hasOption($option), sprintf('Missing option "%s"', $option));
        }
    }

    public function testFailsWhenPasswordTooShort(): void
    {
        $exitCode = $this->commandTester->execute([
            '--db-type' => 'sqlite3',
            '--db-server' => '/tmp/should-not-be-created.sqlite',
            '--admin-password' => 'short',
        ]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('at least 8 characters', $this->commandTester->getDisplay());
    }

    /**
     * The login form verifies the sanitised form of the typed password, and that is the form
     * the installer has to store. The length is therefore checked on the sanitised password:
     * the tags of this one are stripped, which leaves four characters.
     */
    public function testChecksTheLengthOfThePasswordAsTheLoginFormWillSeeIt(): void
    {
        $exitCode = $this->commandTester->execute([
            '--db-type' => 'sqlite3',
            '--db-server' => '/tmp/should-not-be-created.sqlite',
            '--admin-password' => '<b></b><i></i>abcd',
        ]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('at least 8 characters', $this->commandTester->getDisplay());
    }

    public function testFailsWhenDatabaseServerMissingForNonSqlite(): void
    {
        $exitCode = $this->commandTester->execute([
            '--db-type' => 'mysqli',
            '--db-server' => '',
            '--admin-password' => 'password1234',
        ]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('database server is required', $this->commandTester->getDisplay());
    }

    /**
     * The cleanup is recorded instead of executed: Installer::cleanFailedInstallationFiles()
     * works on PMF_ROOT_DIR, the application directory of this checkout.
     */
    private function createCommandRecordingCleanups(System $system): InstallCommand
    {
        return new class($system) extends InstallCommand {
            public int $cleanups = 0;

            protected function removeFilesOfFailedInstallation(): void
            {
                ++$this->cleanups;
            }
        };
    }

    public function testKeepsTheConfigurationOfAnExistingInstallationWhenThePreCheckFails(): void
    {
        // The per-worker content copy holds the database.php of an installed phpMyFAQ.
        $databaseFile = PMF_CONFIG_DIR . '/database.php';
        $this->assertFileExists($databaseFile);
        $contents = file_get_contents($databaseFile);

        $command = $this->createCommandRecordingCleanups(new System());
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([
            '--db-type' => 'sqlite3',
            '--db-server' => '/tmp/should-not-be-created.sqlite',
            '--admin-password' => 'password1234',
        ]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('already installed', $commandTester->getDisplay());
        $this->assertSame(0, $command->cleanups, 'A failed pre-check must not remove any config file.');
        $this->assertFileExists($databaseFile);
        $this->assertSame($contents, file_get_contents($databaseFile));
        $this->assertFileDoesNotExist('/tmp/should-not-be-created.sqlite');
    }

    public function testRemovesTheFilesOfThisRunWhenTheInstallationFailsAfterThePreChecks(): void
    {
        $system = $this->createStub(System::class);
        $system->method('checkDatabase')->willReturn(true);
        $system->method('checkRequiredExtensions')->willReturn(true);
        // Passes the pre-check, then reports an installation that appeared in the meantime.
        $system->method('checkInstallation')->willReturn(true, false);

        $command = $this->createCommandRecordingCleanups($system);
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([
            '--db-type' => 'sqlite3',
            '--db-server' => '/tmp/should-not-be-created.sqlite',
            '--admin-password' => 'password1234',
        ]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertSame(1, $command->cleanups);
    }
}
