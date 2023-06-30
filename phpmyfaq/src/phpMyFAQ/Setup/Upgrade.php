<?php

/**
 * The Upgrade class used for upgrading/installing phpMyFAQ from a ZIP file.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-06-30
 */

namespace phpMyFAQ\Setup;

use phpMyFAQ\Configuration;
use phpMyFAQ\Setup;
use phpMyFAQ\System;

class Upgrade extends Setup
{
    private Configuration $configuration;

    public function __construct(protected System $system)
    {
        parent::__construct($this->system);
    }

    /**
     * Method to check if the filesystem is ready for the upgrade
     * @return void
     */
    public function checkFilesystem()
    {
    }

    /**
     * Method to download a phpMyFAQ package
     * @return void
     */
    public function downloadPackage()
    {
    }

    /**
     * Method to verify the downloaded phpMyFAQ package
     * @return void
     */
    public function verifyPackage()
    {
    }

    /**
     * Method to unpack the downloaded phpMyFAQ package
     * @return void
     */
    public function unpackPackage()
    {
    }

    /**
     * Method to create a temporary backup of the current files
     * @return void
     */
    public function createTemporaryBackup()
    {
    }

    /**
     * Method to restore from the temporary backup
     * @return void
     */
    public function restoreTemporaryBackup()
    {
    }

    /**
     * Method to install the package
     * @return void
     */
    public function installPackage()
    {
    }
}
