<?php

/**
 * Handler for queued exports.
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
 * @since     2026-02-11
 */

declare(strict_types=1);

namespace phpMyFAQ\Queue\Handler;

use phpMyFAQ\Queue\Message\ExportMessage;

final readonly class ExportHandler
{
    public function __construct(
        private mixed $callback = null,
    ) {
    }

    public function __invoke(ExportMessage $message): void
    {
        if (is_callable($this->callback)) {
            ($this->callback)($message);
        }
    }
}
