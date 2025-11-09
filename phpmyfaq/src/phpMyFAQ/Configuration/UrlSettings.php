<?php

/**
 * The phpMyFAQ URL settings class
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-11-03
 */

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

use phpMyFAQ\Configuration as CoreConfiguration;

readonly class UrlSettings
{
    public function __construct(
        private CoreConfiguration $configuration,
    ) {
    }

    public function getDefaultUrl(): string
    {
        $defaultUrl = (string) $this->configuration->get(item: 'main.referenceURL');
        return str_ends_with($defaultUrl, '/') ? $defaultUrl : $defaultUrl . '/';
    }

    /**
     * Returns an array with allowed media hosts for records.
     * @return string[]
     */
    public function getAllowedMediaHosts(): array
    {
        $val = (string) $this->configuration->get(item: 'records.allowedMediaHosts');
        return $val === '' ? [] : explode(',', $val);
    }
}
