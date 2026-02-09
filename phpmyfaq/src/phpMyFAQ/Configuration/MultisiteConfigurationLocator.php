<?php

/**
 * Multisite configuration locator class
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
 * @since     2025-06-19
 */

declare(strict_types=1);

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

        if (isset($parsed['host']) && $parsed['host'] !== '') {
            // 1. Try an exact hostname match (existing behavior)
            $configDir = rtrim($configurationDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $parsed['host'];

            if (is_dir($configDir)) {
                return $configDir;
            }

            // 2. Try subdomain-based tenant matching
            $tenantName = self::extractTenantFromSubdomain($parsed['host']);
            if ($tenantName !== null) {
                $configDir = rtrim($configurationDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $tenantName;

                if (is_dir($configDir)) {
                    return $configDir;
                }
            }
        }

        return null;
    }

    /**
     * Extracts the tenant identifier from a subdomain pattern.
     *
     * Checks the PMF_MULTISITE_BASE_DOMAIN environment variable. If set,
     * extracts the subdomain part from hostnames matching {tenant}.{baseDomain}.
     *
     * Example: With PMF_MULTISITE_BASE_DOMAIN=faq.example.com,
     * the host "acme.faq.example.com" returns "acme".
     */
    public static function extractTenantFromSubdomain(string $host): ?string
    {
        $host = strtolower($host);
        $baseDomain = getenv('PMF_MULTISITE_BASE_DOMAIN');
        if ($baseDomain === false || $baseDomain === '') {
            return null;
        }

        $baseDomain = strtolower(ltrim($baseDomain, characters: '.'));
        $suffix = '.' . $baseDomain;

        if (!str_ends_with($host, $suffix)) {
            return null;
        }

        $tenant = strtolower(substr($host, offset: 0, length: -strlen($suffix)));
        if ($tenant === '' || str_contains($tenant, '.')) {
            return null;
        }

        return $tenant;
    }
}
