<?php

/**
 * Admin log type enum
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
 * @since     2026-01-04
 */

declare(strict_types=1);

namespace phpMyFAQ\Enums;

enum AdminLogType: string
{
    case BACKUP_EXPORT = 'backup-export';
    case BACKUP_RESTORE = 'backup-restore';
    case FAQ_ADD = 'faq-add';
    case FAQ_EDIT = 'faq-edit';
    case FAQ_COPY = 'faq-copy';
    case FAQ_TRANSLATE = 'faq-translate';
    case FAQ_ANSWER_ADD = 'faq-answer-add';
}
