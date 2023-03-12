<?php

/**
 * The main glossary index file.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-15
 */

use phpMyFAQ\Component\Alert;
use phpMyFAQ\Filter;
use phpMyFAQ\Glossary;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i aria-hidden="true" class="fa fa-list-ul"></i> <?= Translation::get('ad_menu_glossary') ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group mr-2">
            <a href="?action=addglossary">
                <button class="btn btn-sm btn-success">
                    <i aria-hidden="true" class="fa fa-plus"></i> <?= Translation::get('ad_glossary_add') ?>
                </button>
            </a>
        </div>
    </div>
</div>

<div class="row">
  <div class="col-lg-12">
      <?php
        $csrfTokenFromPost = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);
        $csrfTokenFromGet = Filter::filterInput(INPUT_GET, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);

        if (
            $user->perm->hasPermission($user->getUserId(), 'addglossary') ||
            $user->perm->hasPermission($user->getUserId(), 'editglossary') ||
            $user->perm->hasPermission($user->getUserId(), 'delglossary')
        ) {
            $glossary = new Glossary($faqConfig);

            if (
                'saveglossary' == $action && $user->perm->hasPermission($user->getUserId(), 'addglossary') &&
                Token::getInstance()->verifyToken('add-glossary', $csrfTokenFromPost)
            ) {
                $item = Filter::filterInput(INPUT_POST, 'item', FILTER_SANITIZE_SPECIAL_CHARS);
                $definition = Filter::filterInput(INPUT_POST, 'definition', FILTER_SANITIZE_SPECIAL_CHARS);
                if ($glossary->addGlossaryItem($item, $definition)) {
                    echo Alert::success('ad_glossary_save_success');
                } else {
                    echo Alert::danger('ad_glossary_save_error', $faqConfig->getDb()->error());
                }
            }

            if (
                'updateglossary' == $action && $user->perm->hasPermission($user->getUserId(), 'editglossary') &&
                Token::getInstance()->verifyToken('edit-glossary', $csrfTokenFromPost)
            ) {
                $id = Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
                $item = Filter::filterInput(INPUT_POST, 'item', FILTER_SANITIZE_SPECIAL_CHARS);
                $definition = Filter::filterInput(INPUT_POST, 'definition', FILTER_SANITIZE_SPECIAL_CHARS);
                if ($glossary->updateGlossaryItem($id, $item, $definition)) {
                    echo Alert::success('ad_glossary_update_success');
                } else {
                    echo Alert::danger('ad_glossary_update_error', $faqConfig->getDb()->error());
                }
            }

            if (
                'deleteglossary' == $action && $user->perm->hasPermission($user->getUserId(), 'editglossary') &&
                Token::getInstance()->verifyToken('delete-glossary', $csrfTokenFromGet)
            ) {
                $id = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
                if ($glossary->deleteGlossaryItem($id)) {
                    echo Alert::success('ad_glossary_delete_success');
                } else {
                    echo Alert::danger('ad_glossary_delete_error', $faqConfig->getDb()->error());
                }
            }

            $glossaryItems = $glossary->getAllGlossaryItems();

            echo '<table class="table table-striped align-middle">';
            printf(
                '<thead><tr><th>%s</th><th>%s</th><th style="width: 16px">&nbsp;</th></tr></thead>',
                Translation::get('ad_glossary_item'),
                Translation::get('ad_glossary_definition')
            );

            foreach ($glossaryItems as $items) {
                echo '<tr>';
                printf(
                    '<td><a href="%s%d">%s</a></td>',
                    '?action=editglossary&amp;id=',
                    $items['id'],
                    $items['item']
                );
                printf(
                    '<td>%s</td>',
                    $items['definition']
                );
                printf(
                    '<td><a class="btn btn-danger" onclick="return confirm(\'%s\');" href="%s%d%s%s">',
                    Translation::get('ad_user_del_3'),
                    '?action=deleteglossary&amp;id=',
                    $items['id'],
                    '&csrf=',
                    Token::getInstance()->getTokenString('delete-glossary')
                );
                printf(
                    '<span title="%s"><i aria-hidden="true" class="fa fa-trash"></i></span></a></td>',
                    Translation::get('ad_entry_delete')
                );
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo Translation::get('err_NotAuth');
        }
        ?>
  </div>
</div>
