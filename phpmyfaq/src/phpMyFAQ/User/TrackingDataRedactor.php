<?php

/**
 * Redacts sensitive parameters from query strings and referer URLs before they
 * are written to the user tracking logs.
 *
 * The tracking logs record the raw request query string and referer. Without
 * redaction these can leak secrets that travel in the URL, most notably the
 * signature of a password reset link (?sig=...), which would allow account
 * takeover if the log file were ever read.
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
 * @since     2026-08-03
 */

declare(strict_types=1);

namespace phpMyFAQ\User;

final readonly class TrackingDataRedactor
{
    /** @var string Placeholder written in place of a sensitive value */
    public const string REDACTED = '[redacted]';

    /**
     * Lowercase names of query parameters whose values must never be logged.
     *
     * @var list<string>
     */
    private const array SENSITIVE_PARAMETERS = [
        'sig',
        'signature',
        'token',
        'csrf',
        'csrftoken',
        'key',
        'secret',
        'password',
        'passwd',
        'pwd',
        'pass',
        'auth',
        'apikey',
        'api_key',
        'access_token',
    ];

    /**
     * Redacts the values of sensitive parameters in a raw query string while
     * preserving parameter order and the values of non-sensitive parameters.
     */
    public function redactQueryString(string $queryString): string
    {
        if ($queryString === '') {
            return '';
        }

        $pairs = explode('&', $queryString);
        foreach ($pairs as $index => $pair) {
            $separatorPosition = strpos($pair, needle: '=');
            if ($separatorPosition === false) {
                continue;
            }

            $name = substr($pair, offset: 0, length: $separatorPosition);
            if (!$this->isSensitive($name)) {
                continue;
            }

            $pairs[$index] = $name . '=' . self::REDACTED;
        }

        return implode('&', $pairs);
    }

    /**
     * Redacts sensitive parameters in the query part of a URL (e.g. a referer),
     * leaving the scheme, host, path and any fragment untouched.
     */
    public function redactUrl(string $url): string
    {
        $queryPosition = strpos($url, needle: '?');
        if ($queryPosition === false) {
            return $url;
        }

        $base = substr($url, offset: 0, length: $queryPosition + 1);
        $query = substr($url, offset: $queryPosition + 1);

        $fragment = '';
        $fragmentPosition = strpos($query, needle: '#');
        if ($fragmentPosition !== false) {
            $fragment = substr($query, offset: $fragmentPosition);
            $query = substr($query, offset: 0, length: $fragmentPosition);
        }

        return $base . $this->redactQueryString($query) . $fragment;
    }

    private function isSensitive(string $name): bool
    {
        return in_array(strtolower(urldecode($name)), self::SENSITIVE_PARAMETERS, strict: true);
    }
}
