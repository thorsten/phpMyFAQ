<?php

/**
 * phpMyFAQ Update Console Command
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-06-16
 */

declare(strict_types=1);

namespace phpMyFAQ\Command;

use DateTime;
use phpMyFAQ\Configuration;
use phpMyFAQ\Setup\UpdateRunner;
use phpMyFAQ\System;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class UpdateCommand extends Command
{
    protected static string $defaultName = 'phpmyfaq:update';

    private Configuration $configuration;

    private System $system;

    public function __construct()
    {
        parent::__construct();

        $this->configuration = Configuration::getConfigurationInstance();
        $this->system = new System();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription(description: 'Executes the phpMyFAQ update process')
            ->addArgument(
                name: 'version',
                mode: InputArgument::OPTIONAL,
                description: 'Requested version for the update',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        $symfonyStyle->title(message: 'Start automatic phpMyFAQ update ...');

        try {
            $updateRunner = new UpdateRunner($this->configuration, $this->system);
            $result = $updateRunner->run($symfonyStyle);

            if (Command::SUCCESS !== $result) {
                return Command::FAILURE;
            }

            $symfonyStyle->success(message: strtr(string: 'phpMyFAQ was successfully updated to version version: on date:.', replace_pairs: [
                'version:' => System::getVersion(),
                'date:' => new DateTime()->format(format: 'Y-m-d H:i:s'),
            ]));

            return Command::SUCCESS;
        } catch (Throwable $throwable) {
            $symfonyStyle->error(message: 'Error during update: ' . $throwable->getMessage());
            return Command::FAILURE;
        }
    }
}
