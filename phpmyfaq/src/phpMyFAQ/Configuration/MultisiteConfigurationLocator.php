<?php

declare(strict_types=1);

/**
 * Multisite configuration locator class
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
 * @since     2025-06-19
 */

namespace phpMyFAQ\Configuration;

use Symfony\Component\HttpFoundation\Request;

class MultisiteConfigurationLocator
{
    public static function locateConfigurationDirectory(Request $request, string $configurationDirectory): ?string
    {
        $protocol = $request->isSecure() ? 'https' : 'http';
        $host = $request->getHost();
        $scriptName = $request->getScriptName();

        $parsed = parse_url($protocol . '://' . $host . $scriptName);

        if (isset($parsed['host']) && strlen($parsed['host']) > 0) {
            $configDir = rtrim($configurationDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $parsed['host'];

            if (is_dir($configDir)) {
                return $configDir;
            }
        }

        return null;
    }
}
