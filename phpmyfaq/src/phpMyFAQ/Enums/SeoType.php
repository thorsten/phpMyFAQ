<?php

/**
 * SEO type enum
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-06-23
 */

declare(strict_types=1);

namespace phpMyFAQ\Enums;

enum SeoType: string
{
    case CATEGORY = 'category';
    case FAQ = 'faq';
    case PAGE = 'page';
}
