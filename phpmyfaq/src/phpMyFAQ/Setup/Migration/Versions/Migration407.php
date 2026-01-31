<?php

/**
 * Migration for phpMyFAQ 4.0.7.
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

readonly class Migration407 extends AbstractMigration
{
    public function getVersion(): string
    {
        return '4.0.7';
    }

    public function getDependencies(): array
    {
        return ['4.0.5'];
    }

    public function getDescription(): string
    {
        return 'Fix language codes for fr_CA and pt_BR';
    }

    public function up(OperationRecorder $recorder): void
    {
        // Define the table/column mappings for language code fixes
        $languageUpdates = [
            ['table' => 'faqattachment', 'column' => 'record_lang'],
            ['table' => 'faqcaptcha', 'column' => 'language'],
            ['table' => 'faqcategories', 'column' => 'lang'],
            ['table' => 'faqdata', 'column' => 'lang'],
            ['table' => 'faqcategoryrelations', 'column' => 'category_lang'],
            ['table' => 'faqcategoryrelations', 'column' => 'record_lang'],
            ['table' => 'faqchanges', 'column' => 'lang'],
            ['table' => 'faqdata_revisions', 'column' => 'lang'],
            ['table' => 'faqglossary', 'column' => 'lang'],
            ['table' => 'faqnews', 'column' => 'lang'],
            ['table' => 'faqquestions', 'column' => 'lang'],
            ['table' => 'faqsearches', 'column' => 'lang'],
            ['table' => 'faqvisits', 'column' => 'lang'],
        ];

        // Update fr-ca to fr_ca
        foreach ($languageUpdates as $update) {
            $recorder->addSql(
                sprintf(
                    "UPDATE %s%s SET %s='fr_ca' WHERE %s='fr-ca'",
                    $this->tablePrefix,
                    $update['table'],
                    $update['column'],
                    $update['column'],
                ),
                sprintf('Fix fr-ca -> fr_ca in %s.%s', $update['table'], $update['column']),
            );
        }

        // Update fr-ca language file reference in faqconfig
        $recorder->addSql(
            sprintf(
                "UPDATE %sfaqconfig SET config_value='language_fr_ca.php' WHERE config_value='language_fr-ca.php'",
                $this->tablePrefix,
            ),
            'Fix fr-ca language file config reference',
        );

        // Update pt-br to pt_br
        foreach ($languageUpdates as $update) {
            $recorder->addSql(
                sprintf(
                    "UPDATE %s%s SET %s='pt_br' WHERE %s='pt-br'",
                    $this->tablePrefix,
                    $update['table'],
                    $update['column'],
                    $update['column'],
                ),
                sprintf('Fix pt-br -> pt_br in %s.%s', $update['table'], $update['column']),
            );
        }

        // Update pt-br language file reference in faqconfig
        $recorder->addSql(
            sprintf(
                "UPDATE %sfaqconfig SET config_value='language_pt_br.php' WHERE config_value='language_pt-br.php'",
                $this->tablePrefix,
            ),
            'Fix pt-br language file config reference',
        );
    }
}
