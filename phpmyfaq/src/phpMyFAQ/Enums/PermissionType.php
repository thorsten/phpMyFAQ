<?php

declare(strict_types=1);

/**
 * Permission type enums
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-01-02
 */

namespace phpMyFAQ\Enums;

enum PermissionType: string
{
    case ATTACHMENT_ADD = 'addattachment';

    case ATTACHMENT_DELETE = 'delattachment';

    case ATTACHMENT_DOWNLOAD = 'dlattachment';

    case ATTACHMENT_EDIT = 'editattachment';

    case BACKUP = 'backup';

    case CATEGORY_ADD = 'addcateg';

    case CATEGORY_DELETE = 'delcateg';

    case CATEGORY_EDIT = 'editcateg';

    case CATEGORIES_VIEW = 'view_categories';

    case COMMENT_ADD = 'addcomment';
    case COMMENT_DELETE = 'delcomment';

    case CONFIGURATION_EDIT = 'editconfig';

    case EXPORT = 'export';

    case FAQ_ADD = 'addfaq';

    case FAQ_APPROVE = 'approverec';

    case FAQ_DELETE = 'delete_faq';

    case FAQ_EDIT = 'edit_faq';

    case FAQ_TRANSLATE = 'translate_faq';

    case FAQS_VIEW = 'view_faqs';

    case FORMS_EDIT = 'forms_edit';

    case GLOSSARY_ADD = 'addglossary';

    case GLOSSARY_DELETE = 'delglossary';

    case GLOSSARY_EDIT = 'editglossary';

    case GROUP_ADD = 'addgroup';

    case GROUPS_ADMINISTRATE = 'administrate_groups';

    case GROUP_DELETE = 'delgroup';

    case GROUP_EDIT = 'editgroup';

    case INSTANCE_ADD = 'addinstances';

    case INSTANCE_DELETE = 'delinstances';

    case INSTANCE_EDIT = 'editinstances';

    case NEWS_ADD = 'addnews';

    case NEWS_EDIT = 'editnews';

    case NEWS_DELETE = 'delnews';

    case NEWS_VIEW = 'view_news';

    case PASSWORD_CHANGE = 'passwd';

    case QUESTION_ADD = 'addquestion';

    case QUESTION_DELETE = 'delquestion';

    case REPORTS = 'reports';

    case RESTORE = 'restore';

    case REVISION_UPDATE = 'changebtrevs';

    case STATISTICS_ADMINLOG = 'adminlog';

    case STATISTICS_VIEWLOGS = 'viewlog';

    case USER_ADD = 'add_user';

    case USER_EDIT = 'edit_user';

    case USER_DELETE = 'delete_user';

    case VIEW_ADMIN_LINK = 'viewadminlink';
}
