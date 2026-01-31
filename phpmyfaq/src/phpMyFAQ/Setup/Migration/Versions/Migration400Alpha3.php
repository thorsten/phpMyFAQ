<?php

/**
 * Migration for phpMyFAQ 4.0.0-alpha.3.
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

use phpMyFAQ\Setup\Migration\AbstractMigration;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;

readonly class Migration400Alpha3 extends AbstractMigration
{
    public function getVersion(): string
    {
        return '4.0.0-alpha.3';
    }

    public function getDependencies(): array
    {
        return ['4.0.0-alpha.2'];
    }

    public function getDescription(): string
    {
        return 'SEO table, media hosts config, layout settings, rich snippets';
    }

    public function up(OperationRecorder $recorder): void
    {
        // Create SEO table
        if ($this->isMySql()) {
            $recorder->addSql(sprintf(
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
                $this->tablePrefix,
            ), 'Create SEO table (MySQL)');
        } elseif ($this->isSqlServer()) {
            $recorder->addSql(sprintf(
                'CREATE TABLE %sfaqseo (
                    id INT NOT NULL,
                    type VARCHAR(32) NOT NULL,
                    reference_id INT NOT NULL,
                    reference_language VARCHAR(5) NOT NULL,
                    title TEXT NULL,
                    description TEXT NULL,
                    slug TEXT NULL,
                    created DATETIME NOT NULL DEFAULT GETDATE(),
                    PRIMARY KEY (id))',
                $this->tablePrefix,
            ), 'Create SEO table (SQL Server)');
        } elseif ($this->isSqlite()) {
            $recorder->addSql(sprintf(
                'CREATE TABLE %sfaqseo (
                    id INT NOT NULL,
                    type VARCHAR(32) NOT NULL,
                    reference_id INT NOT NULL,
                    reference_language VARCHAR(5) NOT NULL,
                    title TEXT NULL,
                    description TEXT NULL,
                    slug TEXT NULL,
                    created DATETIME NOT NULL DEFAULT (datetime(\'now\')),
                    PRIMARY KEY (id))',
                $this->tablePrefix,
            ), 'Create SEO table (SQLite)');
        } elseif ($this->isPostgreSql()) {
            $recorder->addSql(sprintf(
                'CREATE TABLE %sfaqseo (
                    id INTEGER NOT NULL,
                    type VARCHAR(32) NOT NULL,
                    reference_id INTEGER NOT NULL,
                    reference_language VARCHAR(5) NOT NULL,
                    title TEXT,
                    description TEXT,
                    slug TEXT NULL,
                    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id))',
                $this->tablePrefix,
            ), 'Create SEO table (PostgreSQL)');
        }

        // Update bot ignore list
        $recorder->updateConfig(
            'main.botIgnoreList',
            'nustcrape,webpost,GoogleBot,msnbot,crawler,scooter,bravobrian,archiver,'
            . 'w3c,controler,wget,bot,spider,Yahoo! Slurp,htdig,gsa-crawler,AirControler,Uptime-Kuma,facebookcatalog/1.0,'
            . 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php),facebookexternalhit/1.1',
        );

        // New configuration items
        $recorder->addConfig('mail.noReplySenderAddress', '');
        $recorder->addConfig('records.allowedMediaHosts', 'www.youtube.com');
        $recorder->addConfig('seo.title', '');
        $recorder->addConfig('seo.description', '');
        $recorder->addConfig('layout.enablePrivacyLink', 'true');
        $recorder->addConfig('layout.customCss', '');
        $recorder->addConfig('seo.enableRichSnippets', 'false');

        // Delete old config items
        $recorder->deleteConfig('main.urlValidateInterval');
        $recorder->deleteConfig('main.enableGzipCompression');
        $recorder->deleteConfig('main.metaKeywords');
        $recorder->deleteConfig('main.send2friendText');

        // Rename config items
        $recorder->renameConfig('main.templateSet', 'layout.templateSet');
        $recorder->renameConfig('main.enableCookieConsent', 'layout.enableCookieConsent');
        $recorder->renameConfig('main.contactInformationHTML', 'layout.contactInformationHTML');
    }
}
