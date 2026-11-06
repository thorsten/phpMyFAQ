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
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-11-03
 */

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

use phpMyFAQ\Configuration as CoreConfiguration;
use Symfony\Component\HttpFoundation\Request;

readonly class UrlSettings
{
    /** @var string[] */
    private const array ALLOWED_SCHEMES = ['http', 'https'];

    public function __construct(
        private CoreConfiguration $configuration,
    ) {
    }

    public function getDefaultUrl(): string
    {
        $defaultUrl = (string) $this->configuration->get(item: 'main.referenceURL');

        // Defense in depth: never hand out a non-http(s) URL as the FAQ base URL, it is rendered
        // unescaped into href attributes (see CVE-2023-5320). Fall back to the request origin.
        if (!self::isAllowedReferenceUrl($defaultUrl)) {
            $defaultUrl = Request::createFromGlobals()->getSchemeAndHttpHost();
        }

        return str_ends_with($defaultUrl, '/') ? $defaultUrl : $defaultUrl . '/';
    }

    /**
     * Returns true if the given value is an absolute http or https URL with a host.
     * FILTER_VALIDATE_URL alone is not enough: it accepts authority-form URLs of any scheme,
     * e.g. "javascript://x/%0Aalert(1)//".
     */
    public static function isAllowedReferenceUrl(string $url): bool
    {
        if ($url !== trim($url) || preg_match('/[\x00-\x20\x7f]/', $url) === 1) {
            return false;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        return (
            is_string($scheme)
            && is_string($host)
            && $host !== ''
            && in_array(strtolower($scheme), self::ALLOWED_SCHEMES, strict: true)
        );
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
