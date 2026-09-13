<?php

/**
 * Scopes an API key can be minted with.
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

namespace phpMyFAQ\Enums;

/**
 * An API key acts on behalf of its owner, so a scope can only be granted when the owner
 * actually holds the permission it stands for. Unknown scope strings are rejected at
 * mint time instead of being stored verbatim.
 */
enum ApiKeyScope: string
{
    case FAQ_READ = 'faq.read';

    case FAQ_WRITE = 'faq.write';

    case CATEGORY_READ = 'category.read';

    case CATEGORY_WRITE = 'category.write';

    case NEWS_READ = 'news.read';

    case NEWS_WRITE = 'news.write';

    case ATTACHMENT_READ = 'attachment.read';

    case COMMENT_WRITE = 'comment.write';

    case QUESTION_WRITE = 'question.write';

    case GROUP_READ = 'group.read';

    case USER_READ = 'user.read';

    /**
     * The permission the key owner has to hold to be granted this scope.
     */
    public function requiredPermission(): PermissionType
    {
        return match ($this) {
            self::FAQ_READ => PermissionType::FAQS_VIEW,
            self::FAQ_WRITE => PermissionType::FAQ_EDIT,
            self::CATEGORY_READ => PermissionType::CATEGORIES_VIEW,
            self::CATEGORY_WRITE => PermissionType::CATEGORY_EDIT,
            self::NEWS_READ => PermissionType::NEWS_VIEW,
            self::NEWS_WRITE => PermissionType::NEWS_EDIT,
            self::ATTACHMENT_READ => PermissionType::ATTACHMENT_DOWNLOAD,
            self::COMMENT_WRITE => PermissionType::COMMENT_ADD,
            self::QUESTION_WRITE => PermissionType::QUESTION_ADD,
            self::GROUP_READ => PermissionType::GROUP_EDIT,
            self::USER_READ => PermissionType::USER_EDIT,
        };
    }
}
