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
 * @copyright 2010-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
    private const VERSION_MAJOR = 4;

    /**
     * Minor version.
     */
    private const VERSION_MINOR = 0;

    /**
     * Patch level.
     */
    private const VERSION_PATCH_LEVEL = 0;

    /**
     * Pre-release version.
     */
    private const VERSION_PRE_RELEASE = 'alpha.2';

    /**
     * API version.
     */
    private const VERSION_API = '3.0';

    /**
     * Minimum required PHP version.
     */
    final public const VERSION_MINIMUM_PHP = '8.2.0';

    /**
     * phpMyFAQ homepage URL
     */
    final public const PHPMYFAQ_URL = 'https://www.phpmyfaq.de/';

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
        'json',
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
        'mysqli' => [
            self::VERSION_MINIMUM_PHP,
            'MySQL v8 / MariaDB v10 / Percona Server v8 / Galera Cluster v4 for MySQL'
        ],
        'pgsql' => [
            self::VERSION_MINIMUM_PHP,
            'PostgreSQL v10 or later'
        ],
        'sqlite3' => [
            self::VERSION_MINIMUM_PHP,
            'SQLite 3'
        ],
        'sqlsrv' => [
            self::VERSION_MINIMUM_PHP,
            'MS SQL Server 2016 or later'
        ]
    ];

    /**
     * Database handle.
     */
    private ?DatabaseDriver $database = null;

    /**
     * Returns the current version of phpMyFAQ for installation and
     * version in the database.
     * Releases will be numbered with the follow format:
     * <major>.<minor>.<patch>[-<prerelease>]
     */
    public static function getVersion(): string
    {
        $version = sprintf('%d.%d.%d', self::VERSION_MAJOR, self::VERSION_MINOR, self::VERSION_PATCH_LEVEL);
        return $version . (self::isDevelopmentVersion() ? '-' . self::VERSION_PRE_RELEASE : '');
    }

    /**
     * Returns the current major version of phpMyFAQ
     */
    public static function getMajorVersion(): string
    {
        return sprintf('%d.%d', self::VERSION_MAJOR, self::VERSION_MINOR);
    }

    /**
     * Returns the current API version of phpMyFAQ for installation and
     * version in the database.
     */
    public static function getApiVersion(): string
    {
        return self::VERSION_API;
    }

    public static function getPoweredByString(bool $withLink = false): string
    {
        if ($withLink) {
            return sprintf(
                'powered with ❤️ and ☕️ by <a class="%s" target="_blank" href="%s">phpMyFAQ</a> %s',
                'link-light text-decoration-none',
                self::PHPMYFAQ_URL,
                self::getVersion()
            );
        }
        return sprintf('powered with ❤️ and ☕️ by phpMyFAQ %s', self::getVersion());
    }

    /**
     * Returns the URL of the documentation
     */
    public static function getDocumentationUrl(): string
    {
        return sprintf('%sdocs/%s', self::PHPMYFAQ_URL, self::getMajorVersion());
    }

    /**
     * Returns true or false on SQLite3.
     *
     * @static
     */
    public static function isSqlite(string $dbType): bool
    {
        return 'sqlite3' === $dbType;
    }

    public static function isDevelopmentVersion(): bool
    {
        return strlen(self::VERSION_PRE_RELEASE) > 0;
    }

    /**
     * Print out the HTML5 Footer.
     */
    public static function renderFooter(bool $onePageBack = false): never
    {
        if ($onePageBack) {
            printf(
                '<p><a href="./index.php">%s</a></p>',
                'Back to the Setup page'
            );
        }

        printf(
            '</div></section></main><footer class="setup-footer container"><p class="text-end">%s</p></footer>' .
            '</body></html>',
            '&copy; 2001-2023 <a target="_blank" href="https://www.phpmyfaq.de/">phpMyFAQ Team</a>'
        );
        exit();
    }

    public function getDatabase(): ?DatabaseDriver
    {
        return $this->database;
    }

    public function setDatabase(DatabaseDriver $databaseDriver): System
    {
        $this->database = $databaseDriver;
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
            if ($item->isDot()) {
                continue;
            }
            if (!$item->isDir()) {
                continue;
            }
            $templates[$basename] = Template::getTplSetName() === $basename;
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

        if (isset($_ENV['REQUEST_SCHEME']) && 'https' === $_ENV['REQUEST_SCHEME'] && !str_contains($mainUrl, 'https')) {
            $mainUrl = str_replace('http://', 'https://', $mainUrl);
        }

        if (!str_ends_with($mainUrl, '/')) {
            $mainUrl .= '/';
        }

        return $mainUrl;
    }

    /**
     * Returns true, if phpMyFAQ is running on HTTPS
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
            if (extension_loaded($extension)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks for required PHP extensions.
     */
    public function checkRequiredExtensions(): bool
    {
        foreach ($this->requiredExtensions as $requiredExtension) {
            if (!extension_loaded($requiredExtension)) {
                $this->missingExtensions[] = $requiredExtension;
            }
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
     * @throws Exception
     */
    public function createHashes(): string
    {
        $dateTime = new DateTime();
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(PMF_ROOT_DIR),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $hashes = [
            'created' => $dateTime->format('Y-m-d H:i:sP'),
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
                if (
                    'php' === pathinfo((string) $file->getFilename(), PATHINFO_EXTENSION) && !str_contains(
                        (string)$file->getPath(),
                        '/tests/'
                    )
                ) {
                    $current = str_replace(PMF_ROOT_DIR, '', (string) $file->getPathname());

                    if (isset($ignoredFiles[$current])) {
                        continue;
                    }

                    $hashes[$current] = sha1(file_get_contents($file->getPathname()));
                }
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
        if ($this->database instanceof DatabaseDriver) {
            foreach ($queries as $query) {
                $this->database->query($query);
            }
        }
    }

    /**
     * Removes the database.php and the ldap.php if an installation failed.
     *
     * @todo have to be moved to the Installer class
     */
    public function cleanFailedInstallationFiles(): void
    {
        // Remove './config/database.php' file: no need of prompt anything to the user
        if (file_exists(PMF_ROOT_DIR . '/content/core/config/database.php')) {
            unlink(PMF_ROOT_DIR . '/content/core/config/database.php');
        }

        // Remove './config/ldap.php' file: no need of prompt anything to the user
        if (file_exists(PMF_ROOT_DIR . '/content/core/config/ldap.php')) {
            unlink(PMF_ROOT_DIR . '/content/core/config/ldap.php');
        }
    }
}
