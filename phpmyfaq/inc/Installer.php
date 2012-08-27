<?php
    /**
     * The Installer class installs phpMyFAQ. Classy.
     *
     * PHP Version 5.3
     *
     * This Source Code Form is subject to the terms of the Mozilla Public License,
     * v. 2.0. If a copy of the MPL was not distributed with this file, You can
     * obtain one at http://mozilla.org/MPL/2.0/.
     *
     * @category  phpMyFAQ
     * @package   Cache
     * @author    Florian Anderiasch <florian@phpmyfaq.net>
     * @copyright 2002-2012 phpMyFAQ Team
     * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
     * @link      http://www.phpmyfaq.de
     * @since     2012-08-27
     */

    if (!defined('IS_VALID_PHPMYFAQ')) {
        exit();
    }

    /**
     * Installer
     *
     * @category  phpMyFAQ
     * @package   Installer
     * @author    Florian Anderiasch <florian@phpmyfaq.net>
     * @copyright 2002-2012 phpMyFAQ Team
     * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
     * @link      http://www.phpmyfaq.de
     * @since     2012-08-27
     */

class PMF_Installer
{
    protected $_system;

    public function __construct()
    {
        $this->_system = new PMF_System();
    }

    public function checkBasicStuff()
    {
        if (!$this->checkMinimumPhpVersion()) {
            printf('<p class="alert alert-error">Sorry, but you need PHP %s or later!</p>', PMF_System::VERSION_MINIMUM_PHP);
            PMF_System::renderFooter();
        }

        if (! function_exists('date_default_timezone_set')) {
            echo '<p class="alert alert-error">Sorry, but setting a default timezone doesn\'t work in your environment!</p>';
            PMF_System::renderFooter();
        }

        if (! $this->_system->checkDatabase()) {
            echo '<p class="alert alert-error">No supported database detected! Please install one of the following' .
                ' database systems and enable the corresponding PHP extension in php.ini:</p>';
            echo '<ul>';
            foreach ($this->_system->getSupportedDatabases() as $database) {
                printf('    <li>%s</li>', $database[1]);
            }
            echo '</ul>';
            PMF_System::renderFooter();
        }

        if (! $this->_system->checkRequiredExtensions()) {
            echo '<p class="alert alert-error">The following extensions are missing! Please enable the PHP extension(s) in ' .
                'php.ini.</p>';
            echo '<ul>';
            foreach ($this->_system->getMissingExtensions() as $extension) {
                printf('    <li>ext/%s</li>', $extension);
            }
            echo '</ul>';
            PMF_System::renderFooter();
        }

        if (! $this->_system->checkphpMyFAQInstallation()) {
            echo '<p class="alert alert-error">It seems you\'re already running a version of phpMyFAQ. Please use the ' .
                '<a href="update.php">update script</a>.</p>';
            PMF_System::renderFooter();
        }
    }

    public function checkMinimumPhpVersion()
    {
        if (version_compare(PHP_VERSION, PMF_System::VERSION_MINIMUM_PHP, '<')) {
            return false;
        }
        return true;
    }

    public function checkFilesystemPermissions()
    {
        $instanceSetup = new PMF_Instance_Setup();
        $instanceSetup->setRootDir(PMF_ROOT_DIR);

        $dirs       = array('/attachments', '/config', '/data', '/images');
        $failedDirs = $instanceSetup->checkDirs($dirs);
        $numDirs    = sizeof($failedDirs);

        if (1 <= $numDirs) {
            printf(
                '<p class="alert alert-error">The following %s could not be created or %s not writable:</p><ul>',
                (1 < $numDirs) ? 'directories' : 'directory',
                (1 < $numDirs) ? 'are' : 'is'
            );
            foreach ($failedDirs as $dir) {
                echo "<li>$dir</li>\n";
            }
            printf(
                '</ul><p class="alert alert-error">Please create %s manually and/or change access to chmod 755 (or ' .
                    'greater if necessary).</p>',
                (1 < $numDirs) ? 'them' : 'it'
            );
            PMF_System::renderFooter();
        }
    }

    public function checkNoncriticalSettings()
    {
        if ((@ini_get('safe_mode') == 'On' || @ini_get('safe_mode') === 1)) {
            echo '<p class="alert alert-error">The PHP safe mode is enabled. You may have problems when phpMyFAQ tries to write ' .
                ' in some directories.</p>';
        }
        if (! extension_loaded('gd')) {
            echo '<p class="alert alert-error">You don\'t have GD support enabled in your PHP installation. Please enable GD ' .
                'support in your php.ini file otherwise you can\'t use Captchas for spam protection.</p>';
        }
        if (! function_exists('imagettftext')) {
            echo '<p class="alert alert-error">You don\'t have Freetype support enabled in the GD extension of your PHP ' .
                'installation. Please enable Freetype support in GD extension otherwise the Captchas for spam ' .
                'protection will be quite easy to break.</p>';
        }
        if (! extension_loaded('curl') || ! extension_loaded('openssl')) {
            echo '<p class="alert alert-error">You don\'t have cURL and/or OpenSSL support enabled in your PHP installation. ' .
                'Please enable cURL and/or OpenSSL support in your php.ini file otherwise you can\'t use the Twitter ' .
                ' support.</p>';
        }
    }
}