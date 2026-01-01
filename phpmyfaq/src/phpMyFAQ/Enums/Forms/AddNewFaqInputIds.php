<?php

/**
 * Ids of inputIds of the Add new faq form
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <modelrailroader@gmx-topmail.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-21
 */

declare(strict_types=1);

namespace phpMyFAQ\Enums\Forms;

enum AddNewFaqInputIds: int
{
    case TITLE = 1;

    case MESSAGE = 2;

    case NAME = 3;

    case EMAIL = 4;

    case CATEGORY = 5;

    case QUESTION = 6;

    case ANSWER = 7;

    case KEYWORDS = 8;
}
