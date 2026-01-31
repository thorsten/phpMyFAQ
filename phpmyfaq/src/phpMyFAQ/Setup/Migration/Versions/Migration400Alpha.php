<?php

/**
 * Migration for phpMyFAQ 4.0.0-alpha.
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
 * @since     2026-01-25
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Enums\ReleaseType;
use phpMyFAQ\Setup\Migration\AbstractMigration;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;

readonly class Migration400Alpha extends AbstractMigration
{
    public function getVersion(): string
    {
        return '4.0.0-alpha';
    }

    public function getDependencies(): array
    {
        return ['3.2.3'];
    }

    public function getDescription(): string
    {
        return 'New file layout, bookmarks, sticky order, online update config, remove social networks';
    }

    public function up(OperationRecorder $recorder): void
    {
        // Copy database configuration
        if (defined('PMF_LEGACY_CONFIG_DIR') && defined('PMF_CONFIG_DIR')) {
            $recorder->copyFile(PMF_LEGACY_CONFIG_DIR . '/database.php', PMF_CONFIG_DIR . '/database.php');

            // Copy Azure configuration, if available
            $recorder->copyFile(PMF_LEGACY_CONFIG_DIR . '/azure.php', PMF_CONFIG_DIR . '/azure.php', true);

            // Copy Elasticsearch configuration, if available
            $recorder->copyFile(
                PMF_LEGACY_CONFIG_DIR . '/elasticsearch.php',
                PMF_CONFIG_DIR . '/elasticsearch.php',
                true,
            );

            // Copy LDAP configuration, if available
            $recorder->copyFile(PMF_LEGACY_CONFIG_DIR . '/ldap.php', PMF_CONFIG_DIR . '/ldap.php', true);
        }

        if (defined('PMF_ROOT_DIR')) {
            // Copy data directory
            $recorder->copyDirectory(PMF_ROOT_DIR . '/data', PMF_ROOT_DIR . '/content/core');

            // Copy logs directory
            $recorder->copyDirectory(PMF_ROOT_DIR . '/logs', PMF_ROOT_DIR . '/content/core');

            // Copy attachments directory
            $recorder->copyDirectory(PMF_ROOT_DIR . '/attachments', PMF_ROOT_DIR . '/content/user');

            // Copy images directory
            $recorder->copyDirectory(PMF_ROOT_DIR . '/images', PMF_ROOT_DIR . '/content/user');
        }

        // Online Update configuration
        $recorder->addConfig('upgrade.onlineUpdateEnabled', true);
        $recorder->addConfig('upgrade.releaseEnvironment', ReleaseType::DEVELOPMENT->value);
        $recorder->addConfig('upgrade.dateLastChecked', '');
        $recorder->addConfig('upgrade.lastDownloadedPackage', '');

        // Rewrite rules are now mandatory, social network support removed
        $recorder->deleteConfig('main.enableRewriteRules');
        $recorder->deleteConfig('socialnetworks.enableTwitterSupport');
        $recorder->deleteConfig('socialnetworks.twitterConsumerKey');
        $recorder->deleteConfig('socialnetworks.twitterConsumerSecret');
        $recorder->deleteConfig('socialnetworks.twitterAccessTokenKey');
        $recorder->deleteConfig('socialnetworks.twitterAccessTokenSecret');
        $recorder->deleteConfig('socialnetworks.disableAll');
        $recorder->deleteConfig('mail.remoteSMTPEncryption');

        // Bookmarks support
        if ($this->isMySql()) {
            $recorder->addSql(
                sprintf(
                    'CREATE TABLE %sfaqbookmarks (userid int(11) DEFAULT NULL, faqid int(11) DEFAULT NULL)',
                    $this->tablePrefix,
                ),
                'Create bookmarks table (MySQL)',
            );
        } else {
            $recorder->addSql(
                sprintf(
                    'CREATE TABLE %sfaqbookmarks (userid INTEGER DEFAULT NULL, faqid INTEGER DEFAULT NULL)',
                    $this->tablePrefix,
                ),
                'Create bookmarks table',
            );
        }

        // Custom order of sticky records
        if ($this->isMySql()) {
            $recorder->addSql(
                sprintf('ALTER TABLE %sfaqdata ADD COLUMN sticky_order int(10) DEFAULT NULL', $this->tablePrefix),
                'Add sticky_order column to faqdata (MySQL)',
            );

            $recorder->addSql(
                sprintf(
                    'ALTER TABLE %sfaqdata_revisions ADD COLUMN sticky_order int(10) DEFAULT NULL',
                    $this->tablePrefix,
                ),
                'Add sticky_order column to faqdata_revisions (MySQL)',
            );
        } else {
            $recorder->addSql(
                sprintf('ALTER TABLE %sfaqdata ADD COLUMN sticky_order integer DEFAULT NULL', $this->tablePrefix),
                'Add sticky_order column to faqdata',
            );

            $recorder->addSql(
                sprintf(
                    'ALTER TABLE %sfaqdata_revisions ADD COLUMN sticky_order integer DEFAULT NULL',
                    $this->tablePrefix,
                ),
                'Add sticky_order column to faqdata_revisions',
            );
        }

        $recorder->addConfig('records.orderStickyFaqsCustom', 'false');

        // Remove template metadata tables
        $recorder->addSql(sprintf('DROP TABLE %sfaqmeta', $this->tablePrefix), 'Drop faqmeta table');

        // Blocked statistics browsers
        $recorder->addConfig('main.botIgnoreList', 'nustcrape,webpost,GoogleBot,msnbot,crawler,scooter,
            bravobrian,archiver,w3c,controler,wget,bot,spider,Yahoo! Slurp,htdig,gsa-crawler,AirControler,Uptime-Kuma');

        // Enable/Disable cookie consent
        $recorder->addConfig('main.enableCookieConsent', true);

        // Add parent category ID to faqcategory_order
        if ($this->isMySql()) {
            $recorder->addSql(
                sprintf(
                    'ALTER TABLE %sfaqcategory_order ADD COLUMN parent_id int(11) DEFAULT NULL AFTER category_id',
                    $this->tablePrefix,
                ),
                'Add parent_id column to faqcategory_order (MySQL)',
            );
        } elseif ($this->isSqlServer()) {
            $recorder->addSql(
                sprintf(
                    'ALTER TABLE %sfaqcategory_order ADD COLUMN parent_id INTEGER DEFAULT NULL',
                    $this->tablePrefix,
                ),
                'Add parent_id column to faqcategory_order (SQL Server)',
            );
        } else {
            // SQLite and PostgreSQL - table rebuild approach
            $recorder->addSql(sprintf(
                'CREATE TABLE %sfaqcategory_order_new (
                    category_id INTEGER NOT NULL,
                    parent_id INTEGER DEFAULT NULL,
                    position INTEGER NOT NULL,
                    PRIMARY KEY (category_id))',
                $this->tablePrefix,
            ), 'Create new faqcategory_order table with parent_id');

            $recorder->addSql(
                sprintf(
                    'INSERT INTO %sfaqcategory_order_new (category_id, parent_id, position) SELECT category_id, NULL AS parent_id, position FROM %sfaqcategory_order',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Copy data to new faqcategory_order table',
            );

            $recorder->addSql(
                sprintf('DROP TABLE %sfaqcategory_order', $this->tablePrefix),
                'Drop old faqcategory_order table',
            );

            $recorder->addSql(
                sprintf(
                    'ALTER TABLE %sfaqcategory_order_new RENAME TO %sfaqcategory_order',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Rename new faqcategory_order table',
            );
        }
    }
}
