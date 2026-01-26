<?php

/**
 * Migration for phpMyFAQ 4.1.0-alpha.3.
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

readonly class Migration410Alpha3 extends AbstractMigration
{
    /**
     * Migration version identifier for this migration.
     *
     * @return string The migration version string (e.g. "4.1.0-alpha.3").
     */
    public function getVersion(): string
    {
        return '4.1.0-alpha.3';
    }

    /**
     * List migration versions that this migration depends on.
     *
     * @return string[] An array of migration version strings that must be applied before this migration.
     */
    public function getDependencies(): array
    {
        return ['4.1.0-alpha.2'];
    }

    /**
     * Provide a short, human-readable description of this migration.
     *
     * @return string A short, human-readable description of the migration's changes.
     */
    public function getDescription(): string
    {
        return 'LLMs.txt config, LDAP group integration, search optimization indexes';
    }

    /**
     * Apply migration for 4.1.0-alpha.3: add configuration entries and schedule faqsearches indexes.
     *
     * Records the LLMs.txt content and LDAP/search configuration keys, and records SQL statements to
     * create performance indexes on the faqsearches table using database-appropriate syntax.
     *
     * @param OperationRecorder $recorder Recorder used to persist configuration entries and SQL statements.
     */
    public function up(OperationRecorder $recorder): void
    {
        $llmsText =
            "# phpMyFAQ LLMs.txt\n\n"
            . "This file provides information about the AI/LLM training data availability for this FAQ system.\n\n"
            . "Contact: Please see the contact information on the main website.\n\n"
            . "The FAQ content in this system is available for LLM training purposes.\n"
            . "Please respect the licensing terms and usage guidelines of the content.\n\n"
            . 'For more information about this FAQ system, visit: https://www.phpmyfaq.de';

        $recorder->addConfig('seo.contentLlmsText', $llmsText);

        // LDAP group integration
        $recorder->addConfig('ldap.ldap_use_group_restriction', 'false');
        $recorder->addConfig('ldap.ldap_group_allowed_groups', '');
        $recorder->addConfig('ldap.ldap_group_auto_assign', 'false');
        $recorder->addConfig('ldap.ldap_group_mapping', '');

        // Search optimization configuration
        $recorder->addConfig('search.popularSearchTimeWindow', '180');

        // Performance indexes for faqsearches table
        if ($this->isSqlServer()) {
            $recorder->addSql(
                'IF NOT EXISTS (SELECT * FROM sys.indexes '
                . "WHERE name = 'idx_faqsearches_searchterm') "
                . sprintf('CREATE INDEX idx_faqsearches_searchterm ON %sfaqsearches ', $this->tablePrefix)
                . '(searchterm)',
                'Create searchterm index on faqsearches (SQL Server)',
            );

            $recorder->addSql(
                'IF NOT EXISTS (SELECT * FROM sys.indexes ' . "WHERE name = 'idx_faqsearches_date_term') "
                    . sprintf(
                        'CREATE INDEX idx_faqsearches_date_term ON %sfaqsearches (searchdate, searchterm)',
                        $this->tablePrefix,
                    ),
                'Create date_term index on faqsearches (SQL Server)',
            );

            $recorder->addSql(
                'IF NOT EXISTS (SELECT * FROM sys.indexes '
                . "WHERE name = 'idx_faqsearches_date_term_lang') "
                . sprintf('CREATE INDEX idx_faqsearches_date_term_lang ON %sfaqsearches ', $this->tablePrefix)
                . '(searchdate, searchterm, lang)',
                'Create date_term_lang index on faqsearches (SQL Server)',
            );
        } else {
            // MySQL, PostgreSQL, SQLite: Use IF NOT EXISTS
            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_faqsearches_searchterm ON %sfaqsearches (searchterm)',
                    $this->tablePrefix,
                ),
                'Create searchterm index on faqsearches',
            );

            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_faqsearches_date_term ON %sfaqsearches '
                    . '(searchdate, searchterm)',
                    $this->tablePrefix,
                ),
                'Create date_term index on faqsearches',
            );

            $recorder->addSql(
                sprintf(
                    'CREATE INDEX IF NOT EXISTS idx_faqsearches_date_term_lang ON %sfaqsearches '
                    . '(searchdate, searchterm, lang)',
                    $this->tablePrefix,
                ),
                'Create date_term_lang index on faqsearches',
            );
        }
    }
}