<?php

/**
 * Class for checking system requirements.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-01-13
 */

declare(strict_types=1);

namespace phpMyFAQ;

use DateTime;
use DirectoryIterator;
use Exception;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Twig\TwigWrapper;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpFoundation\Request;
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
    private const int VERSION_MAJOR = 4;

    /**
     * Minor version.
     */
    private const int VERSION_MINOR = 2;

    /**
     * Patch level.
     */
    private const int VERSION_PATCH_LEVEL = 0;

    /**
     * Pre-release version.
     */
    private const string VERSION_PRE_RELEASE = 'alpha';

    /**
     * API version.
     */
    private const string VERSION_API = '3.2';

    /**
     * Plugin version.
     */
    private const string PLUGIN_VERSION = '0.2.0';

    /**
     * Minimum required PHP version.
     */
    final public const string VERSION_MINIMUM_PHP = '8.4.0';

    /**
     * phpMyFAQ homepage URL
     */
    final public const string PHPMYFAQ_URL = 'https://www.phpmyfaq.de/';

    /**
     * GitHub Issues URL
     */
    final public const string GITHUB_ISSUES_URL = 'https://github.com/thorsten/phpMyFAQ/issues';

    /**
     * Array of required PHP extensions.
     *
     * @var array<string>
     */
    private array $requiredExtensions = [
        'curl',
        'fileinfo',
        'filter',
        'gd',
        'intl',
        'json',
        'mbstring',
        'openssl',
        'sodium',
        'xml',
        'zip',
    ];

    /**
     * Array of missing PHP extensions.
     *
     * @var array<string>
     */
    private array $missingExtensions = [];

    /**
     * Supported databases for phpMyFAQ.
     *
     * @var array<string, array<int, string>>
     */
    private array $supportedDatabases = [
        'pdo_mysql' => [
            self::VERSION_MINIMUM_PHP,
            'MySQL v8 / MariaDB v10 / Percona Server v8 / Galera Cluster v4 (PDO_MYSQL, preferred)',
        ],
        'mysqli' => [
            self::VERSION_MINIMUM_PHP,
            'MySQL v8 / MariaDB v10 / Percona Server v8 / Galera Cluster v4 (ext/mysqli)',
        ],
        'pdo_pgsql' => [
            self::VERSION_MINIMUM_PHP,
            'PostgreSQL v10 or later (PDO_PGSQL, preferred)',
        ],
        'pgsql' => [
            self::VERSION_MINIMUM_PHP,
            'PostgreSQL v10 or later',
        ],
        'pdo_sqlite' => [
            self::VERSION_MINIMUM_PHP,
            'SQLite 3 (PDO_SQLITE, preferred)',
        ],
        'sqlite3' => [
            self::VERSION_MINIMUM_PHP,
            'SQLite 3',
        ],
        'pdo_sqlsrv' => [
            self::VERSION_MINIMUM_PHP,
            'MS SQL Server 2016 or later / SQL Azure (PDO_SQLSRV, preferred)',
        ],
        'sqlsrv' => [
            self::VERSION_MINIMUM_PHP,
            'MS SQL Server 2016 or later',
        ],
    ];

    /**
     * Database handle.
     */
    private ?DatabaseDriver $databaseDriver = null;

    /**
     * Returns the current version of phpMyFAQ for installation and
     * version in the database.
     * Releases will be numbered with the following format:
     * <major>.<minor>.<patch>[-<prerelease>]
     */
    public static function getVersion(): string
    {
        $version = self::VERSION_MAJOR . '.' . self::VERSION_MINOR . '.' . self::VERSION_PATCH_LEVEL;
        return $version . (self::isDevelopmentVersion() ? '-' . self::VERSION_PRE_RELEASE : '');
    }

    /**
     * Returns the current major version of phpMyFAQ
     */
    public static function getMajorVersion(): string
    {
        return self::VERSION_MAJOR . '.' . self::VERSION_MINOR;
    }

    /**
     * Returns the current API version of phpMyFAQ for installation and
     * version in the database.
     */
    public static function getApiVersion(): string
    {
        return self::VERSION_API;
    }

    /**
     * Returns the current plugin version of phpMyFAQ
     */
    public static function getPluginVersion(): string
    {
        return self::PLUGIN_VERSION;
    }

    public static function getPoweredByString(): string
    {
        return 'powered with ❤️ and ☕️ by <a class="link-light text-decoration-none" target="_blank" href="'
        . self::PHPMYFAQ_URL
        . '">phpMyFAQ</a> '
        . self::getVersion();
    }

    public static function getPoweredByPlainString(): string
    {
        return 'powered with ❤️ and ☕️ by phpMyFAQ ' . self::getVersion();
    }

    /**
     * Returns the URL of the documentation
     */
    public static function getDocumentationUrl(): string
    {
        return self::PHPMYFAQ_URL . 'docs/' . self::getMajorVersion();
    }

    public static function getGitHubIssuesUrl(): string
    {
        return self::GITHUB_ISSUES_URL;
    }

    /**
     * Returns true or false on SQLite3.
     *
     * @static
     */
    public static function isSqlite(string $dbType): bool
    {
        return 'sqlite3' === $dbType || 'pdo_sqlite' === $dbType;
    }

    public static function isDevelopmentVersion(): bool
    {
        return self::VERSION_PRE_RELEASE !== '';
    }

    public function getDatabase(): ?DatabaseDriver
    {
        return $this->databaseDriver;
    }

    public function setDatabase(DatabaseDriver $databaseDriver): System
    {
        $this->databaseDriver = $databaseDriver;
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
        $systemFolder = ['admin', 'setup', 'error'];

        foreach (new DirectoryIterator(PMF_ROOT_DIR . '/assets/templates/') as $item) {
            $basename = $item->getBasename();
            if ($item->isDot()) {
                continue;
            }

            if (!$item->isDir()) {
                continue;
            }

            if (in_array($basename, $systemFolder, strict: true)) {
                continue;
            }

            $templates[$basename] = TwigWrapper::getTemplateSetName() === $basename;
        }

        return $templates;
    }

    /**
     * Returns the locally supported databases.
     *
     * @return array<string, string>
     */
    public function getSupportedSafeDatabases(): array
    {
        $databases = [];
        foreach ($this->getSupportedDatabases() as $extension => $database) {
            if (!extension_loaded($extension)) {
                continue;
            }

            if (version_compare(PHP_VERSION, $database[0]) < 0) {
                continue;
            }

            $databases[$extension] = $database[1];
        }

        return $databases;
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
     */
    public function getSystemUri(Configuration $configuration): string
    {
        $mainUrl = $configuration->getDefaultUrl();

        if (Request::createFromGlobals()->isSecure() && !str_contains($mainUrl, needle: 'https')) {
            $mainUrl = str_replace(search: 'http://', replace: 'https://', subject: $mainUrl);
        }

        if (!str_ends_with($mainUrl, needle: '/')) {
            $mainUrl .= '/';
        }

        return $mainUrl;
    }

    /**
     * Returns true if phpMyFAQ is running on HTTPS
     */
    public function getHttpsStatus(): bool
    {
        return Request::createFromGlobals()->isSecure();
    }

    /**
     * Checks for installed database extensions, if the first supported
     * extension is enabled, return true.
     */
    public function checkDatabase(): bool
    {
        foreach (array_keys($this->supportedDatabases) as $extension) {
            if (!extension_loaded($extension)) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Checks for required PHP extensions.
     */
    public function checkRequiredExtensions(): bool
    {
        foreach ($this->requiredExtensions as $requiredExtension) {
            if (extension_loaded($requiredExtension)) {
                continue;
            }

            $this->missingExtensions[] = $requiredExtension;
        }

        return count($this->missingExtensions) <= 0;
    }

    /**
     * Checks for an installed phpMyFAQ version
     */
    public function checkInstallation(): bool
    {
        return !is_file(PMF_ROOT_DIR . '/content/core/config/database.php');
    }

    /**
     * @return array<string>
     */
    public function getMissingExtensions(): array
    {
        return $this->missingExtensions;
    }

    /**
     * Creates a JSON object with all .php files of phpMyFAQ with their sha1 hashes.
     *
     * @throws Exception
     */
    public function createHashes(): string
    {
        $dateTime = new DateTime();
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(PMF_ROOT_DIR),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        $hashes = [
            'created' => $dateTime->format(format: 'Y-m-d H:i:sP'),
        ];
        $ignoredFiles = [
            '/content/core/config/azure.php' => false,
            '/content/core/config/constants.php' => false,
            '/content/core/config/constants_elasticsearch.php' => false,
            '/content/core/config/database.php' => false,
            '/content/core/config/elasticsearch.php' => false,
            '/content/core/config/ldap.php' => false,
        ];
        $current = '';

        try {
            foreach ($files as $file) {
                if ($file->isDir()) {
                    continue;
                }

                if ('php' !== pathinfo((string) $file->getFilename(), PATHINFO_EXTENSION)) {
                    continue;
                }

                if (str_contains((string) $file->getPath(), needle: '/tests/')) {
                    continue;
                }

                if (str_contains((string) $file->getPath(), needle: '/multisites/')) {
                    continue;
                }

                if (str_contains((string) $file->getPath(), needle: '/upgrades/')) {
                    continue;
                }

                $current = str_replace(PMF_ROOT_DIR, replace: '', subject: (string) $file->getPathname());

                if (isset($ignoredFiles[$current])) {
                    continue;
                }

                $hashes[$current] = sha1(file_get_contents($file->getPathname()));
            }
        } catch (UnexpectedValueException $unexpectedValueException) {
            $hashes[$current . ' failed'] = $unexpectedValueException->getMessage();
        }

        return json_encode($hashes, JSON_THROW_ON_ERROR);
    }

    /**
     * Drops all given tables
     *
     * @param array<string> $queries
     */
    public function dropTables(array $queries): void
    {
        if ($this->databaseDriver instanceof DatabaseDriver) {
            foreach ($queries as $query) {
                $this->databaseDriver->query($query);
            }
        }
    }
}
