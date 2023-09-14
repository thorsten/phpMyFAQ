<?php

/**
 * Administration frontend for Tags.
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

use phpMyFAQ\Component\Alert;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Tags;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
  <h1 class="h2">
    <i aria-hidden="true" class="fa fa-tags"></i>
      <?= Translation::get('ad_entry_tags') ?>
  </h1>
</div>

<div class="row">
  <div class="col-lg-12">
    <form action="" method="post" id="tag-form">
        <?= Token::getInstance()->getTokenInput('tags') ?>
        <?php
        if ($user->perm->hasPermission($user->getUserId(), 'edit_faq')) {
            $tags = new Tags($faqConfig);

            if ('delete-tag' === $action) {
                $tagId = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
                if ($tags->deleteTag($tagId)) {
                    echo Alert::success('ad_tag_delete_success');
                } else {
                    echo Alert::danger('ad_tag_delete_error', $faqConfig->getDb()->error());
                }
            }

            $tagData = $tags->getAllTags();

            if (count($tagData) === 0) {
                echo Alert::warning('ad_news_nodata');
            }

            echo '<table class="table table-hover align-middle">';
            echo '<tbody>';

            foreach ($tagData as $key => $tag) {
                echo '<tr>';
                echo '<td><span id="tag-id-' . $key . '">' . Strings::htmlentities($tag) . '</span></td>';
                printf(
                    '<td><a class="btn btn-primary btn-edit" data-btn-id="%d" title="%s">' .
                    '<i aria-hidden="true" class="fa fa-edit" data-btn-id="%d"></i></a></td>',
                    $key,
                    Translation::get('ad_user_edit'),
                    $key,
                );

                printf(
                    '<td><a class="btn btn-danger" onclick="return confirm(\'%s\');" href="%s%d">',
                    Translation::get('ad_user_del_3'),
                    '?action=delete-tag&amp;id=',
                    $key
                );
                printf(
                    '<span title="%s"><i aria-hidden="true" class="fa fa-trash"></i></span></a></td>',
                    Translation::get('ad_entry_delete')
                );

                echo '<tr>';
            }

            echo '</tbody>';
            echo '</table>';
        } else {
            echo Translation::get('err_NotAuth');
        }
        ?>
    </form>
  </div>
</div>
