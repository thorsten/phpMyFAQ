<?php

/**
 * Migration for phpMyFAQ 4.1.3.
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
 * @since     2026-09-11
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Setup\Migration\AbstractMigration;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;

readonly class Migration413 extends AbstractMigration
{
    public function getVersion(): string
    {
        return '4.1.3';
    }

    public function getDependencies(): array
    {
        return ['4.1.0-alpha.3'];
    }

    public function getDescription(): string
    {
        return 'Seed a random API client token for installations with an empty one';
    }

    /**
     * Older installations were upgraded with an empty api.apiClientToken. The REST API rejects
     * an empty configured token since 4.1.3, so re-key those installations instead of leaving
     * their API silently unusable after the update.
     */
    public function up(OperationRecorder $recorder): void
    {
        if (($this->getConfig('api.apiClientToken') ?? '') !== '') {
            return;
        }

        $recorder->updateConfig('api.apiClientToken', bin2hex(random_bytes(32)));
    }
}
