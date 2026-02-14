<?php

/**
 * Migration for phpMyFAQ 4.2.0-alpha.
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

use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Setup\Migration\AbstractMigration;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;

readonly class Migration420Alpha extends AbstractMigration
{
    public function getVersion(): string
    {
        return '4.2.0-alpha';
    }

    public function getDependencies(): array
    {
        return ['4.1.0-alpha.3'];
    }

    public function getDescription(): string
    {
        return 'Admin log hash columns, custom pages, chat messages, translation config, API rate limiting, queue jobs, mail provider config, API keys, OAuth2 tables';
    }

    public function up(OperationRecorder $recorder): void
    {
        // Add hash columns to faqadminlog
        if ($this->isMySql()) {
            $recorder->addSql(
                sprintf('ALTER TABLE %sfaqadminlog ADD COLUMN hash VARCHAR(64) AFTER text', $this->tablePrefix),
                'Add hash column to faqadminlog (MySQL)',
            );

            $recorder->addSql(
                sprintf(
                    'ALTER TABLE %sfaqadminlog ADD COLUMN previous_hash VARCHAR(64) AFTER hash',
                    $this->tablePrefix,
                ),
                'Add previous_hash column to faqadminlog (MySQL)',
            );
        } elseif ($this->isSqlServer()) {
            $recorder->addSql(
                sprintf('ALTER TABLE %sfaqadminlog ADD hash VARCHAR(64)', $this->tablePrefix),
                'Add hash column to faqadminlog (SQL Server)',
            );

            $recorder->addSql(
                sprintf('ALTER TABLE %sfaqadminlog ADD previous_hash VARCHAR(64)', $this->tablePrefix),
                'Add previous_hash column to faqadminlog (SQL Server)',
            );
        } else {
            $recorder->addSql(
                sprintf('ALTER TABLE %sfaqadminlog ADD COLUMN hash VARCHAR(64)', $this->tablePrefix),
                'Add hash column to faqadminlog',
            );

            $recorder->addSql(
                sprintf('ALTER TABLE %sfaqadminlog ADD COLUMN previous_hash VARCHAR(64)', $this->tablePrefix),
                'Add previous_hash column to faqadminlog',
            );
        }

        $recorder->addSql($this->createIndex('faqadminlog', 'idx_hash', 'hash'), 'Create hash index on faqadminlog');

        // Create custom pages table
        if ($this->isMySql()) {
            $recorder->addSql(sprintf(
                "CREATE TABLE IF NOT EXISTS %sfaqcustompages (
                    id INT(11) NOT NULL,
                    lang VARCHAR(5) NOT NULL,
                    page_title VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) NOT NULL,
                    content TEXT NOT NULL,
                    author_name VARCHAR(255) NOT NULL,
                    author_email VARCHAR(255) NOT NULL,
                    active CHAR(1) NOT NULL DEFAULT 'n',
                    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated TIMESTAMP NULL,
                    seo_title VARCHAR(60) NULL,
                    seo_description VARCHAR(160) NULL,
                    seo_robots VARCHAR(50) NOT NULL DEFAULT 'index,follow',
                    PRIMARY KEY (id, lang),
                    INDEX idx_custompages_slug (slug, lang)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB",
                $this->tablePrefix,
            ), 'Create custom pages table (MySQL)');
        } elseif ($this->isPostgreSql()) {
            $recorder->addSql(sprintf(
                "CREATE TABLE IF NOT EXISTS %sfaqcustompages (
                    id INTEGER NOT NULL,
                    lang VARCHAR(5) NOT NULL,
                    page_title VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) NOT NULL,
                    content TEXT NOT NULL,
                    author_name VARCHAR(255) NOT NULL,
                    author_email VARCHAR(255) NOT NULL,
                    active CHAR(1) NOT NULL DEFAULT 'n',
                    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated TIMESTAMP NULL,
                    seo_title VARCHAR(60) NULL,
                    seo_description VARCHAR(160) NULL,
                    seo_robots VARCHAR(50) NOT NULL DEFAULT 'index,follow',
                    PRIMARY KEY (id, lang)
                )",
                $this->tablePrefix,
            ), 'Create custom pages table (PostgreSQL)');

            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_custompages_slug ON %sfaqcustompages (slug, lang)',
                    $this->tablePrefix,
                ),
                'Create slug index on custom pages (PostgreSQL)',
            );
        } elseif ($this->isSqlite()) {
            $recorder->addSql(sprintf("CREATE TABLE IF NOT EXISTS %sfaqcustompages (
                    id INTEGER NOT NULL,
                    lang VARCHAR(5) NOT NULL,
                    page_title VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) NOT NULL,
                    content TEXT NOT NULL,
                    author_name VARCHAR(255) NOT NULL,
                    author_email VARCHAR(255) NOT NULL,
                    active CHAR(1) NOT NULL DEFAULT 'n',
                    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated TIMESTAMP NULL,
                    seo_title VARCHAR(60) NULL,
                    seo_description VARCHAR(160) NULL,
                    seo_robots VARCHAR(50) NOT NULL DEFAULT 'index,follow',
                    PRIMARY KEY (id, lang)
                )", $this->tablePrefix), 'Create custom pages table (SQLite)');

            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_custompages_slug ON %sfaqcustompages (slug, lang)',
                    $this->tablePrefix,
                ),
                'Create slug index on custom pages (SQLite)',
            );
        } elseif ($this->isSqlServer()) {
            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'%sfaqcustompages') AND type = 'U') "
                    . "CREATE TABLE %sfaqcustompages (
                    id INT NOT NULL,
                    lang VARCHAR(5) NOT NULL,
                    page_title VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) NOT NULL,
                    content NVARCHAR(MAX) NOT NULL,
                    author_name VARCHAR(255) NOT NULL,
                    author_email VARCHAR(255) NOT NULL,
                    active CHAR(1) NOT NULL DEFAULT 'n',
                    created DATETIME NOT NULL DEFAULT GETDATE(),
                    updated DATETIME NULL,
                    seo_title VARCHAR(60) NULL,
                    seo_description VARCHAR(160) NULL,
                    seo_robots VARCHAR(50) NOT NULL DEFAULT 'index,follow',
                    PRIMARY KEY (id, lang)
                )",
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create custom pages table (SQL Server)',
            );

            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT name FROM sys.indexes WHERE name = 'idx_custompages_slug'"
                    . " AND object_id = OBJECT_ID(N'%sfaqcustompages'))"
                    . ' CREATE INDEX idx_custompages_slug ON %sfaqcustompages (slug, lang)',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create slug index on custom pages (SQL Server)',
            );
        }

        // Add new permissions for custom pages
        $recorder->grantPermission(PermissionType::PAGE_ADD->value, 'Right to add custom pages');
        $recorder->grantPermission(PermissionType::PAGE_EDIT->value, 'Right to edit custom pages');
        $recorder->grantPermission(PermissionType::PAGE_DELETE->value, 'Right to delete custom pages');

        // Add configuration entries
        $recorder->addConfig('main.termsURL', '');
        $recorder->addConfig('main.imprintURL', '');
        $recorder->addConfig('main.cookiePolicyURL', '');
        $recorder->addConfig('main.accessibilityStatementURL', '');
        $recorder->addConfig('api.onlyActiveFaqs', 'true');
        $recorder->addConfig('api.onlyActiveCategories', 'true');
        $recorder->addConfig('api.onlyPublicQuestions', 'true');
        $recorder->addConfig('api.ignoreOrphanedFaqs', 'true');
        $recorder->addConfig('api.rateLimit.requests', '100');
        $recorder->addConfig('api.rateLimit.interval', '3600');
        $recorder->addConfig('queue.transport', 'database');
        $recorder->addConfig('mail.provider', 'smtp');
        $recorder->addConfig('mail.sendgridApiKey', '');
        $recorder->addConfig('mail.sesAccessKeyId', '');
        $recorder->addConfig('mail.sesSecretAccessKey', '');
        $recorder->addConfig('mail.sesRegion', '');
        $recorder->addConfig('mail.mailgunApiKey', '');
        $recorder->addConfig('mail.mailgunDomain', '');

        // Translation service configuration
        $recorder->addConfig('translation.provider', 'none');
        $recorder->addConfig('translation.googleApiKey', '');
        $recorder->addConfig('translation.deeplApiKey', '');
        $recorder->addConfig('translation.deeplUseFreeApi', 'true');
        $recorder->addConfig('translation.azureKey', '');
        $recorder->addConfig('translation.azureRegion', '');
        $recorder->addConfig('translation.amazonAccessKeyId', '');
        $recorder->addConfig('translation.amazonSecretAccessKey', '');
        $recorder->addConfig('translation.amazonRegion', 'us-east-1');
        $recorder->addConfig('translation.libreTranslateUrl', 'https://libretranslate.com');
        $recorder->addConfig('translation.libreTranslateApiKey', '');

        $recorder->addConfig('main.enableCommentEditor', 'false');

        // Create the chat messages table
        if ($this->isMySql()) {
            $recorder->addSql(sprintf(
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
                $this->tablePrefix,
            ), 'Create chat messages table (MySQL)');
        } elseif ($this->isPostgreSql()) {
            $recorder->addSql(sprintf(
                'CREATE TABLE IF NOT EXISTS %sfaqchat_messages (
                    id SERIAL NOT NULL,
                    sender_id INTEGER NOT NULL,
                    recipient_id INTEGER NOT NULL,
                    message TEXT NOT NULL,
                    is_read SMALLINT NOT NULL DEFAULT 0,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                )',
                $this->tablePrefix,
            ), 'Create chat messages table (PostgreSQL)');

            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_chat_sender ON %sfaqchat_messages (sender_id)',
                    $this->tablePrefix,
                ),
                'Create sender index on chat messages (PostgreSQL)',
            );

            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_chat_recipient ON %sfaqchat_messages (recipient_id)',
                    $this->tablePrefix,
                ),
                'Create recipient index on chat messages (PostgreSQL)',
            );

            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_chat_conversation ON %sfaqchat_messages (sender_id, recipient_id)',
                    $this->tablePrefix,
                ),
                'Create conversation index on chat messages (PostgreSQL)',
            );

            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_chat_created ON %sfaqchat_messages (created_at)',
                    $this->tablePrefix,
                ),
                'Create created_at index on chat messages (PostgreSQL)',
            );
        } elseif ($this->isSqlite()) {
            $recorder->addSql(sprintf('CREATE TABLE IF NOT EXISTS %sfaqchat_messages (
                    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                    sender_id INTEGER NOT NULL,
                    recipient_id INTEGER NOT NULL,
                    message TEXT NOT NULL,
                    is_read INTEGER NOT NULL DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )', $this->tablePrefix), 'Create chat messages table (SQLite)');

            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_chat_sender ON %sfaqchat_messages (sender_id)',
                    $this->tablePrefix,
                ),
                'Create sender index on chat messages (SQLite)',
            );

            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_chat_recipient ON %sfaqchat_messages (recipient_id)',
                    $this->tablePrefix,
                ),
                'Create recipient index on chat messages (SQLite)',
            );

            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_chat_conversation ON %sfaqchat_messages (sender_id, recipient_id)',
                    $this->tablePrefix,
                ),
                'Create conversation index on chat messages (SQLite)',
            );

            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_chat_created ON %sfaqchat_messages (created_at)',
                    $this->tablePrefix,
                ),
                'Create created_at index on chat messages (SQLite)',
            );
        } elseif ($this->isSqlServer()) {
            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'%sfaqchat_messages') AND type = 'U') "
                    . 'CREATE TABLE %sfaqchat_messages (
                    id INT IDENTITY(1,1) NOT NULL,
                    sender_id INT NOT NULL,
                    recipient_id INT NOT NULL,
                    message NVARCHAR(MAX) NOT NULL,
                    is_read TINYINT NOT NULL DEFAULT 0,
                    created_at DATETIME NOT NULL DEFAULT GETDATE(),
                    PRIMARY KEY (id)
                )',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create chat messages table (SQL Server)',
            );

            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT name FROM sys.indexes WHERE name = 'idx_chat_sender'"
                    . " AND object_id = OBJECT_ID(N'%sfaqchat_messages'))"
                    . ' CREATE INDEX idx_chat_sender ON %sfaqchat_messages (sender_id)',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create sender index on chat messages (SQL Server)',
            );

            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT name FROM sys.indexes WHERE name = 'idx_chat_recipient'"
                    . " AND object_id = OBJECT_ID(N'%sfaqchat_messages'))"
                    . ' CREATE INDEX idx_chat_recipient ON %sfaqchat_messages (recipient_id)',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create recipient index on chat messages (SQL Server)',
            );

            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT name FROM sys.indexes WHERE name = 'idx_chat_conversation'"
                    . " AND object_id = OBJECT_ID(N'%sfaqchat_messages'))"
                    . ' CREATE INDEX idx_chat_conversation ON %sfaqchat_messages (sender_id, recipient_id)',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create conversation index on chat messages (SQL Server)',
            );

            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT name FROM sys.indexes WHERE name = 'idx_chat_created'"
                    . " AND object_id = OBJECT_ID(N'%sfaqchat_messages'))"
                    . ' CREATE INDEX idx_chat_created ON %sfaqchat_messages (created_at)',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create created_at index on chat messages (SQL Server)',
            );
        }

        // Create a push subscriptions table
        if ($this->isMySql()) {
            $recorder->addSql(sprintf(
                'CREATE TABLE IF NOT EXISTS %sfaqpush_subscriptions (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    user_id INT(11) NOT NULL,
                    endpoint TEXT NOT NULL,
                    endpoint_hash VARCHAR(64) NOT NULL,
                    public_key TEXT NOT NULL,
                    auth_token TEXT NOT NULL,
                    content_encoding VARCHAR(50) NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    INDEX idx_push_user_id (user_id),
                    UNIQUE INDEX idx_push_endpoint_hash (endpoint_hash)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',
                $this->tablePrefix,
            ), 'Create push subscriptions table (MySQL)');
        } elseif ($this->isPostgreSql()) {
            $recorder->addSql(sprintf(
                'CREATE TABLE IF NOT EXISTS %sfaqpush_subscriptions (
                    id SERIAL NOT NULL,
                    user_id INTEGER NOT NULL,
                    endpoint TEXT NOT NULL,
                    endpoint_hash VARCHAR(64) NOT NULL,
                    public_key TEXT NOT NULL,
                    auth_token TEXT NOT NULL,
                    content_encoding VARCHAR(50) NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                )',
                $this->tablePrefix,
            ), 'Create push subscriptions table (PostgreSQL)');

            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_push_user_id ON %sfaqpush_subscriptions (user_id)',
                    $this->tablePrefix,
                ),
                'Create user_id index on push subscriptions (PostgreSQL)',
            );

            $recorder->addSql(
                sprintf(
                    'CREATE UNIQUE INDEX IF NOT EXISTS idx_push_endpoint_hash ON %sfaqpush_subscriptions (endpoint_hash)',
                    $this->tablePrefix,
                ),
                'Create endpoint_hash unique index on push subscriptions (PostgreSQL)',
            );
        } elseif ($this->isSqlite()) {
            $recorder->addSql(sprintf(
                'CREATE TABLE IF NOT EXISTS %sfaqpush_subscriptions (
                    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    endpoint TEXT NOT NULL,
                    endpoint_hash VARCHAR(64) NOT NULL,
                    public_key TEXT NOT NULL,
                    auth_token TEXT NOT NULL,
                    content_encoding VARCHAR(50) NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )',
                $this->tablePrefix,
            ), 'Create push subscriptions table (SQLite)');

            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_push_user_id ON %sfaqpush_subscriptions (user_id)',
                    $this->tablePrefix,
                ),
                'Create user_id index on push subscriptions (SQLite)',
            );

            $recorder->addSql(
                sprintf(
                    'CREATE UNIQUE INDEX IF NOT EXISTS idx_push_endpoint_hash ON %sfaqpush_subscriptions (endpoint_hash)',
                    $this->tablePrefix,
                ),
                'Create endpoint_hash unique index on push subscriptions (SQLite)',
            );
        } elseif ($this->isSqlServer()) {
            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'%sfaqpush_subscriptions') AND type = 'U') "
                    . 'CREATE TABLE %sfaqpush_subscriptions (
                    id INT IDENTITY(1,1) NOT NULL,
                    user_id INT NOT NULL,
                    endpoint NVARCHAR(MAX) NOT NULL,
                    endpoint_hash VARCHAR(64) NOT NULL,
                    public_key NVARCHAR(MAX) NOT NULL,
                    auth_token NVARCHAR(MAX) NOT NULL,
                    content_encoding VARCHAR(50) NULL,
                    created_at DATETIME NOT NULL DEFAULT GETDATE(),
                    PRIMARY KEY (id)
                )',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create push subscriptions table (SQL Server)',
            );

            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT name FROM sys.indexes WHERE name = 'idx_push_user_id'"
                    . " AND object_id = OBJECT_ID(N'%sfaqpush_subscriptions'))"
                    . ' CREATE INDEX idx_push_user_id ON %sfaqpush_subscriptions (user_id)',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create user_id index on push subscriptions (SQL Server)',
            );

            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT name FROM sys.indexes WHERE name = 'idx_push_endpoint_hash'"
                    . " AND object_id = OBJECT_ID(N'%sfaqpush_subscriptions'))"
                    . ' CREATE UNIQUE INDEX idx_push_endpoint_hash ON %sfaqpush_subscriptions (endpoint_hash)',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create endpoint_hash unique index on push subscriptions (SQL Server)',
            );
        }

        // Add Web Push configuration entries
        $recorder->addConfig('push.enableWebPush', 'false');
        $recorder->addConfig('push.vapidPublicKey', '');
        $recorder->addConfig('push.vapidPrivateKey', '');
        $recorder->addConfig('push.vapidSubject', '');

        // Create API rate-limit table
        if ($this->isMySql()) {
            $recorder->addSql(sprintf(
                'CREATE TABLE IF NOT EXISTS %sfaqrate_limits (
                    rate_key VARCHAR(255) NOT NULL,
                    window_start INT(11) NOT NULL,
                    requests INT(11) NOT NULL DEFAULT 0,
                    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (rate_key, window_start)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',
                $this->tablePrefix,
            ), 'Create API rate limits table (MySQL)');
        } elseif ($this->isPostgreSql()) {
            $recorder->addSql(sprintf(
                'CREATE TABLE IF NOT EXISTS %sfaqrate_limits (
                    rate_key VARCHAR(255) NOT NULL,
                    window_start INTEGER NOT NULL,
                    requests INTEGER NOT NULL DEFAULT 0,
                    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (rate_key, window_start)
                )',
                $this->tablePrefix,
            ), 'Create API rate limits table (PostgreSQL)');
        } elseif ($this->isSqlite()) {
            $recorder->addSql(sprintf('CREATE TABLE IF NOT EXISTS %sfaqrate_limits (
                    rate_key VARCHAR(255) NOT NULL,
                    window_start INTEGER NOT NULL,
                    requests INTEGER NOT NULL DEFAULT 0,
                    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (rate_key, window_start)
                )', $this->tablePrefix), 'Create API rate limits table (SQLite)');
        } elseif ($this->isSqlServer()) {
            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'%sfaqrate_limits') AND type = 'U') "
                    . 'CREATE TABLE %sfaqrate_limits (
                    rate_key VARCHAR(255) NOT NULL,
                    window_start INT NOT NULL,
                    requests INT NOT NULL DEFAULT 0,
                    created DATETIME NOT NULL DEFAULT GETDATE(),
                    PRIMARY KEY (rate_key, window_start)
                )',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create API rate limits table (SQL Server)',
            );
        }

        // Create a queue jobs table
        if ($this->isMySql()) {
            $recorder->addSql(sprintf(
                "CREATE TABLE IF NOT EXISTS %sfaqjobs (
                    id INT NOT NULL AUTO_INCREMENT,
                    queue VARCHAR(100) NOT NULL DEFAULT 'default',
                    body TEXT NOT NULL,
                    headers TEXT NULL,
                    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    available_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    delivered_at TIMESTAMP NULL,
                    PRIMARY KEY (id),
                    INDEX idx_faqjobs_queue_available (queue, available_at),
                    INDEX idx_faqjobs_delivered_at (delivered_at)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB",
                $this->tablePrefix,
            ), 'Create queue jobs table (MySQL)');
        } elseif ($this->isPostgreSql()) {
            $recorder->addSql(sprintf('CREATE TABLE IF NOT EXISTS %sfaqjobs (
                    id SERIAL NOT NULL,
                    queue VARCHAR(100) NOT NULL DEFAULT \'default\',
                    body TEXT NOT NULL,
                    headers TEXT NULL,
                    available_at TIMESTAMP NOT NULL,
                    delivered_at TIMESTAMP NULL,
                    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                )', $this->tablePrefix), 'Create queue jobs table (PostgreSQL)');

            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_faqjobs_queue_available ON %sfaqjobs (queue, available_at)',
                    $this->tablePrefix,
                ),
                'Create queue/available index on queue jobs (PostgreSQL)',
            );

            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_faqjobs_delivered_at ON %sfaqjobs (delivered_at)',
                    $this->tablePrefix,
                ),
                'Create delivered_at index on queue jobs (PostgreSQL)',
            );
        } elseif ($this->isSqlite()) {
            $recorder->addSql(sprintf('CREATE TABLE IF NOT EXISTS %sfaqjobs (
                    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                    queue VARCHAR(100) NOT NULL DEFAULT \'default\',
                    body TEXT NOT NULL,
                    headers TEXT NULL,
                    available_at DATETIME NOT NULL,
                    delivered_at DATETIME NULL,
                    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                )', $this->tablePrefix), 'Create queue jobs table (SQLite)');

            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_faqjobs_queue_available ON %sfaqjobs (queue, available_at)',
                    $this->tablePrefix,
                ),
                'Create queue/available index on queue jobs (SQLite)',
            );

            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_faqjobs_delivered_at ON %sfaqjobs (delivered_at)',
                    $this->tablePrefix,
                ),
                'Create delivered_at index on queue jobs (SQLite)',
            );
        } elseif ($this->isSqlServer()) {
            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'%sfaqjobs') AND type = 'U') "
                    . "CREATE TABLE %sfaqjobs (
                    id INT IDENTITY(1,1) NOT NULL,
                    queue VARCHAR(100) NOT NULL DEFAULT 'default',
                    body NVARCHAR(MAX) NOT NULL,
                    headers NVARCHAR(MAX) NULL,
                    available_at DATETIME NOT NULL,
                    delivered_at DATETIME NULL,
                    created DATETIME NOT NULL DEFAULT GETDATE(),
                    PRIMARY KEY (id)
                )",
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create queue jobs table (SQL Server)',
            );

            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT name FROM sys.indexes WHERE name = 'idx_faqjobs_queue_available'"
                    . " AND object_id = OBJECT_ID(N'%sfaqjobs'))"
                    . ' CREATE INDEX idx_faqjobs_queue_available ON %sfaqjobs (queue, available_at)',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create queue/available index on queue jobs (SQL Server)',
            );

            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT name FROM sys.indexes WHERE name = 'idx_faqjobs_delivered_at'"
                    . " AND object_id = OBJECT_ID(N'%sfaqjobs'))"
                    . ' CREATE INDEX idx_faqjobs_delivered_at ON %sfaqjobs (delivered_at)',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create delivered_at index on queue jobs (SQL Server)',
            );
        }

        // Create API keys table
        if ($this->isMySql()) {
            $recorder->addSql(sprintf(
                'CREATE TABLE IF NOT EXISTS %sfaqapi_keys (
                    id INT(11) NOT NULL,
                    user_id INT(11) NOT NULL,
                    api_key VARCHAR(64) NOT NULL,
                    name VARCHAR(255) NULL,
                    scopes TEXT NULL,
                    last_used_at TIMESTAMP NULL,
                    expires_at TIMESTAMP NULL,
                    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE INDEX idx_api_key_unique (api_key),
                    INDEX idx_api_key_user (user_id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',
                $this->tablePrefix,
            ), 'Create API keys table (MySQL)');
        } elseif ($this->isPostgreSql()) {
            $recorder->addSql(sprintf('CREATE TABLE IF NOT EXISTS %sfaqapi_keys (
                    id INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    api_key VARCHAR(64) NOT NULL,
                    name VARCHAR(255) NULL,
                    scopes TEXT NULL,
                    last_used_at TIMESTAMP NULL,
                    expires_at TIMESTAMP NULL,
                    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                )', $this->tablePrefix), 'Create API keys table (PostgreSQL)');

            $recorder->addSql(
                sprintf(
                    'CREATE UNIQUE INDEX IF NOT EXISTS idx_api_key_unique ON %sfaqapi_keys (api_key)',
                    $this->tablePrefix,
                ),
                'Create api_key unique index on API keys (PostgreSQL)',
            );

            $recorder->addSql(
                sprintf('CREATE INDEX IF NOT EXISTS idx_api_key_user ON %sfaqapi_keys (user_id)', $this->tablePrefix),
                'Create user_id index on API keys (PostgreSQL)',
            );
        } elseif ($this->isSqlite()) {
            $recorder->addSql(sprintf('CREATE TABLE IF NOT EXISTS %sfaqapi_keys (
                    id INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    api_key VARCHAR(64) NOT NULL,
                    name VARCHAR(255) NULL,
                    scopes TEXT NULL,
                    last_used_at DATETIME NULL,
                    expires_at DATETIME NULL,
                    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                )', $this->tablePrefix), 'Create API keys table (SQLite)');

            $recorder->addSql(
                sprintf(
                    'CREATE UNIQUE INDEX IF NOT EXISTS idx_api_key_unique ON %sfaqapi_keys (api_key)',
                    $this->tablePrefix,
                ),
                'Create api_key unique index on API keys (SQLite)',
            );

            $recorder->addSql(
                sprintf('CREATE INDEX IF NOT EXISTS idx_api_key_user ON %sfaqapi_keys (user_id)', $this->tablePrefix),
                'Create user_id index on API keys (SQLite)',
            );
        } elseif ($this->isSqlServer()) {
            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'%sfaqapi_keys') AND type = 'U') "
                    . 'CREATE TABLE %sfaqapi_keys (
                    id INT NOT NULL,
                    user_id INT NOT NULL,
                    api_key VARCHAR(64) NOT NULL,
                    name VARCHAR(255) NULL,
                    scopes NVARCHAR(MAX) NULL,
                    last_used_at DATETIME NULL,
                    expires_at DATETIME NULL,
                    created DATETIME NOT NULL DEFAULT GETDATE(),
                    PRIMARY KEY (id)
                )',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create API keys table (SQL Server)',
            );

            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT name FROM sys.indexes WHERE name = 'idx_api_key_unique'"
                    . " AND object_id = OBJECT_ID(N'%sfaqapi_keys'))"
                    . ' CREATE UNIQUE INDEX idx_api_key_unique ON %sfaqapi_keys (api_key)',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create api_key unique index on API keys (SQL Server)',
            );

            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT name FROM sys.indexes WHERE name = 'idx_api_key_user'"
                    . " AND object_id = OBJECT_ID(N'%sfaqapi_keys'))"
                    . ' CREATE INDEX idx_api_key_user ON %sfaqapi_keys (user_id)',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create user_id index on API keys (SQL Server)',
            );
        }

        // OAuth2 configuration entries
        $recorder->addConfig('oauth2.enable', 'false');
        $recorder->addConfig('oauth2.privateKeyPath', '');
        $recorder->addConfig('oauth2.publicKeyPath', '');
        $recorder->addConfig('oauth2.encryptionKey', '');
        $recorder->addConfig('oauth2.accessTokenTTL', 'PT1H');
        $recorder->addConfig('oauth2.refreshTokenTTL', 'P1M');
        $recorder->addConfig('oauth2.authCodeTTL', 'PT10M');

        // OAuth2 storage tables
        if ($this->isMySql()) {
            $recorder->addSql(sprintf(
                'CREATE TABLE IF NOT EXISTS %sfaqoauth_clients (
                    client_id VARCHAR(80) NOT NULL,
                    client_secret VARCHAR(255) NULL,
                    name VARCHAR(255) NOT NULL,
                    redirect_uri TEXT NULL,
                    grants VARCHAR(255) NULL,
                    is_confidential TINYINT(1) NOT NULL DEFAULT 1,
                    user_id INT(11) NULL,
                    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (client_id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',
                $this->tablePrefix,
            ), 'Create OAuth2 clients table (MySQL)');

            $recorder->addSql(sprintf(
                'CREATE TABLE IF NOT EXISTS %sfaqoauth_scopes (
                    scope_id VARCHAR(80) NOT NULL,
                    description VARCHAR(255) NULL,
                    PRIMARY KEY (scope_id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',
                $this->tablePrefix,
            ), 'Create OAuth2 scopes table (MySQL)');

            $recorder->addSql(sprintf(
                'CREATE TABLE IF NOT EXISTS %sfaqoauth_access_tokens (
                    identifier VARCHAR(100) NOT NULL,
                    client_id VARCHAR(80) NOT NULL,
                    user_id VARCHAR(80) NULL,
                    scopes TEXT NULL,
                    revoked TINYINT(1) NOT NULL DEFAULT 0,
                    expires_at TIMESTAMP NOT NULL,
                    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (identifier),
                    INDEX idx_oauth_access_client (client_id),
                    INDEX idx_oauth_access_user (user_id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',
                $this->tablePrefix,
            ), 'Create OAuth2 access tokens table (MySQL)');

            $recorder->addSql(sprintf(
                'CREATE TABLE IF NOT EXISTS %sfaqoauth_refresh_tokens (
                    identifier VARCHAR(100) NOT NULL,
                    access_token_identifier VARCHAR(100) NOT NULL,
                    revoked TINYINT(1) NOT NULL DEFAULT 0,
                    expires_at TIMESTAMP NOT NULL,
                    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (identifier),
                    INDEX idx_oauth_refresh_access (access_token_identifier)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',
                $this->tablePrefix,
            ), 'Create OAuth2 refresh tokens table (MySQL)');

            $recorder->addSql(sprintf(
                'CREATE TABLE IF NOT EXISTS %sfaqoauth_auth_codes (
                    identifier VARCHAR(100) NOT NULL,
                    client_id VARCHAR(80) NOT NULL,
                    user_id VARCHAR(80) NULL,
                    redirect_uri TEXT NULL,
                    scopes TEXT NULL,
                    revoked TINYINT(1) NOT NULL DEFAULT 0,
                    expires_at TIMESTAMP NOT NULL,
                    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (identifier),
                    INDEX idx_oauth_code_client (client_id),
                    INDEX idx_oauth_code_user (user_id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',
                $this->tablePrefix,
            ), 'Create OAuth2 auth codes table (MySQL)');
        } elseif ($this->isPostgreSql()) {
            $recorder->addSql(sprintf(
                'CREATE TABLE IF NOT EXISTS %sfaqoauth_clients (
                    client_id VARCHAR(80) NOT NULL,
                    client_secret VARCHAR(255) NULL,
                    name VARCHAR(255) NOT NULL,
                    redirect_uri TEXT NULL,
                    grants VARCHAR(255) NULL,
                    is_confidential SMALLINT NOT NULL DEFAULT 1,
                    user_id INTEGER NULL,
                    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (client_id)
                )',
                $this->tablePrefix,
            ), 'Create OAuth2 clients table (PostgreSQL)');

            $recorder->addSql(sprintf(
                'CREATE TABLE IF NOT EXISTS %sfaqoauth_scopes (
                    scope_id VARCHAR(80) NOT NULL,
                    description VARCHAR(255) NULL,
                    PRIMARY KEY (scope_id)
                )',
                $this->tablePrefix,
            ), 'Create OAuth2 scopes table (PostgreSQL)');

            $recorder->addSql(sprintf(
                'CREATE TABLE IF NOT EXISTS %sfaqoauth_access_tokens (
                    identifier VARCHAR(100) NOT NULL,
                    client_id VARCHAR(80) NOT NULL,
                    user_id VARCHAR(80) NULL,
                    scopes TEXT NULL,
                    revoked SMALLINT NOT NULL DEFAULT 0,
                    expires_at TIMESTAMP NOT NULL,
                    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (identifier)
                )',
                $this->tablePrefix,
            ), 'Create OAuth2 access tokens table (PostgreSQL)');

            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_oauth_access_client ON %sfaqoauth_access_tokens (client_id)',
                    $this->tablePrefix,
                ),
                'Create OAuth2 access client index (PostgreSQL)',
            );
            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_oauth_access_user ON %sfaqoauth_access_tokens (user_id)',
                    $this->tablePrefix,
                ),
                'Create OAuth2 access user index (PostgreSQL)',
            );

            $recorder->addSql(sprintf(
                'CREATE TABLE IF NOT EXISTS %sfaqoauth_refresh_tokens (
                    identifier VARCHAR(100) NOT NULL,
                    access_token_identifier VARCHAR(100) NOT NULL,
                    revoked SMALLINT NOT NULL DEFAULT 0,
                    expires_at TIMESTAMP NOT NULL,
                    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (identifier)
                )',
                $this->tablePrefix,
            ), 'Create OAuth2 refresh tokens table (PostgreSQL)');
            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_oauth_refresh_access ON %sfaqoauth_refresh_tokens (access_token_identifier)',
                    $this->tablePrefix,
                ),
                'Create OAuth2 refresh index (PostgreSQL)',
            );

            $recorder->addSql(sprintf(
                'CREATE TABLE IF NOT EXISTS %sfaqoauth_auth_codes (
                    identifier VARCHAR(100) NOT NULL,
                    client_id VARCHAR(80) NOT NULL,
                    user_id VARCHAR(80) NULL,
                    redirect_uri TEXT NULL,
                    scopes TEXT NULL,
                    revoked SMALLINT NOT NULL DEFAULT 0,
                    expires_at TIMESTAMP NOT NULL,
                    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (identifier)
                )',
                $this->tablePrefix,
            ), 'Create OAuth2 auth codes table (PostgreSQL)');
            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_oauth_code_client ON %sfaqoauth_auth_codes (client_id)',
                    $this->tablePrefix,
                ),
                'Create OAuth2 auth code client index (PostgreSQL)',
            );
            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_oauth_code_user ON %sfaqoauth_auth_codes (user_id)',
                    $this->tablePrefix,
                ),
                'Create OAuth2 auth code user index (PostgreSQL)',
            );
        } elseif ($this->isSqlite()) {
            $recorder->addSql(sprintf('CREATE TABLE IF NOT EXISTS %sfaqoauth_clients (
                    client_id VARCHAR(80) NOT NULL,
                    client_secret VARCHAR(255) NULL,
                    name VARCHAR(255) NOT NULL,
                    redirect_uri TEXT NULL,
                    grants VARCHAR(255) NULL,
                    is_confidential INTEGER NOT NULL DEFAULT 1,
                    user_id INTEGER NULL,
                    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (client_id)
                )', $this->tablePrefix), 'Create OAuth2 clients table (SQLite)');

            $recorder->addSql(sprintf('CREATE TABLE IF NOT EXISTS %sfaqoauth_scopes (
                    scope_id VARCHAR(80) NOT NULL,
                    description VARCHAR(255) NULL,
                    PRIMARY KEY (scope_id)
                )', $this->tablePrefix), 'Create OAuth2 scopes table (SQLite)');

            $recorder->addSql(sprintf(
                'CREATE TABLE IF NOT EXISTS %sfaqoauth_access_tokens (
                    identifier VARCHAR(100) NOT NULL,
                    client_id VARCHAR(80) NOT NULL,
                    user_id VARCHAR(80) NULL,
                    scopes TEXT NULL,
                    revoked INTEGER NOT NULL DEFAULT 0,
                    expires_at DATETIME NOT NULL,
                    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (identifier)
                )',
                $this->tablePrefix,
            ), 'Create OAuth2 access tokens table (SQLite)');
            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_oauth_access_client ON %sfaqoauth_access_tokens (client_id)',
                    $this->tablePrefix,
                ),
                'Create OAuth2 access client index (SQLite)',
            );
            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_oauth_access_user ON %sfaqoauth_access_tokens (user_id)',
                    $this->tablePrefix,
                ),
                'Create OAuth2 access user index (SQLite)',
            );

            $recorder->addSql(sprintf(
                'CREATE TABLE IF NOT EXISTS %sfaqoauth_refresh_tokens (
                    identifier VARCHAR(100) NOT NULL,
                    access_token_identifier VARCHAR(100) NOT NULL,
                    revoked INTEGER NOT NULL DEFAULT 0,
                    expires_at DATETIME NOT NULL,
                    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (identifier)
                )',
                $this->tablePrefix,
            ), 'Create OAuth2 refresh tokens table (SQLite)');
            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_oauth_refresh_access ON %sfaqoauth_refresh_tokens (access_token_identifier)',
                    $this->tablePrefix,
                ),
                'Create OAuth2 refresh index (SQLite)',
            );

            $recorder->addSql(sprintf(
                'CREATE TABLE IF NOT EXISTS %sfaqoauth_auth_codes (
                    identifier VARCHAR(100) NOT NULL,
                    client_id VARCHAR(80) NOT NULL,
                    user_id VARCHAR(80) NULL,
                    redirect_uri TEXT NULL,
                    scopes TEXT NULL,
                    revoked INTEGER NOT NULL DEFAULT 0,
                    expires_at DATETIME NOT NULL,
                    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (identifier)
                )',
                $this->tablePrefix,
            ), 'Create OAuth2 auth codes table (SQLite)');
            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_oauth_code_client ON %sfaqoauth_auth_codes (client_id)',
                    $this->tablePrefix,
                ),
                'Create OAuth2 auth code client index (SQLite)',
            );
            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_oauth_code_user ON %sfaqoauth_auth_codes (user_id)',
                    $this->tablePrefix,
                ),
                'Create OAuth2 auth code user index (SQLite)',
            );
        } elseif ($this->isSqlServer()) {
            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'%sfaqoauth_clients') AND type = 'U') "
                    . 'CREATE TABLE %sfaqoauth_clients (
                    client_id VARCHAR(80) NOT NULL,
                    client_secret VARCHAR(255) NULL,
                    name VARCHAR(255) NOT NULL,
                    redirect_uri NVARCHAR(MAX) NULL,
                    grants VARCHAR(255) NULL,
                    is_confidential TINYINT NOT NULL DEFAULT 1,
                    user_id INT NULL,
                    created DATETIME NOT NULL DEFAULT GETDATE(),
                    PRIMARY KEY (client_id)
                )',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create OAuth2 clients table (SQL Server)',
            );

            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'%sfaqoauth_scopes') AND type = 'U') "
                    . 'CREATE TABLE %sfaqoauth_scopes (
                    scope_id VARCHAR(80) NOT NULL,
                    description VARCHAR(255) NULL,
                    PRIMARY KEY (scope_id)
                )',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create OAuth2 scopes table (SQL Server)',
            );

            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'%sfaqoauth_access_tokens') AND type = 'U') "
                    . 'CREATE TABLE %sfaqoauth_access_tokens (
                    identifier VARCHAR(100) NOT NULL,
                    client_id VARCHAR(80) NOT NULL,
                    user_id VARCHAR(80) NULL,
                    scopes NVARCHAR(MAX) NULL,
                    revoked TINYINT NOT NULL DEFAULT 0,
                    expires_at DATETIME NOT NULL,
                    created DATETIME NOT NULL DEFAULT GETDATE(),
                    PRIMARY KEY (identifier)
                )',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create OAuth2 access tokens table (SQL Server)',
            );
            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT name FROM sys.indexes WHERE name = 'idx_oauth_access_client'"
                    . " AND object_id = OBJECT_ID(N'%sfaqoauth_access_tokens'))"
                    . ' CREATE INDEX idx_oauth_access_client ON %sfaqoauth_access_tokens (client_id)',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create OAuth2 access client index (SQL Server)',
            );
            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT name FROM sys.indexes WHERE name = 'idx_oauth_access_user'"
                    . " AND object_id = OBJECT_ID(N'%sfaqoauth_access_tokens'))"
                    . ' CREATE INDEX idx_oauth_access_user ON %sfaqoauth_access_tokens (user_id)',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create OAuth2 access user index (SQL Server)',
            );

            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'%sfaqoauth_refresh_tokens') AND type = 'U') "
                    . 'CREATE TABLE %sfaqoauth_refresh_tokens (
                    identifier VARCHAR(100) NOT NULL,
                    access_token_identifier VARCHAR(100) NOT NULL,
                    revoked TINYINT NOT NULL DEFAULT 0,
                    expires_at DATETIME NOT NULL,
                    created DATETIME NOT NULL DEFAULT GETDATE(),
                    PRIMARY KEY (identifier)
                )',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create OAuth2 refresh tokens table (SQL Server)',
            );
            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT name FROM sys.indexes WHERE name = 'idx_oauth_refresh_access'"
                    . " AND object_id = OBJECT_ID(N'%sfaqoauth_refresh_tokens'))"
                    . ' CREATE INDEX idx_oauth_refresh_access ON %sfaqoauth_refresh_tokens (access_token_identifier)',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create OAuth2 refresh index (SQL Server)',
            );

            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'%sfaqoauth_auth_codes') AND type = 'U') "
                    . 'CREATE TABLE %sfaqoauth_auth_codes (
                    identifier VARCHAR(100) NOT NULL,
                    client_id VARCHAR(80) NOT NULL,
                    user_id VARCHAR(80) NULL,
                    redirect_uri NVARCHAR(MAX) NULL,
                    scopes NVARCHAR(MAX) NULL,
                    revoked TINYINT NOT NULL DEFAULT 0,
                    expires_at DATETIME NOT NULL,
                    created DATETIME NOT NULL DEFAULT GETDATE(),
                    PRIMARY KEY (identifier)
                )',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create OAuth2 auth codes table (SQL Server)',
            );
            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT name FROM sys.indexes WHERE name = 'idx_oauth_code_client'"
                    . " AND object_id = OBJECT_ID(N'%sfaqoauth_auth_codes'))"
                    . ' CREATE INDEX idx_oauth_code_client ON %sfaqoauth_auth_codes (client_id)',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create OAuth2 auth code client index (SQL Server)',
            );
            $recorder->addSql(
                sprintf(
                    "IF NOT EXISTS (SELECT name FROM sys.indexes WHERE name = 'idx_oauth_code_user'"
                    . " AND object_id = OBJECT_ID(N'%sfaqoauth_auth_codes'))"
                    . ' CREATE INDEX idx_oauth_code_user ON %sfaqoauth_auth_codes (user_id)',
                    $this->tablePrefix,
                    $this->tablePrefix,
                ),
                'Create OAuth2 auth code user index (SQL Server)',
            );
        }
    }
}
