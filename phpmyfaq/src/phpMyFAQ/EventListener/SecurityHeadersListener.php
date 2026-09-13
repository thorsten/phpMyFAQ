<?php

/**
 * Response listener adding browser hardening headers to HTML responses
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
 * @since     2026-09-13
 */

declare(strict_types=1);

namespace phpMyFAQ\EventListener;

use phpMyFAQ\Configuration;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Sets X-Content-Type-Options, X-Frame-Options, Referrer-Policy,
 * Permissions-Policy and a Content-Security-Policy on every HTML response.
 * Headers a controller already set are never overwritten.
 *
 * The policy keeps 'unsafe-inline' for scripts and styles because the
 * templates still contain inline scripts and style attributes; moving them
 * to nonces is a separate task. External hosts that phpMyFAQ legitimately
 * loads (Google reCAPTCHA, Gravatar avatars, embedded media from the
 * records.allowedMediaHosts configuration) are allowed explicitly.
 */
final readonly class SecurityHeadersListener
{
    private const array RECAPTCHA_SCRIPT_HOSTS = ['https://www.google.com', 'https://www.gstatic.com'];

    private const array RECAPTCHA_FRAME_HOSTS = ['https://www.google.com'];

    public function __construct(
        private ?Configuration $configuration = null,
    ) {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $headers = $response->headers;

        $this->setIfMissing($response, 'X-Content-Type-Options', 'nosniff');

        if (!$this->isHtmlResponse($response)) {
            return;
        }

        $this->setIfMissing($response, 'X-Frame-Options', 'SAMEORIGIN');
        $this->setIfMissing($response, 'Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->setIfMissing($response, 'Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (!$headers->has('Content-Security-Policy')) {
            $headers->set('Content-Security-Policy', $this->buildContentSecurityPolicy());
        }
    }

    public function buildContentSecurityPolicy(): string
    {
        $mediaHosts = $this->getAllowedMediaOrigins();

        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' " . implode(' ', self::RECAPTCHA_SCRIPT_HOSTS),
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "media-src 'self'" . $this->joinOrigins($mediaHosts),
            "frame-src 'self' " . implode(' ', self::RECAPTCHA_FRAME_HOSTS) . $this->joinOrigins($mediaHosts),
            "frame-ancestors 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        return implode('; ', $directives);
    }

    /**
     * Origins from records.allowedMediaHosts (host names, optionally with a
     * scheme). Anything that is not a plausible host name is dropped so a
     * broken configuration value cannot corrupt the policy.
     *
     * @return string[]
     */
    private function getAllowedMediaOrigins(): array
    {
        if (!$this->configuration instanceof Configuration) {
            return [];
        }

        $origins = [];
        foreach ($this->configuration->getAllowedMediaHosts() as $host) {
            $host = trim($host);
            if ($host === '') {
                continue;
            }

            $withScheme = str_contains($host, '://') ? $host : 'https://' . $host;
            $hostName = parse_url($withScheme, PHP_URL_HOST);
            if (!is_string($hostName) || preg_match('/^[a-z0-9.\-]+$/i', $hostName) !== 1) {
                continue;
            }

            $origins[] = 'https://' . strtolower($hostName);
        }

        return array_values(array_unique($origins));
    }

    /**
     * @param string[] $origins
     */
    private function joinOrigins(array $origins): string
    {
        return $origins === [] ? '' : ' ' . implode(' ', $origins);
    }

    private function isHtmlResponse(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type');

        // Symfony sets text/html on send() when nothing else was given
        if ($contentType === null || $contentType === '') {
            return true;
        }

        return str_starts_with(strtolower($contentType), 'text/html');
    }

    private function setIfMissing(Response $response, string $name, string $value): void
    {
        if ($response->headers->has($name)) {
            return;
        }

        $response->headers->set($name, $value);
    }
}
