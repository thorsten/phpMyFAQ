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
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-04-03
 */

namespace phpMyFAQ\Setup;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Enums\ReleaseType;
use phpMyFAQ\Filesystem;
use phpMyFAQ\Forms;
use phpMyFAQ\Setup;
use phpMyFAQ\System;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class Update extends Setup
{
    private string $version;

    /** @var string[] */
    private array $queries = [];

    private bool $dryRun = false;

    public function __construct(protected System $system, private readonly Configuration $configuration)
    {
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
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $file = realpath($file);
            if (str_contains($file, $configDir . DIRECTORY_SEPARATOR)) {
                if (is_dir($file)) {
                    $zipArchive->addEmptyDir(
                        str_replace($configDir . DIRECTORY_SEPARATOR, '', $file . DIRECTORY_SEPARATOR)
                    );
                } elseif (is_file($file)) {
                    $zipArchive->addFile($file, str_replace($configDir . DIRECTORY_SEPARATOR, '', $file));
                }
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
    public function applyUpdates(callable $progressCallback): bool
    {
        // 3.1 updates
        $this->applyUpdates310Alpha();
        $this->applyUpdates310Alpha3();
        $this->applyUpdates310Beta();
        $this->applyUpdates310RC();

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

        // Optimize the tables
        $this->optimizeTables();

        // Execute queries
        $this->executeQueries($progressCallback);

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

    /**
     * @throws Exception
     */
    private function executeQueries(callable $progressCallback): void
    {
        if ($this->dryRun) {
            foreach ($this->queries as $query) {
                echo $query . PHP_EOL;
            }
        } else {
            foreach ($this->queries as $query) {
                try {
                    $this->configuration->getDb()->query($query);
                    $progressCallback($query);
                } catch (Exception $exception) {
                    throw new Exception($exception->getMessage());
                }
            }
        }
    }

    private function applyUpdates310Alpha(): void
    {
        if (version_compare($this->version, '3.1.0-alpha', '<')) {
            // Add is_visible flag for user data
            if ('sqlite3' === Database::getType()) {
                $this->queries[] = sprintf(
                    'ALTER TABLE %sfaquserdata ADD COLUMN is_visible INT(1) DEFAULT 0',
                    Database::getTablePrefix()
                );
            } else {
                $this->queries[] = sprintf(
                    'ALTER TABLE %sfaquserdata ADD is_visible INTEGER DEFAULT 0',
                    Database::getTablePrefix()
                );
            }

            // Remove RSS support
            $this->configuration->delete('main.enableRssFeeds');

            // Add API-related configuration
            $this->configuration->add('api.enableAccess', true);
            $this->configuration->add('api.apiClientToken', '');

            // Add passlist for domains
            $this->configuration->add('security.domainWhiteListForRegistrations', '');
        }
    }

    private function applyUpdates310Alpha3(): void
    {
        if (version_compare($this->version, '3.1.0-alpha.3', '<')) {
            // Add "Login with email address" configuration
            $this->configuration->add('main.loginWithEmailAddress', false);
        }
    }

    private function applyUpdates310Beta(): void
    {
        if (version_compare($this->version, '3.1.0-beta', '<')) {
            $this->queries[] = match (Database::getType()) {
                'mysqli' => sprintf(
                    'CREATE TABLE %sfaqcategory_order 
                    (category_id int(11) NOT NULL, position int(11) NOT NULL, PRIMARY KEY (category_id))',
                    Database::getTablePrefix()
                ),
                'pgsql', 'sqlite3', 'sqlsrv' => sprintf(
                    'CREATE TABLE %sfaqcategory_order 
                    (category_id INTEGER NOT NULL, position INTEGER NOT NULL, PRIMARY KEY (category_id))',
                    Database::getTablePrefix()
                ),
            };
        }
    }

    private function applyUpdates310RC(): void
    {
        if (version_compare($this->version, '3.1.0-RC', '<')) {
            $this->configuration->delete('records.autosaveActive');
            $this->configuration->delete('records.autosaveSecs');
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
                    Database::getTablePrefix()
                );

                $this->queries[] = sprintf(
                    'ALTER TABLE %sfaquserdata
                        ADD COLUMN twofactor_enabled INT(1) NULL DEFAULT 0,
                        ADD COLUMN secret VARCHAR(128) NULL DEFAULT NULL',
                    Database::getTablePrefix()
                );
            } else {
                $this->queries[] = sprintf(
                    'ALTER TABLE %sfaquser 
                        ADD refresh_token TEXT NULL DEFAULT NULL,
                        ADD access_token TEXT NULL DEFAULT NULL,
                        ADD code_verifier VARCHAR(255) NULL DEFAULT NULL,
                        ADD jwt TEXT NULL DEFAULT NULL;',
                    Database::getTablePrefix()
                );

                $this->queries[] = sprintf(
                    'ALTER TABLE %sfaquserdata
                        ADD twofactor_enabled INT NULL DEFAULT 0,
                        ADD secret VARCHAR(128) NULL DEFAULT NULL',
                    Database::getTablePrefix()
                );
            }

            // New backup
            $this->queries[] = sprintf(
                'CREATE TABLE %sfaqbackup (
                    id INT NOT NULL,
                    filename VARCHAR(255) NOT NULL,
                    authkey VARCHAR(255) NOT NULL,
                    authcode VARCHAR(255) NOT NULL,
                    created timestamp NOT NULL,
                    PRIMARY KEY (id))',
                Database::getTablePrefix()
            );

            // Migrate MySQL from MyISAM to InnoDB
            if ('mysqli' === Database::getType()) {
                $this->queries[] = sprintf('ALTER TABLE %sfaqdata ENGINE=INNODB', Database::getTablePrefix());
            }

            // new options
            $this->configuration->add('main.enableAskQuestions', true);
            $this->configuration->add('main.enableNotifications', true);

            // update options
            $this->configuration->rename('security.loginWithEmailAddress', 'security.loginWithEmailAddress');
            if ($this->configuration->get('security.permLevel') === 'large') {
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
                Database::getTablePrefix()
            );
            $this->queries[] = sprintf(
                'ALTER TABLE %sfaqdata_revisions DROP COLUMN links_state, DROP COLUMN links_check_date',
                Database::getTablePrefix()
            );

            // Configuration values in a TEXT column
            switch (Database::getType()) {
                case 'mysqli':
                    $this->queries[] = sprintf(
                        'ALTER TABLE %sfaqconfig MODIFY config_value TEXT DEFAULT NULL',
                        Database::getTablePrefix()
                    );
                    break;
                case 'pgsql':
                    $this->queries[] = sprintf(
                        'ALTER TABLE %sfaqconfig ALTER COLUMN config_value TYPE TEXT',
                        Database::getTablePrefix()
                    );
                    break;
                case 'sqlite3':
                    $this->queries[] = sprintf(
                        'CREATE TABLE %sfaqconfig_new (
                            config_name VARCHAR(255) NOT NULL default \'\', 
                            config_value TEXT DEFAULT NULL, PRIMARY KEY (config_name)
                         )',
                        Database::getTablePrefix()
                    );
                    $this->queries[] = sprintf(
                        'INSERT INTO %sfaqconfig_new SELECT config_name, config_value FROM %sfaqconfig',
                        Database::getTablePrefix(),
                        Database::getTablePrefix()
                    );
                    $this->queries[] = sprintf(
                        'DROP TABLE %sfaqconfig',
                        Database::getTablePrefix()
                    );
                    $this->queries[] = sprintf(
                        'ALTER TABLE %sfaqconfig_new RENAME TO %sfaqconfig',
                        Database::getTablePrefix(),
                        Database::getTablePrefix()
                    );
                    break;
                case 'sqlsrv':
                    $this->queries[] = sprintf(
                        'ALTER TABLE %sfaqconfig ALTER COLUMN config_value TEXT',
                        Database::getTablePrefix()
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
                        Database::getTablePrefix()
                    );
                    break;
                case 'pgsql':
                    $this->queries[] = sprintf(
                        'ALTER TABLE %sfaquser ALTER COLUMN ip TYPE VARCHAR(64)',
                        Database::getTablePrefix()
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
                        Database::getTablePrefix()
                    );
                    $this->queries[] = sprintf(
                        'INSERT INTO %sfaquser_new SELECT * FROM %sfaquser',
                        Database::getTablePrefix(),
                        Database::getTablePrefix(),
                    );
                    $this->queries[] = sprintf(
                        'DROP TABLE %sfaquser',
                        Database::getTablePrefix()
                    );
                    $this->queries[] = sprintf(
                        'ALTER TABLE %sfaquser_new RENAME TO %sfaquser',
                        Database::getTablePrefix(),
                        Database::getTablePrefix(),
                    );
                    break;
                case 'sqlsrv':
                    $this->queries[] = sprintf(
                        'ALTER TABLE %sfaquser ALTER COLUMN ip VARCHAR(64)',
                        Database::getTablePrefix()
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
            $fileSystem->copy(
                PMF_LEGACY_CONFIG_DIR . '/database.php',
                PMF_CONFIG_DIR . '/database.php'
            );

            // Copy Azure configuration, if available
            if (file_exists(PMF_LEGACY_CONFIG_DIR . '/azure.php')) {
                $fileSystem->copy(
                    PMF_LEGACY_CONFIG_DIR . '/azure.php',
                    PMF_CONFIG_DIR . '/azure.php'
                );
            }

            // Copy Elasticsearch configuration, if available
            if (file_exists(PMF_LEGACY_CONFIG_DIR . '/elasticsearch.php')) {
                $fileSystem->copy(
                    PMF_LEGACY_CONFIG_DIR . '/elasticsearch.php',
                    PMF_CONFIG_DIR . '/elasticsearch.php'
                );
            }

            // Copy LDAP configuration, if available
            if (file_exists(PMF_LEGACY_CONFIG_DIR . '/ldap.php')) {
                $fileSystem->copy(
                    PMF_LEGACY_CONFIG_DIR . '/ldap.php',
                    PMF_CONFIG_DIR . '/ldap.php'
                );
            }

            // Copy data directory
            $fileSystem->recursiveCopy(
                PMF_ROOT_DIR . '/data',
                PMF_ROOT_DIR . '/content/core'
            );

            // Copy logs directory
            $fileSystem->recursiveCopy(
                PMF_ROOT_DIR . '/logs',
                PMF_ROOT_DIR . '/content/core'
            );

            // Copy attachments directory
            $fileSystem->recursiveCopy(
                PMF_ROOT_DIR . '/attachments',
                PMF_ROOT_DIR . '/content/user'
            );

            // Copy images directory
            $fileSystem->recursiveCopy(
                PMF_ROOT_DIR . '/images',
                PMF_ROOT_DIR . '/content/user'
            );

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
                    Database::getTablePrefix()
                ),
                'pgsql', 'sqlite3', 'sqlsrv' => sprintf(
                    'CREATE TABLE %sfaqbookmarks (userid INTEGER DEFAULT NULL, faqid INTEGER DEFAULT NULL)',
                    Database::getTablePrefix()
                ),
            };

            // Custom order of sticky records
            $this->queries[] = match (Database::getType()) {
                'mysqli' => sprintf(
                    'ALTER TABLE %sfaqdata ADD COLUMN sticky_order int(10) DEFAULT NULL',
                    Database::getTablePrefix()
                ),
                'pgsql', 'sqlite3', 'sqlsrv' => sprintf(
                    'ALTER TABLE %sfaqdata ADD COLUMN sticky_order integer DEFAULT NULL',
                    Database::getTablePrefix()
                ),
            };

            // Custom order of sticky records
            $this->queries[] = match (Database::getType()) {
                'mysqli' => sprintf(
                    'ALTER TABLE %sfaqdata_revisions ADD COLUMN sticky_order int(10) DEFAULT NULL',
                    Database::getTablePrefix()
                ),
                'pgsql', 'sqlite3', 'sqlsrv' => sprintf(
                    'ALTER TABLE %sfaqdata_revisions ADD COLUMN sticky_order integer DEFAULT NULL',
                    Database::getTablePrefix()
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
                        Database::getTablePrefix()
                    );
                    break;
                case 'sqlsrv':
                    $this->queries[] = sprintf(
                        'ALTER TABLE %sfaqcategory_order ADD COLUMN parent_id INTEGER DEFAULT NULL AFTER category_id',
                        Database::getTablePrefix()
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
                        Database::getTablePrefix()
                    );
                    $this->queries[] = sprintf(
                        'INSERT INTO %sfaqcategory_order_new SELECT * FROM %sfaqcategory_order',
                        Database::getTablePrefix(),
                        Database::getTablePrefix()
                    );
                    $this->queries[] = sprintf(
                        'DROP TABLE %sfaqcategory_order',
                        Database::getTablePrefix()
                    );
                    $this->queries[] = sprintf(
                        'ALTER TABLE %sfaqcategory_order_new RENAME TO %sfaqcategory_order',
                        Database::getTablePrefix(),
                        Database::getTablePrefix()
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
                'description' => 'Right to edit forms'
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
                        Database::getTablePrefix()
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
                        Database::getTablePrefix()
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
                        Database::getTablePrefix()
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
                            created DATE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            PRIMARY KEY (id)) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',
                        Database::getTablePrefix()
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
                        Database::getTablePrefix()
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
                        Database::getTablePrefix()
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
                        Database::getTablePrefix()
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
            $this->configuration->add('seo.title', $this->configuration->get('main.titleFAQ'));
            $this->configuration->add('seo.description', $this->configuration->get('main.metaDescription'));
            $this->configuration->add('main.enablePrivacyLink', 'true');
            $this->configuration->add('seo.glossary.title', '');
            $this->configuration->add('seo.glossary.description', '');
            $this->configuration->delete('main.urlValidateInterval');
        }
    }

    private function updateVersion(): void
    {
        $this->configuration->update(['main.currentApiVersion' => System::getApiVersion()]);
        $this->configuration->update(['main.currentVersion' => System::getVersion()]);
    }

    private function getBackupFilename(): string
    {
        return sprintf('phpmyfaq-config-backup.%s.zip', date('Y-m-d'));
    }
}
