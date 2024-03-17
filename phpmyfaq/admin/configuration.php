<?php

/**
 * The main configuration frontend.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2005-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-12-26
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

if ($user->perm->hasPermission($user->getUserId(), 'editconfig')) {
    // actions defined by url: user_action=
    $userAction = Filter::filterInput(INPUT_GET, 'config_action', FILTER_SANITIZE_SPECIAL_CHARS, 'listConfig');
    $csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);

    // Save the configuration
    if ('saveConfig' === $userAction && Token::getInstance()->verifyToken('configuration', $csrfToken)) {
        $checks = [
            'filter' => FILTER_SANITIZE_SPECIAL_CHARS,
            'flags' => FILTER_REQUIRE_ARRAY,
        ];
        $editData = Filter::filterInputArray(INPUT_POST, ['edit' => $checks]);
        $userAction = 'listConfig';
        $oldConfigValues = $faqConfig->getAll();

        // Set the new values
        $forbiddenValues = ['{', '}'];
        $newConfigValues = [];
        $escapeValues = [
            'main.contactInformation',
            'main.customPdfHeader',
            'main.customPdfFooter',
            'main.titleFAQ',
            'main.metaKeywords'
        ];

        // Special checks
        if (isset($editData['edit']['main.enableMarkdownEditor'])) {
            $editData['edit']['main.enableWysiwygEditor'] = false; // Disable WYSIWYG editor if Markdown is enabled
        }
        if (isset($editData['edit']['main.currentVersion'])) {
            unset($editData['edit']['main.currentVersion']); // don't update the version number
        }
        if (isset($editData['edit']['records.attachmentsPath'])) {
            $editData['edit']['records.attachmentsPath'] = str_replace(
                '../',
                '',
                $editData['edit']['records.attachmentsPath']
            );
        }

        if (
            isset($editData['edit']['main.referenceURL']) &&
            is_null(Filter::filterVar($editData['edit']['main.referenceURL'], FILTER_VALIDATE_URL))
        ) {
            unset($editData['edit']['main.referenceURL']);
        }

        foreach ($editData['edit'] as $key => $value) {
            // Remove forbidden characters
            $newConfigValues[$key] = str_replace($forbiddenValues, '', (string) $value);
            // Escape some values
            if (isset($escapeValues[$key])) {
                $newConfigValues[$key] = Strings::htmlspecialchars($value, ENT_QUOTES);
            }
            $keyArray = array_values(explode('.', (string) $key));
            $newConfigClass = array_shift($keyArray);
        }

        foreach ($oldConfigValues as $key => $value) {
            $keyArray = array_values(explode('.', (string) $key));
            $oldConfigClass = array_shift($keyArray);
            if (isset($newConfigValues[$key])) {
                continue;
            } else {
                if ($oldConfigClass === $newConfigClass && $oldConfigValues[$key] === 'true') {
                    $newConfigValues[$key] = 'false';
                } else {
                    $newConfigValues[$key] = $oldConfigValues[$key];
                }
            }
        }

        $faqConfig->update($newConfigValues);

        $faqConfig->getAll();
    }
    ?>
  <form id="configuration-list" name="configuration-list" method="post"
        action="?action=config&amp;config_action=saveConfig">
      <?= Token::getInstance()->getTokenInput('configuration') ?>

    <div
      class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
      <h1 class="h2">
        <i aria-hidden="true" class="fa fa-wrench"></i>
          <?= Translation::get('ad_config_edit') ?>
      </h1>
      <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group mr-2">
          <button class="btn btn-sm btn-warning" type="reset">
              <?= Translation::get('ad_config_reset') ?>
          </button>
          <button class="btn btn-sm btn-success" type="submit">
              <?= Translation::get('ad_config_save') ?>
          </button>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-12">

        <ul class="nav nav-tabs" role="tablist">
          <li role="presentation" class="nav-item">
            <a href="#main" aria-controls="main" role="tab" data-bs-toggle="tab" class="nav-link active">
              <i aria-hidden="true" class="fa fa-home"></i>
                <?= Translation::get('mainControlCenter') ?>
            </a>
          </li>
          <li role="presentation" class="nav-item">
            <a href="#records" aria-controls="records" role="tab" data-bs-toggle="tab" class="nav-link">
              <i aria-hidden="true" class="fa fa-th-list"></i>
                <?= Translation::get('recordsControlCenter') ?>
            </a>
          </li>
          <li role="presentation" class="nav-item">
            <a href="#search" aria-controls="search" role="tab" data-bs-toggle="tab" class="nav-link">
              <i aria-hidden="true" class="fa fa-search"></i>
                <?= Translation::get('searchControlCenter') ?>
            </a>
          </li>
          <li role="presentation" class="nav-item">
            <a href="#security" aria-controls="security" role="tab" data-bs-toggle="tab" class="nav-link">
              <i aria-hidden="true" class="fa fa-warning"></i>
                <?= Translation::get('securityControlCenter') ?>
            </a>
          </li>
          <li role="presentation" class="nav-item">
            <a href="#spam" aria-controls="spam" role="tab" data-bs-toggle="tab" class="nav-link">
              <i aria-hidden="true" class="fa fa-thumbs-down"></i>
                <?= Translation::get('spamControlCenter') ?>
            </a>
          </li>
          <li role="presentation" class="nav-item">
            <a href="#seo" aria-controls="seo" role="tab" data-bs-toggle="tab" class="nav-link">
              <i aria-hidden="true" class="fa fa-search"></i>
                <?= Translation::get('seoCenter') ?>
            </a>
          </li>
          <li role="presentation" class="nav-item">
            <a href="#social" aria-controls="social" role="tab" data-bs-toggle="tab" class="nav-link">
              <i aria-hidden="true" class="fa fa-retweet"></i>
                <?= Translation::get('socialNetworksControlCenter') ?>
            </a>
          </li>
          <li role="presentation" class="nav-item">
            <a href="#mail" aria-controls="mail" role="tab" data-bs-toggle="tab" class="nav-link">
              <i aria-hidden="true" class="fa fa-inbox"></i>
                <?= Translation::get('mailControlCenter') ?>
            </a>
          </li>
          <li role="presentation" class="nav-item">
            <a href="#ldap" aria-controls="ldap" role="tab" data-bs-toggle="tab" class="nav-link">
              <i aria-hidden="true" class="fa fa-sitemap"></i>
                LDAP
            </a>
          </li>
          <li role="presentation" class="nav-item">
            <a href="#api" aria-controls="ldap" role="tab" data-bs-toggle="tab" class="nav-link">
              <i aria-hidden="true" class="fa fa-gears"></i>
                API
            </a>
          </li>
        </ul>

        <div class="tab-content p-2 pt-4 pmf-configuration-panel">
          <div role="tabpanel" class="tab-pane fade show active" id="main"></div>
          <div role="tabpanel" class="tab-pane fade" id="records"></div>
          <div role="tabpanel" class="tab-pane fade" id="search"></div>
          <div role="tabpanel" class="tab-pane fade" id="security"></div>
          <div role="tabpanel" class="tab-pane fade" id="spam"></div>
          <div role="tabpanel" class="tab-pane fade" id="seo"></div>
          <div role="tabpanel" class="tab-pane fade" id="social"></div>
          <div role="tabpanel" class="tab-pane fade" id="mail"></div>
          <div role="tabpanel" class="tab-pane fade" id="ldap"></div>
          <div role="tabpanel" class="tab-pane fade" id="api"></div>
        </div>
      </div>
    </div>

  </form>
    <?php
} else {
    echo Translation::get('err_NotAuth');
}
