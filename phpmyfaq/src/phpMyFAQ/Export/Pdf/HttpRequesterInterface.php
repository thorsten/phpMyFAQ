<?php

/**
 * Issues a single, non-redirecting HTTP(S) request.
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
 * @since     2026-08-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Export\Pdf;

/**
 * Interface HttpRequesterInterface
 *
 * @package phpMyFAQ\Export\Pdf
 */
interface HttpRequesterInterface
{
    /**
     * @return array{0: int, 1: string[], 2: false|string} Status code, response headers, and body.
     */
    public function request(string $url): array;
}
