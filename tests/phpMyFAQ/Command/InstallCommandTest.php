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

use phpMyFAQ\System;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(InstallCommand::class)]
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
}
