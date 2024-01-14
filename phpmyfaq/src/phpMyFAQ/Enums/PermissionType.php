<?php

/**
 * Permission type enums
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-01-02
 */

namespace phpMyFAQ\Enums;

enum PermissionType: string
{
    case ATTACHMENT_ADD = 'addattachment';

    case ATTACHMENT_DELETE = 'delattachment';

    case BACKUP = 'backup';

    case CATEGORY_ADD = 'addcateg';

    case CATEGORY_DELETE = 'delcateg';

    case CATEGORY_EDIT = 'editcateg';

    case COMMENT_DELETE = 'delcomment';

    case CONFIGURATION_EDIT = 'editconfig';

    case EXPORT = 'export';

    case FAQ_ADD = 'addfaq';

    case FAQ_APPROVE = 'approverec';

    case FAQ_DELETE = 'delete_faq';

    case FAQ_EDIT = 'edit_faq';

    case GLOSSARY_ADD = 'addglossary';

    case GLOSSARY_DELETE = 'delglossary';

    case GLOSSARY_EDIT = 'editglossary';

    case GROUP_ADD = 'addgroup';

    case GROUP_DELETE = 'delgroup';

    case GROUP_EDIT = 'editgroup';

    case INSTANCE_ADD = 'addinstances';

    case INSTANCE_DELETE = 'delinstances';

    case INSTANCE_EDIT = 'editinstances';

    case QUESTION_DELETE = 'delquestion';

    case REPORTS = 'reports';

    case RESTORE = 'restore';

    case STATISTICS_VIEWLOGS = 'viewlog';

    case USER_ADD = 'add_user';

    case USER_DELETE = 'delete_user';
}
