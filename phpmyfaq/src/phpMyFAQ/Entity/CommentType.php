<?php

/**
 * The CommentType class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Entity
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2019-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-12-28
 */

declare(strict_types=1);

namespace phpMyFAQ\Entity;

/**
 * Class CommentType
 * @package phpMyFAQ\Entity
 */
class CommentType
{
    final public const string FAQ = 'faq';

    final public const string NEWS = 'news';
}
