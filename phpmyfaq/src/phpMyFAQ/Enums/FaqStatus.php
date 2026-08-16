<?php

/**
 * The FAQ editorial status enum.
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
 * @since     2026-08-16
 */

declare(strict_types=1);

namespace phpMyFAQ\Enums;

enum FaqStatus: string
{
    case Draft = 'draft';
    case Review = 'review';
    case Published = 'published';

    /**
     * Any transition that makes content go live or takes it down is gated by the
     * publish right; moving between the editorial states only needs the edit right.
     */
    public function transitionRequiresPublishRight(FaqStatus $target): bool
    {
        return $this === self::Published || $target === self::Published;
    }
}
