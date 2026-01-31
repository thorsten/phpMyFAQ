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
    public function getVersion(): string
    {
        return '4.1.0-alpha.3';
    }

    public function getDependencies(): array
    {
        return ['4.1.0-alpha.2'];
    }

    public function getDescription(): string
    {
        return 'LLMs.txt config, LDAP group integration, search optimization indexes';
    }

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
        $recorder->addSql(
            $this->createIndex('faqsearches', 'idx_faqsearches_searchterm', 'searchterm'),
            'Create searchterm index on faqsearches',
        );

        $recorder->addSql(
            $this->createIndex('faqsearches', 'idx_faqsearches_date_term', ['searchdate', 'searchterm']),
            'Create date_term index on faqsearches',
        );

        $recorder->addSql(
            $this->createIndex('faqsearches', 'idx_faqsearches_date_term_lang', ['searchdate', 'searchterm', 'lang']),
            'Create date_term_lang index on faqsearches',
        );
    }
}
