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
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-04-01
 */

use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}
?>

  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
      <i aria-hidden="true" class="fa fa-wrench"></i>
        <?= Translation::get('ad_menu_stopwordsconfig') ?>
    </h1>
  </div>

<?php
if ($user->perm->hasPermission($user->getUserId(), 'editconfig')) {
    $sortedLanguageCodes = LanguageCodes::getAll();
    asort($sortedLanguageCodes);
    reset($sortedLanguageCodes);
    ?>
  <div class="row">
    <div class="col-lg-12">
      <p>
          <?= Translation::get('ad_stopwords_desc') ?>
      </p>
        <form class="row row-cols-lg-auto g-3 align-items-center">
            <?= Token::getInstance()->getTokenInput('stopwords') ?>

            <div class="col-12">
                <label class="visually-hidden" for="pmf-stop-words-language-selector">
                    <?= Translation::get('ad_stopwords_desc') ?>
                </label>
                <select id="pmf-stop-words-language-selector" class="form-select">
                    <option value="none">---</option>
                    <?php foreach ($sortedLanguageCodes as $key => $value) { ?>
                        <option value="<?= strtolower($key) ?>"><?= $value ?></option>
                    <?php } ?>
                </select>
                <span id="pmf-stop-words-loading-indicator"></span>
            </div>

            <div class="col-12">
                <button class="btn btn-primary" type="button" id="pmf-stop-words-add-input" disabled>
                    <i aria-hidden="true" class="fa fa-plus"></i> <?= Translation::get('ad_config_stopword_input') ?>
                </button>
            </div>
      </form>
    </div>
  </div>

    <div class="row">
        <div class="col-12">
            <div class="mt-3" id="pmf-stopwords-content"></div>
        </div>
    </div>

    <?php
} else {
    echo Translation::get('err_NotAuth');
}
