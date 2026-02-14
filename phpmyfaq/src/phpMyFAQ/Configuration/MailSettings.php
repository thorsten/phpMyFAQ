<?php

/**
 * The mail settings class
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-11-03
 */

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

use phpMyFAQ\Configuration as CoreConfiguration;

readonly class MailSettings
{
    private const string DEFAULT_PROVIDER = 'smtp';
    private const array ALLOWED_PROVIDERS = ['smtp', 'sendgrid', 'ses'];

    public function __construct(
        private CoreConfiguration $coreConfiguration,
    ) {
    }

    public function getNoReplyEmail(): string
    {
        $sender = $this->coreConfiguration->get(item: 'mail.noReplySenderAddress');
        if ($sender === '' || $sender === null) {
            return $this->coreConfiguration->get(item: 'main.administrationMail');
        }

        return (string) $sender;
    }

    public function getProvider(): string
    {
        $provider = strtolower(
            (string) ($this->coreConfiguration->get(item: 'mail.provider') ?? self::DEFAULT_PROVIDER),
        );

        if (!in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            return self::DEFAULT_PROVIDER;
        }

        return $provider;
    }
}
