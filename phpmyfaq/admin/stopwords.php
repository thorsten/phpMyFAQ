<?php

/**
 * The main stop words configuration frontend.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2022 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-04-01
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}
?>

  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
      <i aria-hidden="true" class="fa fa-wrench"></i>
        <?= $PMF_LANG['ad_menu_stopwordsconfig'] ?>
    </h1>
  </div>

<?php
if ($user->perm->hasPermission($user->getUserId(), 'editconfig')) {
    $sortedLanguageCodes = $languageCodes;
    asort($sortedLanguageCodes);
    reset($sortedLanguageCodes);
    ?>
  <div class="row">
    <div class="col-lg-12">
      <form>
          <input type="hidden" name="pmf-stop-words-csrf-token" id="pmf-stop-words-csrf-token"
                 value="<?= $user->getCsrfTokenFromSession() ?>">
      <p>
        <?= $PMF_LANG['ad_stopwords_desc'] ?>
      </p>
      <p>
        <label for="pmf-stop-words-language-selector"><?= $PMF_LANG['ad_entry_locale'] ?>:</label>
        <select id="pmf-stop-words-language-selector">
          <option value="none">---</option>
            <?php foreach ($sortedLanguageCodes as $key => $value) { ?>
              <option value="<?= strtolower($key) ?>"><?= $value ?></option>
            <?php } ?>
        </select>
        <span id="pmf-stop-words-loading-indicator"></span>
      </p>

      <div class="mb-3" id="pmf-stopwords-content"></div>

      <button class="btn btn-primary" type="button" id="pmf-stop-words-add-input" disabled>
          <i aria-hidden="true" class="fa fa-plus"></i> <?= $PMF_LANG['ad_config_stopword_input'] ?>
      </button>

      </form>
    </div>
  </div>
    <?php
} else {
    echo $PMF_LANG['err_NotAuth'];
}
