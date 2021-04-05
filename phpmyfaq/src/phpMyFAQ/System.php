<?php

/**
 * Class for checking system requirements.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-01-13
 */

namespace phpMyFAQ;

use DateTime;
use DirectoryIterator;
use Exception;
use phpMyFAQ\Database\DatabaseDriver;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use UnexpectedValueException;

/**
 * Class System
 *
 * @package phpMyFAQ
 */
class System
{
    /**
     * Major version.
     */
    private const VERSION_MAJOR = 3;

    /**
     * Minor version.
     */
    private const VERSION_MINOR = 1;

    /**
     * Patch level.
     */
    private const VERSION_PATCH_LEVEL = 0;

    /**
     * Pre-release version.
     */
    private const VERSION_PRE_RELEASE = 'beta';

    /**
     * API version.
     */
    private const VERSION_API = '2.1';

    /**
     * Minimum required PHP version.
     */
    public const VERSION_MINIMUM_PHP = '7.3.0';

    /**
     * Array of required PHP extensions.
     *
     * @var array<string>
     */
    private $requiredExtensions = [
        'curl',
        'fileinfo',
        'filter',
        'gd',
        'json',
        'zip',
        'xml'
    ];

    /**
     * Array of missing PHP extensions.
     *
     * @var array<string>
     */
    private $missingExtensions = [];

    /**
     * Supported databases for phpMyFAQ.
     *
     * @var array<string, array<int, string>>
     */
    private $supportedDatabases = [
        'mysqli' => [self::VERSION_MINIMUM_PHP, 'MySQL / Percona Server / MariaDB'],
        'pgsql' => [self::VERSION_MINIMUM_PHP, 'PostgreSQL'],
        'sqlite3' => [self::VERSION_MINIMUM_PHP, 'SQLite 3'],
        'sqlsrv' => [self::VERSION_MINIMUM_PHP, 'MS SQL Server']
    ];

    /**
     * Database handle.
     *
     * @var DatabaseDriver
     */
    private $database = null;

    /**
     * Returns the current version of phpMyFAQ for installation and
     * version in the database.
     * Releases will be numbered with the follow format:
     * <major>.<minor>.<patch>[-<prerelease>]
     *
     * @return string
     */
    public static function getVersion(): string
    {
        if (null !== self::VERSION_PRE_RELEASE) {
            return sprintf(
                '%d.%d.%d-%s',
                self::VERSION_MAJOR,
                self::VERSION_MINOR,
                self::VERSION_PATCH_LEVEL,
                self::VERSION_PRE_RELEASE
            );
        } else { // @phpstan-ignore-line
            return sprintf(
                '%d.%d.%d',
                self::VERSION_MAJOR,
                self::VERSION_MINOR,
                self::VERSION_PATCH_LEVEL
            );
        }
    }

    /**
     * Returns the current API version of phpMyFAQ for installation and
     * version in the database.
     *
     * @return string
     */
    public static function getApiVersion(): string
    {
        return self::VERSION_API;
    }

    /**
     * Returns true or false on SQLite3.
     *
     * @static
     * @param string $dbType
     * @return bool
     */
    public static function isSqlite(string $dbType): bool
    {
        return 'sqlite3' === $dbType;
    }

    /**
     * Print out the HTML5 Footer.
     *
     * @param bool $onePageBack
     */
    public static function renderFooter(bool $onePageBack = false): void
    {
        if (true === $onePageBack) {
            printf(
                '<p><a href="./index.php">%s</a></p>',
                'Back to the Setup page'
            );
        }
        printf(
            '</div></section></main><footer class="setup-footer container"><p class="text-right">%s</p></footer>' .
            '</body></html>',
            '&copy; 2001-2021 <a target="_blank" href="https://www.phpmyfaq.de/">phpMyFAQ Team</a>'
        );
        exit(-1);
    }

    /**
     * Sets the database handler.
     *
     * @param DatabaseDriver $database
     * @return System
     */
    public function setDatabase(DatabaseDriver $database): System
    {
        $this->database = $database;
        return $this;
    }

    /**
     * Returns all available templates as array.
     *
     * @return array<string, bool>
     */
    public function getAvailableTemplates(): array
    {
        $templates = [];

        foreach (new DirectoryIterator(PMF_ROOT_DIR . '/assets/themes/') as $item) {
            $basename = $item->getBasename();
            if (!$item->isDot() && $item->isDir()) {
                $templates[$basename] = Template::getTplSetName() === $basename;
            }
        }

        return $templates;
    }

