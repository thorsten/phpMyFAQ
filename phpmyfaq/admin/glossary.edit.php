<?php

/**
 * Displays a form to edit an existing glossary item.
 *
 * @todo Move code to a modal window
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2005-09-15
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Glossary;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}
?>
<header class="row">
  <div class="col-lg-12">
    <h2 class="page-header">
      <i aria-hidden="true" class="fa fa-list-ul"></i> <?= $PMF_LANG['ad_glossary_edit'] ?>
    </h2>
  </div>
</header>

<div class="row">
  <div class="col-lg-12">
      <?php
        if ($user->perm->hasPermission($user->getUserId(), 'editglossary')) {
            $id = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            $glossary = new Glossary($faqConfig);
            $glossaryItem = $glossary->getGlossaryItem($id);
            ?>
        <form action="?action=updateglossary" method="post" accept-charset="utf-8">
          <input type="hidden" name="csrf" value="<?= $user->getCsrfTokenFromSession() ?>">
          <input type="hidden" name="id" value="<?= $glossaryItem['id'] ?>">
          <div class="form-group row">
            <label class="col-lg-2 col-form-label" for="item"><?= $PMF_LANG['ad_glossary_item'] ?>:</label>
            <div class="col-lg-4">
              <input class="form-control" type="text" name="item" id="item"
                     value="<?= $glossaryItem['item'] ?>" required>
            </div>
          </div>

          <div class="control-group">
            <label class="col-form-label" for="definition">
                <?= $PMF_LANG['ad_glossary_definition'] ?>:
            </label>
            <div class="controls">
              <textarea class="input-xxlarge" name="definition" id="definition" cols="50" rows="3" required
              ><?= $glossaryItem['definition'] ?></textarea>
            </div>
          </div>

          <div class="form-group row">
            <div class="offset-lg-2 col-lg-4">
              <a class="btn btn-info" href="?action=glossary">
                  <?= $PMF_LANG['ad_entry_back'] ?>
              </a>
              <button class="btn btn-primary" type="submit">
              <?= $PMF_LANG['ad_glossary_save'] ?>
              </button>
            </div>
          </div>
        </form>
            <?php
        } else {
            echo $PMF_LANG['err_NotAuth'];
        }
        ?>
  </div>
</div>
