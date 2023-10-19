<?php

/**
 * The abstract setup class for installation and updating phpMyFAQ.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL wasn't distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-04-04
 */

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;

abstract class Setup
{
    public function __construct(protected System $system)
    {
    }

    /**
     * Checks the minimum required PHP version, defined in System class.
     * Returns true if it's okay.
     */
    public function checkMinimumPhpVersion(): bool
    {
        return version_compare(PHP_VERSION, System::VERSION_MINIMUM_PHP) > 0;
    }

    /**
     * Checks if the database file exists.
     * @return bool
     */
    public function checkDatabaseFile(): bool
    {
        if (
            !file_exists(PMF_ROOT_DIR . '/config/database.php') &&
            !file_exists(PMF_ROOT_DIR . '/content/core/config/database.php')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Checks for the minimum PHP requirement and if the database credentials file is readable.
     * @throws Exception
     */
    public function checkPreUpgrade(string $databaseType): void
    {
        $database = null;
        if (!$this->checkMinimumPhpVersion()) {
            throw new Exception(
                sprintf('Sorry, but you need PHP %s or later!', System::VERSION_MINIMUM_PHP)
            );
        }

        if (!is_readable(PMF_ROOT_DIR . '/content/core/config/database.php')) {
            throw new Exception(
                'Sorry, but the database configuration file is not readable. Please check the permissions.'
            );
        }

        if ('' !== $databaseType) {
            $databaseFound = false;
            foreach ($this->system->getSupportedDatabases() as $database => $values) {
                if ($database === $databaseType) {
                    $databaseFound = true;
                    break;
                }
            }
            if (!$databaseFound) {
                throw new Exception(
                    sprintf('Sorry, but the database %s is not supported!', ucfirst($database))
                );
            }
        }
    }
}
