<?php

/**
 * This command iterates recursively through the whole phpMyFAQ project and
 * creates SHA-1 keys for all files
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-04-11
 */

declare(strict_types=1);

namespace phpMyFAQ\Command;

use phpMyFAQ\System;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

#[AsCommand(name: 'phpmyfaq:hashes:create', description: 'Generate phpMyFAQ file hashes')]
class CreateHashesCommand extends Command
{
    public function __construct(
        private readonly System $system,
        private readonly Filesystem $filesystem,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            name: 'root',
            shortcut: null,
            mode: InputOption::VALUE_OPTIONAL,
            description: 'Optional phpMyFAQ root directory if different from the current install',
        )->addOption(
            name: 'out',
            shortcut: null,
            mode: InputOption::VALUE_OPTIONAL,
            description: 'Optional file to store the generated hashes as JSON',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        $root = $input->getOption('root');
        if (is_string($root) && '' !== $root) {
            if (!is_dir($root)) {
                $symfonyStyle->error(sprintf("The provided root directory '%s' does not exist.", $root));
                return Command::FAILURE;
            }

            $rootDir = rtrim($root, '/');
        } else {
            $rootDir = defined('PMF_ROOT_DIR') ? PMF_ROOT_DIR : null;
        }

        if (null !== $rootDir && !defined('PMF_ROOT_DIR')) {
            define('PMF_ROOT_DIR', $rootDir);
        }

        if (!defined('PMF_ROOT_DIR')) {
            $symfonyStyle->error(
                'PMF_ROOT_DIR is not defined. Provide --root option or run inside a configured installation.',
            );
            return Command::FAILURE;
        }

        try {
            $hashes = $this->system->createHashes();
        } catch (Throwable $throwable) {
            $symfonyStyle->error('Failed to create hashes: ' . $throwable->getMessage());
            return Command::FAILURE;
        }

        $outputPath = $input->getOption('out');
        if (is_string($outputPath) && '' !== $outputPath) {
            $directory = dirname($outputPath);
            if (!$this->filesystem->exists($directory)) {
                $this->filesystem->mkdir($directory);
            }

            try {
                $this->filesystem->dumpFile($outputPath, $hashes);
            } catch (Throwable $throwable) {
                $symfonyStyle->error('Unable to write hashes: ' . $throwable->getMessage());
                return Command::FAILURE;
            }

            $symfonyStyle->success(sprintf('Hashes written to %s', $outputPath));
            return Command::SUCCESS;
        }

        $output->writeln($hashes);
        return Command::SUCCESS;
    }
}