    /**
     * Returns the locally supported databases.
     *
     * @param bool $returnAsHtml
     * @return array<string, string>
     */
    public function getSupportedSafeDatabases(bool $returnAsHtml = false): array
    {
        $retVal = [];
        foreach ($this->getSupportedDatabases() as $extension => $database) {
            if (extension_loaded($extension) && version_compare(PHP_VERSION, $database[0]) >= 0) {
                if ($returnAsHtml) {
                    $retVal[] = sprintf('<option value="%s">%s</option>', $extension, $database[1]);
                } else {
                    $retVal[$extension] = $database;
                }
            }
        }

        return $retVal;
    }

    /**
     * Returns the supported databases.
     *
     * @return array<string, array<int, string>>
     */
    public function getSupportedDatabases(): array
    {
        return $this->supportedDatabases;
    }

    /**
     * Checks if the system URI is running with http or https.
     *
     * @param Configuration $faqConfig
     * @return string
     */
    public function getSystemUri(Configuration $faqConfig): string
    {
        $mainUrl = $faqConfig->getDefaultUrl();

        if (isset($_ENV['REQUEST_SCHEME']) && 'https' === $_ENV['REQUEST_SCHEME']) {
            if (false === strpos($mainUrl, 'https')) {
                $mainUrl = str_replace('http://', 'https://', $mainUrl);
            }
        }

        if ('/' !== substr($mainUrl, -1)) {
            $mainUrl .= '/';
        }

        return $mainUrl;
    }

    /**
     * Checks for installed database extensions, if the first supported
     * extension is enabled, return true.
     *
     * @return bool
     */
    public function checkDatabase(): bool
    {
        foreach ($this->supportedDatabases as $extension => $database) {
            if (extension_loaded($extension)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks for required PHP extensions.
     *
     * @return bool
     */
    public function checkRequiredExtensions(): bool
    {
        foreach ($this->requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                $this->missingExtensions[] = $extension;
            }
        }

        if (count($this->missingExtensions) > 0) {
            return false;
        }

        return true;
    }

    /**
     * Checks for an installed phpMyFAQ version.
     * config/database.php -> phpMyFAQ 2.6 and later
     *
     * @return bool
     */
    public function checkInstallation(): bool
    {
        return !is_file(PMF_ROOT_DIR . '/config/database.php');
    }

    /**
     * Returns all missing extensions.
     *
     * @return array<string>
     */
    public function getMissingExtensions(): array
    {
        return $this->missingExtensions;
    }

    /**
     * Creates a JSON object with all .php files of phpMyFAQ with their sha1 hashes.
     *
     * @return string
     * @throws Exception
     */
    public function createHashes(): string
    {
        $created = new DateTime();
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(PMF_ROOT_DIR),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $hashes = [
            'created' => $created->format('Y-m-d H:i:sP'),
        ];
        $ignoredFiles = [
            '/config/constants.php' => false,
            '/config/constants_elasticsearch.php' => false,
            '/config/database.php' => false,
            '/config/ldap.php' => false,
        ];
        $current = '';

        try {
            foreach ($files as $file) {
                if (
                    'php' === pathinfo($file->getFilename(), PATHINFO_EXTENSION) && !preg_match(
                        '#/tests/#',
                        $file->getPath()
                    )
                ) {
                    $current = str_replace(PMF_ROOT_DIR, '', $file->getPathname());

                    if (isset($ignoredFiles[$current])) {
                        continue;
                    }
                    $hashes[$current] = sha1(file_get_contents($file->getPathname()));
                }
            }
        } catch (UnexpectedValueException $e) {
            $hashes[$current . ' failed'] = $e->getMessage();
        }

        return json_encode($hashes);
    }

    /**
     * Drops all given tables
     *
     * @param array<string> $queries
     */
    public function dropTables(array $queries): void
    {
        if ($this->database instanceof DatabaseDriver) {
            foreach ($queries as $query) {
                $this->database->query($query);
            }
        }
    }

    /**
     * Removes the database.php and the ldap.php if an installation failed.
     */
    public function cleanFailedInstallationFiles(): void
    {
        // Remove './config/database.php' file: no need of prompt anything to the user
        if (file_exists(PMF_ROOT_DIR . '/config/database.php')) {
            unlink(PMF_ROOT_DIR . '/config/database.php');
        }
        // Remove './config/ldap.php' file: no need of prompt anything to the user
        if (file_exists(PMF_ROOT_DIR . '/config/ldap.php')) {
            unlink(PMF_ROOT_DIR . '/config/ldap.php');
        }
    }
}
