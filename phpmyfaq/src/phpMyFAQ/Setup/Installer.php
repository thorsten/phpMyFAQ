<?php

/**
 * The Installer class installs phpMyFAQ. Classy.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Florian Anderiasch <florian@phpmyfaq.net>
 * @copyright 2012-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-08-27
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup;

use Elastic\Elasticsearch\Exception\AuthenticationException;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Instance\Setup;
use phpMyFAQ\Setup\Installation\DefaultDataSeeder;
use phpMyFAQ\System;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Installer
 *
 * @package phpMyFAQ
 */
class Installer extends Setup
{
    /**
     * Constructor.
     *
     * @throws \Exception
     */
    public function __construct(
        private readonly System $system,
    ) {
        parent::__construct();
    }

    /**
     * Removes the database.php and the ldap.php if an installation failed.
     */
    public static function cleanFailedInstallationFiles(): void
    {
        if (file_exists(PMF_ROOT_DIR . '/content/core/config/database.php')) {
            unlink(PMF_ROOT_DIR . '/content/core/config/database.php');
        }

        if (file_exists(PMF_ROOT_DIR . '/content/core/config/ldap.php')) {
            unlink(PMF_ROOT_DIR . '/content/core/config/ldap.php');
        }
    }

    /**
     * Check the necessary stuff and throw an exception if something is wrong.
     * @throws Exception
     */
    public function checkBasicStuff(): void
    {
        if (!$this->checkMinimumPhpVersion()) {
            throw new Exception(sprintf('Sorry, but you need PHP %s or later!', System::VERSION_MINIMUM_PHP));
        }

        if (!function_exists('date_default_timezone_set')) {
            throw new Exception('Sorry, but setting a default timezone does not work in your environment!');
        }

        if (!$this->system->checkDatabase()) {
            throw new Exception('No supported database detected!');
        }

        if (!$this->system->checkRequiredExtensions()) {
            throw new Exception(sprintf('Some required PHP extensions are missing: %s', implode(
                ', ',
                $this->system->getMissingExtensions(),
            )));
        }

        if (!$this->system->checkInstallation()) {
            throw new Exception(
                'Looks like phpMyFAQ is already installed! Please use the <a href="../update">update</a>.',
            );
        }
    }

    /**
     * Checks if the file permissions are okay.
     */
    public function checkFilesystemPermissions(): ?string
    {
        $instanceSetup = new Setup();
        $instanceSetup->setRootDir(PMF_ROOT_DIR);

        $dirs = [
            '/content/core/config',
            '/content/core/data',
            '/content/core/logs',
            '/content/user/images',
            '/content/user/attachments',
        ];
        $failedDirs = $instanceSetup->checkDirs($dirs);
        $numDirs = count($failedDirs);

        $hints = '';
        if (1 <= $numDirs) {
            $hints .= sprintf(
                '<p class="alert alert-danger">The following %s could not be created or %s not writable:</p><ul>',
                1 < $numDirs ? 'directories' : 'directory',
                1 < $numDirs ? 'are' : 'is',
            );
            foreach ($failedDirs as $failedDir) {
                $hints .= "<li>{$failedDir}</li>\n";
            }

            return $hints
            . sprintf(
                '</ul><p class="alert alert-danger">Please create %s manually and/or change access to chmod 775 (or '
                . 'greater if necessary).</p>',
                1 < $numDirs ? 'them' : 'it',
            );
        }

        return null;
    }

    /**
     * Checks some non-critical settings and print some hints.
     *
     * @return string[]
     */
    public function checkNoncriticalSettings(): array
    {
        $hints = [];
        if (!$this->system->getHttpsStatus()) {
            $hints[] =
                '<p class="alert alert-warning">HTTPS support is not enabled in your web server.'
                . ' To ensure the security of your data and protect against potential vulnerabilities,'
                . ' we highly recommend enabling HTTPS. Please configure your web server to support HTTPS as soon as'
                . ' possible.</p>';
        }

        if (!extension_loaded('gd')) {
            $hints[] =
                '<p class="alert alert-warning">You don\'t have GD support enabled in your PHP installation. '
                . "Please enable GD support in your php.ini file otherwise you can't use Captchas for spam protection."
                . '</p>';
        }

        if (!function_exists('imagettftext')) {
            $hints[] =
                '<p class="alert alert-warning">You don\'t have Freetype support enabled in the GD extension '
                . ' of your PHP installation. Please enable Freetype support in GD extension otherwise the Captchas '
                . 'for spam protection will be quite easy to break.</p>';
        }

        if (!extension_loaded('curl') || !extension_loaded('openssl')) {
            $hints[] =
                '<p class="alert alert-warning">You don\'t have cURL and/or OpenSSL support enabled in your '
                . 'PHP installation. Please enable cURL and/or OpenSSL support in your php.ini file otherwise you '
                . " can't use Elasticsearch.</p>";
        }

        if (!extension_loaded('fileinfo')) {
            $hints[] =
                '<p class="alert alert-warning">You don\'t have Fileinfo support enabled in your PHP '
                . "installation. Please enable Fileinfo support in your php.ini file otherwise you can't use our "
                . 'backup/restore functionality.</p>';
        }

        if (!extension_loaded('sodium')) {
            $hints[] =
                '<p class="alert alert-warning">You don\'t have Sodium support enabled in your PHP '
                . "installation. Please enable Sodium support in your php.ini file otherwise you can't use our "
                . 'backup/restore functionality.</p>';
        }

        return $hints;
    }

    /**
     * @throws Exception
     */
    public function checkInitialRewriteBasePath(Request $request): bool
    {
        $basePath = $request->getBasePath();
        $basePath = rtrim($basePath, 'setup');

        $htaccessPath = PMF_ROOT_DIR . '/.htaccess';

        $htaccessUpdater = new HtaccessUpdater();
        return $htaccessUpdater->updateRewriteBase($htaccessPath, $basePath);
    }

    /**
     * Starts the installation.
     *
     * Delegates to InstallationInputValidator for input parsing and
     * InstallationRunner for the actual installation steps.
     *
     * @param array<string, mixed>|null $setup Optional setup array (for programmatic/test installs)
     * @throws Exception|AuthenticationException
     * @throws \Exception
     */
    public function startInstall(?array $setup = null): void
    {
        $validator = new InstallationInputValidator();
        $input = $validator->validate($setup);

        $runner = new InstallationRunner($this->system);
        $runner->run($input);
    }

    /**
     * Checks the minimum required PHP version, defined in System class.
     * Returns true if it's okay.
     */
    public function checkMinimumPhpVersion(): bool
    {
        return version_compare(PHP_VERSION, System::VERSION_MINIMUM_PHP) >= 0;
    }

    public function hasLdapSupport(): bool
    {
        return extension_loaded('ldap');
    }

    public function hasElasticsearchSupport(): bool
    {
        return extension_loaded('curl') && extension_loaded('openssl');
    }

    /**
     * Returns the form inputs array, delegating to DefaultDataSeeder.
     *
     * @return array<array<string, int|string>>
     * @throws \Exception
     */
    public function getFormInputs(): array
    {
        $seeder = new DefaultDataSeeder();
        return $seeder->getFormInputs();
    }
}
