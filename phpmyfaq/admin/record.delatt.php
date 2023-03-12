<?php

/**
 * Deletes an attachment.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-24
 */

use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Component\Alert;
use phpMyFAQ\Filter;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

printf(
    '<header><h2 class="page-header"><i aria-hidden="true" class="fa fa-pencil"></i> %s</h2></header>',
    Translation::get('ad_entry_aor')
);

if ($user->perm->hasPermission($user->getUserId(), 'delattachment')) {
    $recordId = Filter::filterInput(INPUT_GET, 'record_id', FILTER_VALIDATE_INT);
    $recordLang = Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_SPECIAL_CHARS);
    $id = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    try {
        $att = AttachmentFactory::create($id);
        if ($att && $att->delete()) {
            echo Alert::success('ad_att_delsuc');
        }
    } catch (AttachmentException) {
        echo Alert::danger('ad_att_delfail');
    }

    printf(
        '<p><a href="?action=editentry&amp;id=%d&amp;lang=%s">%s</a></p>',
        $recordId,
        $recordLang,
        Translation::get('ad_entry_back')
    );
} else {
    print Translation::get('err_NotAuth');
}
