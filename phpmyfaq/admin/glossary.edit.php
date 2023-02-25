<?php

/**
 * Displays a form to edit an existing glossary item.
 *
 * @todo Move code to a modal window
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
        <i aria-hidden="true" class="fa fa-list-ul"></i> <?= Translation::get('ad_glossary_edit') ?>
    </h1>
</div>

<div class="row">
    <div class="col-lg-12">
    <?php
    if ($user->perm->hasPermission($user->getUserId(), 'editglossary')) {
        $id = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $glossary = new Glossary($faqConfig);
        $glossaryItem = $glossary->getGlossaryItem($id);
        ?>
        <form action="?action=updateglossary" method="post" accept-charset="utf-8">
            <input type="hidden" name="id" value="<?= $glossaryItem['id'] ?>">
            <?= Token::getInstance()->getTokenInput('edit-glossary') ?>
            <div class="row mb-2">
                <label class="col-lg-2 col-form-label" for="item">
                    <?= Translation::get('ad_glossary_item') ?>:
                </label>
                <div class="col-lg-4">
                    <input class="form-control" type="text" name="item" id="item"
                           value="<?= $glossaryItem['item'] ?>" required>
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-lg-2 col-form-label" for="definition">
                    <?= Translation::get('ad_glossary_definition') ?>:
                </label>
                <div class="col-lg-4">
                <textarea class="form-control" name="definition" id="definition" cols="50" rows="5" required
                ><?= $glossaryItem['definition'] ?></textarea>
                </div>
            </div>

            <div class="row mb-2">
                <div class="offset-lg-2 col-lg-4 text-end">
                    <a class="btn btn-secondary" href="?action=glossary">
                        <?= Translation::get('ad_entry_back') ?>
                    </a>
                    <button class="btn btn-primary" type="submit">
                        <?= Translation::get('ad_glossary_save') ?>
                    </button>
                </div>
            </div>
        </form>
        <?php
    } else {
        echo Translation::get('err_NotAuth');
    }
    ?>
    </div>
</div>
