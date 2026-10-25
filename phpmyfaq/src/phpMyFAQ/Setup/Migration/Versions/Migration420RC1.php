<?php

/**
 * Migration for phpMyFAQ 4.2.0-RC.1: re-apply the configuration changes of 4.0.0-alpha.3.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-10-25
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Setup\Migration\AbstractMigration;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;

/**
 * Between February and October 2026, Migration400Alpha3 returned right after creating the SEO table on
 * MySQL, SQLite and SQL Server, so its configuration changes were never applied to installations that
 * were updated from 3.x in that time. This migration re-applies them in an idempotent way.
 */
readonly class Migration420RC1 extends AbstractMigration
{
    private const array RENAMED_KEYS = [
        'main.templateSet' => 'layout.templateSet',
        'main.enableCookieConsent' => 'layout.enableCookieConsent',
        'main.contactInformationHTML' => 'layout.contactInformationHTML',
    ];

    private const array ADDED_KEYS = [
        'mail.noReplySenderAddress' => '',
        'records.allowedMediaHosts' => 'www.youtube.com',
        'seo.title' => '',
        'seo.description' => '',
        'layout.enablePrivacyLink' => 'true',
        'layout.customCss' => '',
        'seo.enableRichSnippets' => 'false',
    ];

    private const array OBSOLETE_KEYS = [
        'main.urlValidateInterval',
        'main.enableGzipCompression',
        'main.metaKeywords',
        'main.send2friendText',
    ];

    public function getVersion(): string
    {
        return '4.2.0-RC.1';
    }

    public function getDependencies(): array
    {
        return ['4.2.0-beta.3'];
    }

    public function getDescription(): string
    {
        return 'Re-apply the configuration changes of 4.0.0-alpha.3 that were skipped on MySQL, SQLite and SQL Server';
    }

    public function up(OperationRecorder $recorder): void
    {
        foreach (self::RENAMED_KEYS as $oldKey => $newKey) {
            if ($this->getConfig($oldKey) === null) {
                continue;
            }

            if ($this->getConfig($newKey) === null) {
                $recorder->renameConfig($oldKey, $newKey);
                continue;
            }

            $recorder->deleteConfig($oldKey);
        }

        // Configuration::add() skips keys that already exist
        foreach (self::ADDED_KEYS as $key => $value) {
            $recorder->addConfig($key, $value);
        }

        foreach (self::OBSOLETE_KEYS as $key) {
            $recorder->deleteConfig($key);
        }
    }
}
