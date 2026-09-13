<?php

/**
 * Writes security-relevant events (denied requests, CSRF failures, rate
 * limiting, uploads) to the application log so that probing and abuse on
 * the public site leaves a trace.
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

namespace phpMyFAQ\Http;

use phpMyFAQ\Enums\AdminLogType;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class SecurityEventLogger
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Logs a security event with the request context (method, path, client IP)
     * and an optional user id. Control characters in the detail are stripped
     * so a crafted value cannot forge additional log lines.
     */
    public function log(AdminLogType $type, Request $request, string $detail, ?int $userId = null): void
    {
        $this->logger->warning(sprintf(
            '%s: %s [method=%s path=%s ip=%s user=%s]',
            $type->value,
            $this->sanitize($detail),
            $request->getMethod(),
            $this->sanitize($request->getPathInfo()),
            $request->getClientIp() ?? 'unknown',
            $userId === null ? 'anonymous' : (string) $userId,
        ));
    }

    private function sanitize(string $value): string
    {
        return preg_replace('/[\x00-\x1f\x7f]+/', replacement: ' ', subject: $value) ?? '';
    }
}
