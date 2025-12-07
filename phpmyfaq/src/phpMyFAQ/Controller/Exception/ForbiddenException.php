<?php

/**
 * Forbidden Exception for phpMyFAQ
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
 * @since     2025-12-07
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ForbiddenException extends HttpException
{
    public function __construct(
        string $message = 'Forbidden',
        ?Throwable $previous = null,
        int $code = 0,
        array $headers = [],
    ) {
        parent::__construct(
            statusCode: 403,
            message: $message,
            previous: $previous,
            headers: $headers,
            code: $code,
        );
    }
}
