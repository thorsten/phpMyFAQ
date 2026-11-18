<?php

/**
 * Migration for phpMyFAQ 4.2.0-RC.3: random secret for the WebAuthn decoy credential.
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
 * @since     2026-11-18
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Setup\Migration\AbstractMigration;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;

/**
 * The WebAuthn login answers logins without a passkey with a decoy credential ID. It was keyed
 * with security.salt, which older installers derived from the public reference URL, so the decoy
 * could be computed offline and told apart from a real credential ID. It is now keyed with a
 * random secret of its own.
 */
readonly class Migration420RC3 extends AbstractMigration
{
    public function getVersion(): string
    {
        return '4.2.0-RC.3';
    }

    public function getDependencies(): array
    {
        return ['4.2.0-RC.2'];
    }

    public function getDescription(): string
    {
        return 'Add the random security.webAuthnDecoySecret configuration';
    }

    /**
     * Configuration::add() skips keys that already exist, so a secret created on first use by
     * AuthWebAuthn before the update ran is kept.
     */
    public function up(OperationRecorder $recorder): void
    {
        $recorder->addConfig('security.webAuthnDecoySecret', bin2hex(random_bytes(32)));
    }
}
