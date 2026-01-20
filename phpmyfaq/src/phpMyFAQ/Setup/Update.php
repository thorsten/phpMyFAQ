<?php

/**
 * The Update class updates phpMyFAQ. Classy.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-04-03
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup;

use phpMyFAQ\Administration\AdminLogRepository;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Enums\ReleaseType;
use phpMyFAQ\Filesystem\Filesystem;
use phpMyFAQ\Forms;
use phpMyFAQ\System;
use phpMyFAQ\User;
use Random\RandomException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\HttpFoundation\Request;
use ZipArchive;

class Update extends AbstractSetup
{
    private string $version;

    /** @var string[] */
    private array $queries = [];

    private bool $dryRun = false;

    /** @var string[] */
    private array $dryRunQueries = [];

    private ?string $backupFilename = null;

    public function __construct(
        protected System $system,
        private readonly Configuration $configuration,
    ) {
        parent::__construct($this->system);
    }

    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    /**
     * Checks if the "faqconfig" table is available
     */
    public function isConfigTableNotAvailable(DatabaseDriver $databaseDriver): bool
    {
        $query = sprintf('SELECT * FROM %s%s', Database::getTablePrefix(), 'faqconfig');
        $result = $databaseDriver->query($query);
        return $databaseDriver->numRows($result) === 0;
    }

    /**
     * Creates a backup of the current config files
     * @throws Exception
     * @throws RandomException
     */
    public function createConfigBackup(string $configDir): string
    {
        $outputZipFile = $configDir . DIRECTORY_SEPARATOR . $this->getBackupFilename();

        $zipArchive = new ZipArchive();
        if ($zipArchive->open($outputZipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Cannot create config backup file.');
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($configDir),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($files as $file) {
            if ($file instanceof SplFileInfo) {
                $filePath = $file->getRealPath() ?: $file->getPathname();
                $isDir = $file->isDir();
                $isFile = $file->isFile();
            } else {
                $filePath = is_string($file) ? $file : (string) $file;
                $filePath = realpath($filePath) ?: $filePath;
                $isDir = is_dir($filePath);
                $isFile = is_file($filePath);
            }
            if ($filePath === false) {
                continue;
            }
            if ($filePath === null) {
                continue;
            }
            if ($filePath === '') {
                continue;
            }

            // Exclude the zip we are currently writing
            if ($filePath === $outputZipFile) {
                continue;
            }

            // Only include entries inside the config directory
            if (!str_contains($filePath, $configDir . DIRECTORY_SEPARATOR) && $filePath !== $configDir) {
                continue;
            }

            // Compute a relative path inside the archive
            $relativePath = str_replace($configDir . DIRECTORY_SEPARATOR, '', $filePath);
            $relativePath = ltrim($relativePath, DIRECTORY_SEPARATOR);

            if ($isDir) {
                // Ensure directory entries end with a slash
                $zipArchive->addEmptyDir(rtrim($relativePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
            } elseif ($isFile) {
                $zipArchive->addFile($filePath, $relativePath);
            }
        }

        $zipArchive->close();

        if (!file_exists($outputZipFile)) {
            throw new Exception('Cannot store config backup file.');
        }

        return $this->configuration->getDefaultUrl() . 'content/core/config/' . $this->getBackupFilename();
    }

    /**
     * @throws Exception
     */
    public function checkInitialRewriteBasePath(Request $request): bool
    {
        $basePath = $request->getBasePath();
        $basePath = rtrim($basePath, 'update');

        $htaccessPath = PMF_ROOT_DIR . '/.htaccess';

        $htaccessUpdater = new HtaccessUpdater();
        return $htaccessUpdater->updateRewriteBase($htaccessPath, $basePath);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function applyUpdates(): bool
    {
        // 3.2 updates
        $this->applyUpdates320Alpha();
        $this->applyUpdates320Beta();
        $this->applyUpdates320Beta2();
        $this->applyUpdates320RC();
        $this->applyUpdates323();

        // 4.0 updates
        $this->applyUpdates400Alpha();
        $this->applyUpdates400Alpha2();
        $this->applyUpdates400Alpha3();
        $this->applyUpdates400Beta2();
        $this->applyUpdates405();
        $this->applyUpdates407();
        $this->applyUpdates409();

        // 4.1 updates
        $this->applyUpdates410Alpha();
        $this->applyUpdates410Alpha2();
        $this->applyUpdates410Alpha3();

        // 4.2 updates
        $this->applyUpdates420Alpha();

        // Optimize the tables
        $this->optimizeTables();

        // Execute queries
        $this->executeQueries();

        // Always the last step: Update version number
        $this->updateVersion();

        return true;
    }

    public function optimizeTables(): void
    {
        switch (Database::getType()) {
            case 'mysqli':
                $this->configuration->getDb()->getTableNames(Database::getTablePrefix());
                foreach ($this->configuration->getDb()->tableNames as $tableName) {
                    $this->queries[] = 'OPTIMIZE TABLE ' . $tableName;
                }

                break;
            case 'pgsql':
                $this->queries[] = 'VACUUM ANALYZE;';
                break;
        }
    }

    public function setDryRun(bool $dryRun): void
    {
        $this->dryRun = $dryRun;
    }

    public function getDryRunQueries(): array
    {
        return $this->dryRunQueries;
    }

    /**
     * @throws Exception
     */
    private function executeQueries(): void
    {
        if ($this->dryRun) {
            foreach ($this->queries as $query) {
                $this->dryRunQueries[] = $query;
            }
        } else {
            foreach ($this->queries as $query) {
                try {
                    $this->configuration->getDb()->query($query);
                } catch (Exception $exception) {
                    throw new Exception($exception->getMessage());
                }
            }
        }
    }

    private function applyUpdates320Alpha(): void
    {
        if (version_compare($this->version, '3.2.0-alpha', '<')) {
            // Microsoft Entra ID support and 2FA-support
            $this->configuration->add('security.enableSignInWithMicrosoft', false);

            if ('sqlite3' === Database::getType()) {
                $this->queries[] = sprintf(
                    'ALTER TABLE %sfaquser
                        ADD COLUMN refresh_token TEXT NULL DEFAULT NULL,
                        ADD COLUMN access_token TEXT NULL DEFAULT NULL,
                        ADD COLUMN code_verifier VARCHAR(255) NULL DEFAULT NULL,
                        ADD COLUMN jwt TEXT NULL DEFAULT NULL;',
                    Database::getTablePrefix(),
                );

                $this->queries[] = sprintf(
                    'ALTER TABLE %sfaquserdata
                        ADD COLUMN twofactor_enabled INT(1) NULL DEFAULT 0,
                        ADD COLUMN secret VARCHAR(128) NULL DEFAULT NULL',
                    Database::getTablePrefix(),
                );
            } else {
                $this->queries[] = sprintf(
                    'ALTER TABLE %sfaquser
                        ADD refresh_token TEXT NULL DEFAULT NULL,
                        ADD access_token TEXT NULL DEFAULT NULL,
                        ADD code_verifier VARCHAR(255) NULL DEFAULT NULL,
                        ADD jwt TEXT NULL DEFAULT NULL;',
                    Database::getTablePrefix(),
                );

                $this->queries[] = sprintf(
                    'ALTER TABLE %sfaquserdata
                        ADD twofactor_enabled INT NULL DEFAULT 0,
                        ADD secret VARCHAR(128) NULL DEFAULT NULL',
                    Database::getTablePrefix(),
                );
            }

            // New backup
            $this->queries[] = sprintf('CREATE TABLE %sfaqbackup (
                    id INT NOT NULL,
                    filename VARCHAR(255) NOT NULL,
                    authkey VARCHAR(255) NOT NULL,
                    authcode VARCHAR(255) NOT NULL,
                    created timestamp NOT NULL,
                    PRIMARY KEY (id))', Database::getTablePrefix());

            // Migrate MySQL from MyISAM to InnoDB
            if ('mysqli' === Database::getType()) {
                $this->queries[] = sprintf('ALTER TABLE %sfaqdata ENGINE=INNODB', Database::getTablePrefix());
            }

            // new options
            $this->configuration->add('main.enableAskQuestions', true);
            $this->configuration->add('main.enableNotifications', true);

            // update options
            $this->configuration->rename('security.loginWithEmailAddress', 'security.loginWithEmailAddress');
            if ($this->configuration->get(item: 'security.permLevel') === 'large') {
                $this->configuration->set('security.permLevel', 'medium');
            }

            // Google ReCAPTCHAv3 support
            $this->configuration->add('security.enableGoogleReCaptchaV2', false);
            $this->configuration->add('security.googleReCaptchaV2SiteKey', '');
            $this->configuration->add('security.googleReCaptchaV2SecretKey', '');

            // Remove section tables
            $this->queries[] = sprintf('DROP TABLE %sfaqsections', Database::getTablePrefix());
            $this->queries[] = sprintf('DROP TABLE %sfaqsection_category', Database::getTablePrefix());
            $this->queries[] = sprintf('DROP TABLE %sfaqsection_group', Database::getTablePrefix());
            $this->queries[] = sprintf('DROP TABLE %sfaqsection_news', Database::getTablePrefix());
        }
    }

    private function applyUpdates320Beta(): void
    {
        if (version_compare($this->version, '3.2.0-beta', '<')) {
            $this->configuration->add('mail.remoteSMTPDisableTLSPeerVerification', false);
            $this->configuration->delete('main.enableLinkVerification');

            // Delete link verification columns
            $this->queries[] = sprintf(
                'ALTER TABLE %sfaqdata DROP COLUMN links_state, DROP COLUMN links_check_date',
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                'ALTER TABLE %sfaqdata_revisions DROP COLUMN links_state, DROP COLUMN links_check_date',
                Database::getTablePrefix(),
            );

            // Configuration values in a TEXT column
            switch (Database::getType()) {
                case 'mysqli':
                    $this->queries[] = sprintf(
                        'ALTER TABLE %sfaqconfig MODIFY config_value TEXT DEFAULT NULL',
                        Database::getTablePrefix(),
                    );
                    break;
                case 'pgsql':
                    $this->queries[] = sprintf(
                        'ALTER TABLE %sfaqconfig ALTER COLUMN config_value TYPE TEXT',
                        Database::getTablePrefix(),
                    );
                    break;
                case 'sqlite3':
                    $this->queries[] = sprintf('CREATE TABLE %sfaqconfig_new (
                            config_name VARCHAR(255) NOT NULL default \'\',
                            config_value TEXT DEFAULT NULL, PRIMARY KEY (config_name)
                         )', Database::getTablePrefix());
                    $this->queries[] = sprintf(
                        'INSERT INTO %sfaqconfig_new SELECT config_name, config_value FROM %sfaqconfig',
                        Database::getTablePrefix(),
                        Database::getTablePrefix(),
                    );
                    $this->queries[] = sprintf('DROP TABLE %sfaqconfig', Database::getTablePrefix());
                    $this->queries[] = sprintf(
                        'ALTER TABLE %sfaqconfig_new RENAME TO %sfaqconfig',
                        Database::getTablePrefix(),
                        Database::getTablePrefix(),
                    );
                    break;
                case 'sqlsrv':
                    $this->queries[] = sprintf(
                        'ALTER TABLE %sfaqconfig ALTER COLUMN config_value TEXT',
                        Database::getTablePrefix(),
                    );
                    break;
            }
        }
    }

    private function applyUpdates320Beta2(): void
    {
        if (version_compare($this->version, '3.2.0-beta.2', '<')) {
            // HTML-support for contactInformation
            $this->configuration->add('main.contactInformationHTML', false);
            $this->configuration->rename('main.contactInformations', 'main.contactInformation');
        }
    }

    private function applyUpdates320RC(): void
    {
        if (version_compare($this->version, '3.2.0-RC', '<')) {
            // HTML-support for contactInformation
            $this->configuration->add('spam.mailAddressInExport', true);
        }
    }

    private function applyUpdates323(): void
    {
        if (version_compare($this->version, '3.2.3', '<')) {
            switch (Database::getType()) {
                case 'mysqli':
                    $this->queries[] = sprintf(
                        'ALTER TABLE %sfaquser CHANGE ip ip VARCHAR(64) NULL DEFAULT NULL',
                        Database::getTablePrefix(),
                    );
                    break;
                case 'pgsql':
                    $this->queries[] = sprintf(
                        'ALTER TABLE %sfaquser ALTER COLUMN ip TYPE VARCHAR(64)',
                        Database::getTablePrefix(),
                    );
                    break;
                case 'sqlite3':
                    $this->queries[] = sprintf(
                        'CREATE TABLE %sfaquser_new (
                        user_id INTEGER NOT NULL,
                        login VARCHAR(128) NOT NULL,
                        session_id VARCHAR(150) NULL,
                        session_timestamp INTEGER NULL,
                        ip VARCHAR(64) NULL,
                        account_status VARCHAR(50) NULL,
                        last_login VARCHAR(14) NULL,
                        auth_source VARCHAR(100) NULL,
                        member_since VARCHAR(14) NULL,
                        remember_me VARCHAR(150) NULL,
                        success INT(1) NULL DEFAULT 1,
                        is_superadmin INT(1) NULL DEFAULT 0,
                        login_attempts INT(1) NULL DEFAULT 0,
                        refresh_token TEXT NULL DEFAULT NULL,
                        access_token TEXT NULL DEFAULT NULL,
                        code_verifier VARCHAR(255) NULL DEFAULT NULL,
                        jwt TEXT NULL DEFAULT NULL,
                        PRIMARY KEY (user_id))',
                        Database::getTablePrefix(),
                    );
                    $this->queries[] = sprintf(
                        'INSERT INTO %sfaquser_new SELECT * FROM %sfaquser',
                        Database::getTablePrefix(),
                        Database::getTablePrefix(),
                    );
                    $this->queries[] = sprintf('DROP TABLE %sfaquser', Database::getTablePrefix());
                    $this->queries[] = sprintf(
                        'ALTER TABLE %sfaquser_new RENAME TO %sfaquser',
                        Database::getTablePrefix(),
                        Database::getTablePrefix(),
                    );
                    break;
                case 'sqlsrv':
                    $this->queries[] = sprintf(
                        'ALTER TABLE %sfaquser ALTER COLUMN ip VARCHAR(64)',
                        Database::getTablePrefix(),
                    );
                    break;
            }
        }
    }

    /**
     * @throws Exception
     */
    private function applyUpdates400Alpha(): void
    {
        if (version_compare($this->version, '4.0.0-alpha', '<')) {
            // First, move everything to the new file layout
            $fileSystem = new Filesystem(PMF_ROOT_DIR);

            // Copy database configuration
            $fileSystem->copy(PMF_LEGACY_CONFIG_DIR . '/database.php', PMF_CONFIG_DIR . '/database.php');

            // Copy Azure configuration, if available
            if (file_exists(PMF_LEGACY_CONFIG_DIR . '/azure.php')) {
                $fileSystem->copy(PMF_LEGACY_CONFIG_DIR . '/azure.php', PMF_CONFIG_DIR . '/azure.php');
            }

            // Copy Elasticsearch configuration, if available
            if (file_exists(PMF_LEGACY_CONFIG_DIR . '/elasticsearch.php')) {
                $fileSystem->copy(PMF_LEGACY_CONFIG_DIR . '/elasticsearch.php', PMF_CONFIG_DIR . '/elasticsearch.php');
            }

            // Copy LDAP configuration, if available
            if (file_exists(PMF_LEGACY_CONFIG_DIR . '/ldap.php')) {
                $fileSystem->copy(PMF_LEGACY_CONFIG_DIR . '/ldap.php', PMF_CONFIG_DIR . '/ldap.php');
            }

            // Copy data directory
            $fileSystem->recursiveCopy(PMF_ROOT_DIR . '/data', PMF_ROOT_DIR . '/content/core');

            // Copy logs directory
            $fileSystem->recursiveCopy(PMF_ROOT_DIR . '/logs', PMF_ROOT_DIR . '/content/core');

            // Copy attachments directory
            $fileSystem->recursiveCopy(PMF_ROOT_DIR . '/attachments', PMF_ROOT_DIR . '/content/user');

            // Copy images directory
            $fileSystem->recursiveCopy(PMF_ROOT_DIR . '/images', PMF_ROOT_DIR . '/content/user');

            // Online Update configuration
            $this->configuration->add('upgrade.onlineUpdateEnabled', true);
            $this->configuration->add('upgrade.releaseEnvironment', ReleaseType::DEVELOPMENT->value);
            $this->configuration->add('upgrade.dateLastChecked', '');
            $this->configuration->add('upgrade.lastDownloadedPackage', '');

            // Rewrite rules are now mandatory, social network support removed
            $this->configuration->delete('main.enableRewriteRules');
            $this->configuration->delete('socialnetworks.enableTwitterSupport');
            $this->configuration->delete('socialnetworks.twitterConsumerKey');
            $this->configuration->delete('socialnetworks.twitterConsumerSecret');
            $this->configuration->delete('socialnetworks.twitterAccessTokenKey');
            $this->configuration->delete('socialnetworks.twitterAccessTokenSecret');
            $this->configuration->delete('socialnetworks.disableAll');
            $this->configuration->delete('mail.remoteSMTPEncryption');

            // Bookmarks support
            $this->queries[] = match (Database::getType()) {
                'mysqli' => sprintf(
                    'CREATE TABLE %sfaqbookmarks (userid int(11) DEFAULT NULL, faqid int(11) DEFAULT NULL)',
                    Database::getTablePrefix(),
                ),
                'pgsql', 'sqlite3', 'sqlsrv' => sprintf(
                    'CREATE TABLE %sfaqbookmarks (userid INTEGER DEFAULT NULL, faqid INTEGER DEFAULT NULL)',
                    Database::getTablePrefix(),
                ),
            };

            // Custom order of sticky records
            $this->queries[] = match (Database::getType()) {
                'mysqli' => sprintf(
                    'ALTER TABLE %sfaqdata ADD COLUMN sticky_order int(10) DEFAULT NULL',
                    Database::getTablePrefix(),
                ),
                'pgsql', 'sqlite3', 'sqlsrv' => sprintf(
                    'ALTER TABLE %sfaqdata ADD COLUMN sticky_order integer DEFAULT NULL',
                    Database::getTablePrefix(),
                ),
            };

            // Custom order of sticky records
            $this->queries[] = match (Database::getType()) {
                'mysqli' => sprintf(
                    'ALTER TABLE %sfaqdata_revisions ADD COLUMN sticky_order int(10) DEFAULT NULL',
                    Database::getTablePrefix(),
                ),
                'pgsql', 'sqlite3', 'sqlsrv' => sprintf(
                    'ALTER TABLE %sfaqdata_revisions ADD COLUMN sticky_order integer DEFAULT NULL',
                    Database::getTablePrefix(),
                ),
            };
            $this->configuration->add('records.orderStickyFaqsCustom', 'false');

            // Remove template metadata tables
            $this->queries[] = sprintf('DROP TABLE %sfaqmeta', Database::getTablePrefix());

            // Blocked statistics browsers
            $this->configuration->add('main.botIgnoreList', 'nustcrape,webpost,GoogleBot,msnbot,crawler,scooter,
            bravobrian,archiver,w3c,controler,wget,bot,spider,Yahoo! Slurp,htdig,gsa-crawler,AirControler,Uptime-Kuma');

            // Enable/Disable cookie consent
            $this->configuration->add('main.enableCookieConsent', true);

            // Add parent category ID to faqcategory_order
            switch (Database::getType()) {
                case 'mysqli':
                    $this->queries[] = sprintf(
                        'ALTER TABLE %sfaqcategory_order ADD COLUMN parent_id int(11) DEFAULT NULL AFTER category_id',
                        Database::getTablePrefix(),
                    );
                    break;
                case 'sqlsrv':
                    $this->queries[] = sprintf(
                        'ALTER TABLE %sfaqcategory_order ADD COLUMN parent_id INTEGER DEFAULT NULL AFTER category_id',
                        Database::getTablePrefix(),
                    );
                    break;
                case 'sqlite3':
                case 'pgsql':
                    $this->queries[] = sprintf(
                        'CREATE TABLE %sfaqcategory_order_new (
                            category_id INTEGER NOT NULL,
                            parent_id INTEGER DEFAULT NULL,
                            position INTEGER NOT NULL,
                            PRIMARY KEY (category_id))',
                        Database::getTablePrefix(),
                    );
                    $this->queries[] = sprintf(
                        'INSERT INTO %sfaqcategory_order_new SELECT * FROM %sfaqcategory_order',
                        Database::getTablePrefix(),
                        Database::getTablePrefix(),
                    );
                    $this->queries[] = sprintf('DROP TABLE %sfaqcategory_order', Database::getTablePrefix());
                    $this->queries[] = sprintf(
                        'ALTER TABLE %sfaqcategory_order_new RENAME TO %sfaqcategory_order',
                        Database::getTablePrefix(),
                        Database::getTablePrefix(),
                    );
                    break;
            }
        }
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    private function applyUpdates400Alpha2(): void
    {
        if (version_compare($this->version, '4.0.0-alpha.2', '<')) {
            $this->configuration->delete('main.optionalMailAddress');

            // Add new permission for editing forms
            $user = new User($this->configuration);
            $rightData = [
                'name' => 'forms_edit',
                'description' => 'Right to edit forms',
            ];
            $user->perm->grantUserRight(1, $user->perm->addRight($rightData));

            switch (Database::getType()) {
                case 'mysqli':
                    $this->queries[] = sprintf(
                        'CREATE TABLE %sfaqforms (
                        form_id INT(1) NOT NULL,
                        input_id INT(11) NOT NULL,
                        input_type VARCHAR(1000) NOT NULL,
                        input_label VARCHAR(100) NOT NULL,
                        input_active INT(1) NOT NULL,
                        input_required INT(1) NOT NULL,
                        input_lang VARCHAR(11) NOT NULL)',
                        Database::getTablePrefix(),
                    );
                    break;
                case 'sqlsrv':
                    $this->queries[] = sprintf(
                        'CREATE TABLE %sfaqforms (
                        form_id INTEGER NOT NULL,
                        input_id INTEGER NOT NULL,
                        input_type NVARCHAR(1000) NOT NULL,
                        input_label NVARCHAR(100) NOT NULL,
                        input_active INTEGER NOT NULL,
                        input_required INTEGER NOT NULL,
                        input_lang NVARCHAR(11) NOT NULL)',
                        Database::getTablePrefix(),
                    );
                    break;
                case 'sqlite3':
                case 'pgsql':
                    $this->queries[] = sprintf(
                        'CREATE TABLE %sfaqforms (
                        form_id INTEGER NOT NULL,
                        input_id INTEGER NOT NULL,
                        input_type VARCHAR(1000) NOT NULL,
                        input_label VARCHAR(100) NOT NULL,
                        input_active INTEGER NOT NULL,
                        input_required INTEGER NOT NULL,
                        input_lang VARCHAR(11) NOT NULL)',
                        Database::getTablePrefix(),
                    );
                    break;
            }

            // Add function for editing forms
            $forms = new Forms($this->configuration);
            $installer = new Installer(new System());
            foreach ($installer->formInputs as $input) {
                $this->queries[] = $forms->getInsertQueries($input);
            }
        }
    }

    /**
     * @throws \Exception
     */
    private function applyUpdates400Alpha3(): void
    {
        if (version_compare($this->version, '4.0.0-alpha.3', '<')) {
            switch (Database::getType()) {
                case 'mysqli':
                    $this->queries[] = sprintf(
                        'CREATE TABLE %sfaqseo (
                            id INT(11) NOT NULL,
                            type VARCHAR(32) NOT NULL,
                            reference_id INT(11) NOT NULL,
                            reference_language VARCHAR(5) NOT NULL,
                            title TEXT DEFAULT NULL,
                            description TEXT DEFAULT NULL,
                            slug TEXT DEFAULT NULL,
                            created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            PRIMARY KEY (id)) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',
                        Database::getTablePrefix(),
                    );
                    break;
                case 'sqlsrv':
                    $this->queries[] = sprintf(
                        'CREATE TABLE %sfaqseo (
                            id INT NOT NULL,
                            type VARCHAR(32) NOT NULL,
                            reference_id INT NOT NULL,
                            reference_language VARCHAR(5) NOT NULL,
                            title TEXT NULL,
                            description TEXT NULL,
                            slug TEXT NULL,
                            created DATE NOT NULL DEFAULT GETDATE(),
                            PRIMARY KEY (id))',
                        Database::getTablePrefix(),
                    );
                    break;
                case 'sqlite3':
                    $this->queries[] = sprintf(
                        'CREATE TABLE %sfaqseo (
                            id INT NOT NULL,
                            type VARCHAR(32) NOT NULL,
                            reference_id INT NOT NULL,
                            reference_language VARCHAR(5) NOT NULL,
                            title TEXT NULL,
                            description TEXT NULL,
                            slug TEXT NULL,
                            created DATE NOT NULL DEFAULT (date(\'now\')),
                            PRIMARY KEY (id))',
                        Database::getTablePrefix(),
                    );
                    break;
                case 'pgsql':
                    $this->queries[] = sprintf(
                        'CREATE TABLE %sfaqseo (
                            id INTEGER NOT NULL,
                            type VARCHAR(32) NOT NULL,
                            reference_id INTEGER NOT NULL,
                            reference_language VARCHAR(5) NOT NULL,
                            title TEXT,
                            description TEXT,
                            slug TEXT NULL,
                            created DATE NOT NULL DEFAULT CURRENT_DATE,
                            PRIMARY KEY (id))',
                        Database::getTablePrefix(),
                    );
                    break;
            }

            // Configuration items
            $this->configuration->update(['main.botIgnoreList' => 'nustcrape,webpost,GoogleBot,msnbot,crawler,scooter,
            bravobrian,archiver,w3c,controler,wget,bot,spider,Yahoo! Slurp,htdig,gsa-crawler,AirControler,Uptime-Kuma,
            facebookcatalog/1.0,facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php),
            facebookexternalhit/1.1']);
            $this->configuration->add('mail.noReplySenderAddress', '');
            $this->configuration->add('records.allowedMediaHosts', 'www.youtube.com');
            $this->configuration->add('seo.title', $this->configuration->get(item: 'main.titleFAQ'));
            $this->configuration->add('seo.description', $this->configuration->get(item: 'main.metaDescription'));
            $this->configuration->add('layout.enablePrivacyLink', 'true');
            $this->configuration->add('layout.customCss', '');
            $this->configuration->add('seo.enableRichSnippets', 'false');
            $this->configuration->delete('main.urlValidateInterval');
            $this->configuration->delete('main.enableGzipCompression');
            $this->configuration->delete('main.metaKeywords');
            $this->configuration->delete('main.send2friendText');
            $this->configuration->rename('main.templateSet', 'layout.templateSet');
            $this->configuration->rename('main.enableCookieConsent', 'layout.enableCookieConsent');
            $this->configuration->rename('main.contactInformationHTML', 'layout.contactInformationHTML');
        }
    }

    private function applyUpdates400Beta2(): void
    {
        if (version_compare($this->version, '4.0.0-beta.2', '<')) {
            // WebAuthn support
            $this->configuration->add('security.enableWebAuthnSupport', false);

            if ('sqlite3' === Database::getType()) {
                $this->queries[] = sprintf(
                    'ALTER TABLE %sfaquser ADD COLUMN webauthnkeys TEXT NULL DEFAULT NULL;',
                    Database::getTablePrefix(),
                );
            } else {
                $this->queries[] = sprintf(
                    'ALTER TABLE %sfaquser ADD webauthnkeys TEXT NULL DEFAULT NULL;',
                    Database::getTablePrefix(),
                );
            }
        }
    }

    private function applyUpdates405(): void
    {
        if (version_compare($this->version, '4.0.5', '<')) {
            // Delete old permissions
            $this->queries[] = sprintf(
                "DELETE FROM %sfaqright WHERE name = 'view_sections'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf("DELETE FROM %sfaqright WHERE name = 'add_section'", Database::getTablePrefix());
            $this->queries[] = sprintf(
                "DELETE FROM %sfaqright WHERE name = 'edit_section'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "DELETE FROM %sfaqright WHERE name = 'delete_section'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "DELETE FROM %sfaqright WHERE name = 'delete_section'",
                Database::getTablePrefix(),
            );

            // Update faqforms table
            switch (Database::getType()) {
                case 'mysqli':
                    $this->queries[] = sprintf(
                        'ALTER TABLE %sfaqforms CHANGE input_label input_label VARCHAR(500) NOT NULL',
                        Database::getTablePrefix(),
                    );
                    break;
                case 'pgsql':
                    $this->queries[] = sprintf(
                        'ALTER TABLE %sfaqforms ALTER COLUMN input_label SET TYPE VARCHAR(500)',
                        Database::getTablePrefix(),
                    );
                    $this->queries[] = sprintf(
                        'ALTER TABLE %sfaqforms ALTER COLUMN input_label SET NOT NULL',
                        Database::getTablePrefix(),
                    );
                    break;
                case 'sqlite3':
                    $this->queries[] = sprintf(
                        'ALTER TABLE %sfaqforms RENAME TO %sfaqforms_old',
                        Database::getTablePrefix(),
                        Database::getTablePrefix(),
                    );
                    $this->queries[] = sprintf('CREATE TABLE %sfaqforms (
                            form_id INTEGER NOT NULL,
                            input_id INTEGER NOT NULL,
                            input_type VARCHAR(1000) NOT NULL,
                            input_label VARCHAR(500) NOT NULL,
                            input_active INTEGER NOT NULL,
                            input_required INTEGER NOT NULL,
                            input_lang VARCHAR(11) NOT NULL
                        )', Database::getTablePrefix());
                    $this->queries[] = sprintf(
                        'INSERT INTO %sfaqforms
                            SELECT
                                form_id, input_id, input_type, input_label, input_active, input_required, input_lang
                            FROM %sfaqforms_old',
                        Database::getTablePrefix(),
                        Database::getTablePrefix(),
                    );
                    $this->queries[] = sprintf('DROP TABLE %sfaqforms_old;', Database::getTablePrefix());
                    break;
                case 'sqlsrv':
                    $this->queries[] = sprintf(
                        'ALTER TABLE %sfaqforms ALTER COLUMN input_label NVARCHAR(500) NOT NULL',
                        Database::getTablePrefix(),
                    );
                    break;
            }
        }
    }

    private function applyUpdates407(): void
    {
        if (version_compare($this->version, '4.0.7', '<')) {
            // Update language codes for fr_CA and pt_BR
            $this->queries[] = sprintf(
                "UPDATE %sfaqattachment SET record_lang='fr_ca' WHERE record_lang='fr-ca'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqcaptcha SET language='fr_ca' WHERE language='fr-ca'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqcategories SET lang='fr_ca' WHERE lang='fr-ca'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqdata SET lang='fr_ca' WHERE lang='fr-ca'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqcategoryrelations SET category_lang='fr_ca' WHERE category_lang='fr-ca'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqcategoryrelations SET record_lang='fr_ca' WHERE record_lang='fr-ca'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqchanges SET lang='fr_ca' WHERE lang='fr-ca'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqdata_revisions SET lang='fr_ca' WHERE lang='fr-ca'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqglossary SET lang='fr_ca' WHERE lang='fr-ca'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqnews SET lang='fr_ca' WHERE lang='fr-ca'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqquestions SET lang='fr_ca' WHERE lang='fr-ca'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqsearches SET lang='fr_ca' WHERE lang='fr-ca'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqvisits SET lang='fr_ca' WHERE lang='fr-ca'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqconfig SET config_value='language_fr_ca.php' WHERE config_value='language_fr-ca.php'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqattachment SET record_lang='pt_br' WHERE record_lang='pt-br'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqcaptcha SET language='pt_br' WHERE language='pt-br'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqcategories SET lang='pt_br' WHERE lang='pt-br'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqdata SET lang='pt_br' WHERE lang='pt-br'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqcategoryrelations SET category_lang='pt_br' WHERE category_lang='pt-br'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqcategoryrelations SET record_lang='pt_br' WHERE record_lang='pt-br'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqchanges SET lang='pt_br' WHERE lang='pt-br'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqdata_revisions SET lang='pt_br' WHERE lang='pt-br'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqglossary SET lang='pt_br' WHERE lang='pt-br'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqnews SET lang='pt_br' WHERE lang='pt-br'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqquestions SET lang='pt_br' WHERE lang='pt-br'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqsearches SET lang='pt_br' WHERE lang='pt-br'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqvisits SET lang='pt_br' WHERE lang='pt-br'",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "UPDATE %sfaqconfig SET config_value='language_pt_br.php' WHERE config_value='language_pt-br.php'",
                Database::getTablePrefix(),
            );
        }
    }

    private function applyUpdates409(): void
    {
        if (version_compare($this->version, '4.0.9', '<') && Database::getType() === 'pgsql') {
            $this->queries[] = sprintf('CREATE SEQUENCE %sfaqseo_id_seq', Database::getTablePrefix());
            $this->queries[] = sprintf(
                "ALTER TABLE %sfaqseo ALTER COLUMN id SET DEFAULT nextval('faqseo_id_seq')",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf(
                "SELECT setval('faqseo_id_seq', (SELECT MAX(id) FROM %sfaqseo));",
                Database::getTablePrefix(),
            );
            $this->queries[] = sprintf('ALTER TABLE %sfaqseo ALTER COLUMN id SET NOT NULL', Database::getTablePrefix());
        }
    }

    private function applyUpdates410Alpha(): void
    {
        if (version_compare($this->version, '4.1.0-alpha', '<')) {
            $text = <<<EOT
                User-agent: Amazonbot
                User-agent: anthropic-ai
                User-agent: Applebot-Extended
                User-agent: Bytespider
                User-agent: CCBot
                User-agent: ChatGPT-User
                User-agent: ClaudeBot
                User-agent: Claude-Web
                User-agent: cohere-ai
                User-agent: Diffbot
                User-agent: FacebookBot
                User-agent: facebookexternalhit
                User-agent: FriendlyCrawler
                User-agent: Google-Extended
                User-agent: GoogleOther
                User-agent: GoogleOther-Image
                User-agent: GoogleOther-Video
                User-agent: GPTBot
                User-agent: ICC-Crawler
                User-agent: ImagesiftBot
                User-agent: img2dataset
                User-agent: Meta-ExternalAgent
                User-agent: OAI-SearchBot
                User-agent: omgili
                User-agent: omgilibot
                User-agent: PerplexityBot
                User-agent: PetalBot
                User-agent: Scrapy
                User-agent: Timpibot
                User-agent: VelenPublicWebCrawler
                User-agent: YouBot
                User-agent: Meta-ExternalFetcher
                User-agent: Applebot
                Disallow: /

                User-agent: *
                Disallow: /admin/

                Sitemap: /sitemap.xml
                EOT;
            $this->configuration->add('seo.contentRobotsText', $text);
        }
    }

    /**
     * @throws Exception
     */
    private function applyUpdates410Alpha2(): void
    {
        if (version_compare($this->version, '4.1.0-alpha.2', '<')) {
            $this->configuration->add('security.enableAdminSessionTimeoutCounter', true);
            $this->configuration->add('search.enableOpenSearch', false);

            // Add new permission to translate FAQs
            $user = new User($this->configuration);
            $rightData = [
                'name' => PermissionType::FAQ_TRANSLATE->value,
                'description' => 'Right to translate FAQs',
            ];
            $user->perm->grantUserRight(1, $user->perm->addRight($rightData));
        }
    }

    private function applyUpdates410Alpha3(): void
    {
        if (version_compare($this->version, '4.1.0-alpha.3', '<')) {
            $llmsText =
                "# phpMyFAQ LLMs.txt\n\n"
                . "This file provides information about the AI/LLM training data availability for this FAQ system.\n\n"
                . "Contact: Please see the contact information on the main website.\n\n"
                . "The FAQ content in this system is available for LLM training purposes.\n"
                . "Please respect the licensing terms and usage guidelines of the content.\n\n"
                . 'For more information about this FAQ system, visit: https://www.phpmyfaq.de';

            $this->configuration->add('seo.contentLlmsText', $llmsText);

            // LDAP group integration
            $this->configuration->add('ldap.ldap_use_group_restriction', 'false');
            $this->configuration->add('ldap.ldap_group_allowed_groups', '');
            $this->configuration->add('ldap.ldap_group_auto_assign', 'false');
            $this->configuration->add('ldap.ldap_group_mapping', '');

            // Search optimization configuration
            $this->configuration->add('search.popularSearchTimeWindow', '180');

            // Performance indexes for faqsearches table (conditional creation)
            $dbType = Database::getType();
            $tablePrefix = Database::getTablePrefix();

            switch ($dbType) {
                case 'sqlsrv':
                case 'pdo_sqlsrv':
                    // SQL Server: Check if the index exists before creating
                    $this->queries[] =
                        'IF NOT EXISTS (SELECT * FROM sys.indexes '
                        . "WHERE name = 'idx_faqsearches_searchterm') "
                        . sprintf('CREATE INDEX idx_faqsearches_searchterm ON %sfaqsearches ', $tablePrefix)
                        . '(searchterm)';
                    $this->queries[] =
                        'IF NOT EXISTS (SELECT * FROM sys.indexes '
                        . "WHERE name = 'idx_faqsearches_date_term') "
                        . sprintf(
                            'CREATE INDEX idx_faqsearches_date_term ON %sfaqsearches (searchdate, searchterm)',
                            $tablePrefix,
                        );
                    $this->queries[] =
                        'IF NOT EXISTS (SELECT * FROM sys.indexes '
                        . "WHERE name = 'idx_faqsearches_date_term_lang') "
                        . sprintf('CREATE INDEX idx_faqsearches_date_term_lang ON %sfaqsearches ', $tablePrefix)
                        . '(searchdate, searchterm, lang)';
                    break;
                default:
                    // MySQL, PostgreSQL, SQLite: Use IF NOT EXISTS
                    $this->queries[] = sprintf(
                        'CREATE INDEX IF NOT EXISTS idx_faqsearches_searchterm ON %sfaqsearches (searchterm)',
                        $tablePrefix,
                    );
                    $this->queries[] = sprintf(
                        'CREATE INDEX IF NOT EXISTS idx_faqsearches_date_term ON %sfaqsearches '
                        . '(searchdate, searchterm)',
                        $tablePrefix,
                    );
                    $this->queries[] = sprintf(
                        'CREATE INDEX IF NOT EXISTS idx_faqsearches_date_term_lang ON %sfaqsearches '
                        . '(searchdate, searchterm, lang)',
                        $tablePrefix,
                    );
                    break;
            }
        }
    }

    private function applyUpdates420Alpha(): void
    {
        if (version_compare($this->version, '4.2.0-alpha', '<')) {
            // Create the new columns first
            $alterQueries = match (Database::getType()) {
                'mysqli', 'pdo_mysql' => [
                    sprintf(
                        'ALTER TABLE %sfaqadminlog ADD COLUMN hash VARCHAR(64) AFTER text',
                        Database::getTablePrefix(),
                    ),
                    sprintf(
                        'ALTER TABLE %sfaqadminlog ADD COLUMN previous_hash VARCHAR(64) AFTER hash',
                        Database::getTablePrefix(),
                    ),
                    sprintf('CREATE INDEX idx_hash ON %sfaqadminlog (hash)', Database::getTablePrefix()),
                ],
                'pgsql', 'pdo_pgsql', 'sqlite3', 'pdo_sqlite' => [
                    sprintf('ALTER TABLE %sfaqadminlog ADD COLUMN hash VARCHAR(64)', Database::getTablePrefix()),
                    sprintf(
                        'ALTER TABLE %sfaqadminlog ADD COLUMN previous_hash VARCHAR(64)',
                        Database::getTablePrefix(),
                    ),
                    sprintf('CREATE INDEX idx_hash ON %sfaqadminlog (hash)', Database::getTablePrefix()),
                ],
                'sqlsrv', 'pdo_sqlsrv' => [
                    sprintf('ALTER TABLE %sfaqadminlog ADD hash VARCHAR(64)', Database::getTablePrefix()),
                    sprintf('ALTER TABLE %sfaqadminlog ADD previous_hash VARCHAR(64)', Database::getTablePrefix()),
                    sprintf('CREATE INDEX idx_hash ON %sfaqadminlog (hash)', Database::getTablePrefix()),
                ],
                default => [],
            };

            // Execute ALTER TABLE queries immediately
            foreach ($alterQueries as $query) {
                $this->configuration->getDb()->query($query);
            }

            // Now migrate the existing data
            $this->migrateAdminLogHashes();

            // Create custom pages table
            switch (Database::getType()) {
                case 'mysqli':
                case 'pdo_mysql':
                    $this->queries[] = sprintf(
                        'CREATE TABLE IF NOT EXISTS %sfaqcustompages (
                            id INT(11) NOT NULL,
                            lang VARCHAR(5) NOT NULL,
                            page_title VARCHAR(255) NOT NULL,
                            slug VARCHAR(255) NOT NULL,
                            content TEXT NOT NULL,
                            author_name VARCHAR(255) NOT NULL,
                            author_email VARCHAR(255) NOT NULL,
                            active CHAR(1) NOT NULL DEFAULT \'n\',
                            created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            updated TIMESTAMP NULL,
                            seo_title VARCHAR(60) NULL,
                            seo_description VARCHAR(160) NULL,
                            seo_robots VARCHAR(50) NOT NULL DEFAULT \'index,follow\',
                            PRIMARY KEY (id, lang),
                            INDEX idx_custompages_slug (slug, lang)
                        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',
                        Database::getTablePrefix(),
                    );
                    break;

                case 'pgsql':
                case 'pdo_pgsql':
                    $this->queries[] = sprintf('CREATE TABLE IF NOT EXISTS %sfaqcustompages (
                            id INTEGER NOT NULL,
                            lang VARCHAR(5) NOT NULL,
                            page_title VARCHAR(255) NOT NULL,
                            slug VARCHAR(255) NOT NULL,
                            content TEXT NOT NULL,
                            author_name VARCHAR(255) NOT NULL,
                            author_email VARCHAR(255) NOT NULL,
                            active CHAR(1) NOT NULL DEFAULT \'n\',
                            created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            updated TIMESTAMP NULL,
                            seo_title VARCHAR(60) NULL,
                            seo_description VARCHAR(160) NULL,
                            seo_robots VARCHAR(50) NOT NULL DEFAULT \'index,follow\',
                            PRIMARY KEY (id, lang)
                        )', Database::getTablePrefix());
                    $this->queries[] = sprintf(
                        'CREATE INDEX IF NOT EXISTS idx_custompages_slug ON %sfaqcustompages (slug, lang)',
                        Database::getTablePrefix(),
                    );
                    break;

                case 'sqlite3':
                case 'pdo_sqlite':
                    $this->queries[] = sprintf('CREATE TABLE IF NOT EXISTS %sfaqcustompages (
                            id INTEGER NOT NULL,
                            lang VARCHAR(5) NOT NULL,
                            page_title VARCHAR(255) NOT NULL,
                            slug VARCHAR(255) NOT NULL,
                            content TEXT NOT NULL,
                            author_name VARCHAR(255) NOT NULL,
                            author_email VARCHAR(255) NOT NULL,
                            active CHAR(1) NOT NULL DEFAULT \'n\',
                            created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            updated TIMESTAMP NULL,
                            seo_title VARCHAR(60) NULL,
                            seo_description VARCHAR(160) NULL,
                            seo_robots VARCHAR(50) NOT NULL DEFAULT \'index,follow\',
                            PRIMARY KEY (id, lang)
                        )', Database::getTablePrefix());
                    $this->queries[] = sprintf(
                        'CREATE INDEX IF NOT EXISTS idx_custompages_slug ON %sfaqcustompages (slug, lang)',
                        Database::getTablePrefix(),
                    );
                    break;

                case 'sqlsrv':
                case 'pdo_sqlsrv':
                    $this->queries[] = sprintf('CREATE TABLE %sfaqcustompages (
                            id INT NOT NULL,
                            lang VARCHAR(5) NOT NULL,
                            page_title VARCHAR(255) NOT NULL,
                            slug VARCHAR(255) NOT NULL,
                            content NVARCHAR(MAX) NOT NULL,
                            author_name VARCHAR(255) NOT NULL,
                            author_email VARCHAR(255) NOT NULL,
                            active CHAR(1) NOT NULL DEFAULT \'n\',
                            created DATETIME NOT NULL DEFAULT GETDATE(),
                            updated DATETIME NULL,
                            seo_title VARCHAR(60) NULL,
                            seo_description VARCHAR(160) NULL,
                            seo_robots VARCHAR(50) NOT NULL DEFAULT \'index,follow\',
                            PRIMARY KEY (id, lang)
                        )', Database::getTablePrefix());
                    $this->queries[] = sprintf(
                        'CREATE INDEX idx_custompages_slug ON %sfaqcustompages (slug, lang)',
                        Database::getTablePrefix(),
                    );
                    break;
            }

            // Add new permissions for custom pages
            $user = new User($this->configuration);
            $pageAddRight = [
                'name' => PermissionType::PAGE_ADD->value,
                'description' => 'Right to add custom pages',
            ];
            $pageEditRight = [
                'name' => PermissionType::PAGE_EDIT->value,
                'description' => 'Right to edit custom pages',
            ];
            $pageDeleteRight = [
                'name' => PermissionType::PAGE_DELETE->value,
                'description' => 'Right to delete custom pages',
            ];
            $user->perm->grantUserRight(1, $user->perm->addRight($pageAddRight));
            $user->perm->grantUserRight(1, $user->perm->addRight($pageEditRight));
            $user->perm->grantUserRight(1, $user->perm->addRight($pageDeleteRight));

            // Add configuration entries for legal page URLs if they don't exist
            $this->configuration->add('main.termsURL', '');
            $this->configuration->add('main.imprintURL', '');
            $this->configuration->add('main.cookiePolicyURL', '');
            $this->configuration->add('main.accessibilityStatementURL', '');
            $this->configuration->add('api.onlyActiveFaqs', 'true');
            $this->configuration->add('api.onlyActiveCategories', 'true');
            $this->configuration->add('api.onlyPublicQuestions', 'true');
            $this->configuration->add('api.ignoreOrphanedFaqs', 'true');

            // Add translation service configuration
            $this->configuration->add('translation.provider', 'none');
            $this->configuration->add('translation.googleApiKey', '');
            $this->configuration->add('translation.deeplApiKey', '');
            $this->configuration->add('translation.deeplUseFreeApi', 'true');
            $this->configuration->add('translation.azureKey', '');
            $this->configuration->add('translation.azureRegion', '');
            $this->configuration->add('translation.amazonAccessKeyId', '');
            $this->configuration->add('translation.amazonSecretAccessKey', '');
            $this->configuration->add('translation.amazonRegion', 'us-east-1');
            $this->configuration->add('translation.libreTranslateUrl', 'https://libretranslate.com');
            $this->configuration->add('translation.libreTranslateApiKey', '');

            $this->configuration->add('main.enableCommentEditor', 'false');

            // Create a chat messages table for private user-to-user messaging
            switch (Database::getType()) {
                case 'mysqli':
                case 'pdo_mysql':
                    $this->queries[] = sprintf(
                        'CREATE TABLE IF NOT EXISTS %sfaqchat_messages (
                            id INT(11) NOT NULL AUTO_INCREMENT,
                            sender_id INT(11) NOT NULL,
                            recipient_id INT(11) NOT NULL,
                            message TEXT NOT NULL,
                            is_read TINYINT(1) NOT NULL DEFAULT 0,
                            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            PRIMARY KEY (id),
                            INDEX idx_chat_sender (sender_id),
                            INDEX idx_chat_recipient (recipient_id),
                            INDEX idx_chat_conversation (sender_id, recipient_id),
                            INDEX idx_chat_created (created_at)
                        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',
                        Database::getTablePrefix(),
                    );
                    break;

                case 'pgsql':
                case 'pdo_pgsql':
                    $this->queries[] = sprintf('CREATE TABLE IF NOT EXISTS %sfaqchat_messages (
                            id SERIAL NOT NULL,
                            sender_id INTEGER NOT NULL,
                            recipient_id INTEGER NOT NULL,
                            message TEXT NOT NULL,
                            is_read SMALLINT NOT NULL DEFAULT 0,
                            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            PRIMARY KEY (id)
                        )', Database::getTablePrefix());
                    $this->queries[] = sprintf(
                        'CREATE INDEX IF NOT EXISTS idx_chat_sender ON %sfaqchat_messages (sender_id)',
                        Database::getTablePrefix(),
                    );
                    $this->queries[] = sprintf(
                        'CREATE INDEX IF NOT EXISTS idx_chat_recipient ON %sfaqchat_messages (recipient_id)',
                        Database::getTablePrefix(),
                    );
                    $this->queries[] = sprintf(
                        'CREATE INDEX IF NOT EXISTS idx_chat_conversation ON %sfaqchat_messages (sender_id, recipient_id)',
                        Database::getTablePrefix(),
                    );
                    $this->queries[] = sprintf(
                        'CREATE INDEX IF NOT EXISTS idx_chat_created ON %sfaqchat_messages (created_at)',
                        Database::getTablePrefix(),
                    );
                    break;

                case 'sqlite3':
                case 'pdo_sqlite':
                    $this->queries[] = sprintf('CREATE TABLE IF NOT EXISTS %sfaqchat_messages (
                            id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                            sender_id INTEGER NOT NULL,
                            recipient_id INTEGER NOT NULL,
                            message TEXT NOT NULL,
                            is_read INTEGER NOT NULL DEFAULT 0,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        )', Database::getTablePrefix());
                    $this->queries[] = sprintf(
                        'CREATE INDEX IF NOT EXISTS idx_chat_sender ON %sfaqchat_messages (sender_id)',
                        Database::getTablePrefix(),
                    );
                    $this->queries[] = sprintf(
                        'CREATE INDEX IF NOT EXISTS idx_chat_recipient ON %sfaqchat_messages (recipient_id)',
                        Database::getTablePrefix(),
                    );
                    $this->queries[] = sprintf(
                        'CREATE INDEX IF NOT EXISTS idx_chat_conversation ON %sfaqchat_messages (sender_id, recipient_id)',
                        Database::getTablePrefix(),
                    );
                    $this->queries[] = sprintf(
                        'CREATE INDEX IF NOT EXISTS idx_chat_created ON %sfaqchat_messages (created_at)',
                        Database::getTablePrefix(),
                    );
                    break;

                case 'sqlsrv':
                case 'pdo_sqlsrv':
                    $this->queries[] = sprintf('CREATE TABLE %sfaqchat_messages (
                            id INT IDENTITY(1,1) NOT NULL,
                            sender_id INT NOT NULL,
                            recipient_id INT NOT NULL,
                            message NVARCHAR(MAX) NOT NULL,
                            is_read TINYINT NOT NULL DEFAULT 0,
                            created_at DATETIME NOT NULL DEFAULT GETDATE(),
                            PRIMARY KEY (id)
                        )', Database::getTablePrefix());
                    $this->queries[] = sprintf(
                        'CREATE INDEX idx_chat_sender ON %sfaqchat_messages (sender_id)',
                        Database::getTablePrefix(),
                    );
                    $this->queries[] = sprintf(
                        'CREATE INDEX idx_chat_recipient ON %sfaqchat_messages (recipient_id)',
                        Database::getTablePrefix(),
                    );
                    $this->queries[] = sprintf(
                        'CREATE INDEX idx_chat_conversation ON %sfaqchat_messages (sender_id, recipient_id)',
                        Database::getTablePrefix(),
                    );
                    $this->queries[] = sprintf(
                        'CREATE INDEX idx_chat_created ON %sfaqchat_messages (created_at)',
                        Database::getTablePrefix(),
                    );
                    break;
            }
        }
    }

    private function updateVersion(): void
    {
        $this->configuration->update(['main.currentApiVersion' => System::getApiVersion()]);
        $this->configuration->update(['main.currentVersion' => System::getVersion()]);
    }

    /**
     * @throws RandomException
     */
    private function getBackupFilename(): string
    {
        if ($this->backupFilename === null) {
            $randomHash = bin2hex(random_bytes(4)); // 8-character hex string
            $this->backupFilename = sprintf('phpmyfaq-config-backup.%s.%s.zip', date(format: 'Y-m-d'), $randomHash);
        }

        return $this->backupFilename;
    }

    private function migrateAdminLogHashes(): void
    {
        if (version_compare($this->version, '4.2.0-alpha', '<')) {
            $repository = new AdminLogRepository($this->configuration);

            try {
                $entries = $repository->getAll();
                $previousHash = null;

                foreach ($entries as $entity) {
                    if ($entity->getHash() === null) {
                        $entity->setPreviousHash($previousHash);
                        $hash = $entity->calculateHash();

                        // Execute UPDATE directly instead of adding to the queries array
                        $updateQuery = sprintf(
                            "UPDATE %sfaqadminlog SET hash = '%s', previous_hash = %s WHERE id = %d",
                            Database::getTablePrefix(),
                            $this->configuration->getDb()->escape($hash),
                            $previousHash !== null
                                ? "'" . $this->configuration->getDb()->escape($previousHash) . "'"
                                : 'NULL',
                            $entity->getId(),
                        );

                        $this->configuration->getDb()->query($updateQuery);

                        $previousHash = $hash;
                    }
                }
            } catch (\Exception $e) {
                $this->configuration->getLogger()->error('Admin log hash migration failed: ' . $e->getMessage());
            }
        }
    }
}
