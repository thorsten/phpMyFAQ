<?php

/**
 * The main configuration frontend.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2005-2022 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2005-12-26
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Strings;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

if ($user->perm->hasPermission($user->getUserId(), 'editconfig')) {
    // actions defined by url: user_action=
    $userAction = Filter::filterInput(INPUT_GET, 'config_action', FILTER_UNSAFE_RAW, 'listConfig');
    $csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_UNSAFE_RAW);
    $currentToken = $user->getCsrfTokenFromSession();

    // Save the configuration
    if ('saveConfig' === $userAction && $currentToken === $csrfToken) {
        $checks = [
            'filter' => FILTER_UNSAFE_RAW,
            'flags' => FILTER_REQUIRE_ARRAY,
        ];
        $editData = Filter::filterInputArray(INPUT_POST, ['edit' => $checks]);
        $userAction = 'listConfig';
        $oldConfigValues = $faqConfig->config;

        // Set the new values
        $forbiddenValues = ['{', '}'];
        $newConfigValues = [];
        $escapeValues = [
            'main.contactInformations',
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

        foreach ($editData['edit'] as $key => $value) {
            // Remove forbidden characters
            $newConfigValues[$key] = str_replace($forbiddenValues, '', $value);
            // Escape some values
            if (isset($escapeValues[$key])) {
                $newConfigValues[$key] = Strings::htmlspecialchars($value, ENT_QUOTES);
            }
            $keyArray = array_values(explode('.', $key));
            $newConfigClass = array_shift($keyArray);
        }

        foreach ($oldConfigValues as $key => $value) {
            $keyArray = array_values(explode('.', $key));
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

        if (!is_null($editData)) {
            $faqConfig->update($newConfigValues);
        }

        $faqConfig->getAll();
    } elseif ('saveConfig' === $userAction && $currentToken !== $csrfToken) {
        echo '<div class="alert alert-danger">Error: CSRF Token mismatch!</div>';
    }
    ?>
  <form id="config_list" name="config_list" method="post"
        action="?action=config&amp;config_action=saveConfig">
    <input type="hidden" name="csrf" value="<?= $currentToken ?>">

    <div
      class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
      <h1 class="h2">
        <i aria-hidden="true" class="fa fa-wrench"></i>
          <?= $PMF_LANG['ad_config_edit'] ?>
      </h1>
      <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group mr-2">
          <button class="btn btn-sm btn-warning" type="reset">
              <?= $PMF_LANG['ad_config_reset'] ?>
          </button>
          <button class="btn btn-sm btn-success" type="submit">
              <?= $PMF_LANG['ad_config_save'] ?>
          </button>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-12">

        <ul class="nav nav-tabs" role="tablist">
          <li role="presentation" class="nav-item">
            <a href="#main" aria-controls="main" role="tab" data-toggle="tab" class="nav-link active">
              <i aria-hidden="true" class="fa fa-home"></i>
                <?= $PMF_LANG['mainControlCenter'] ?>
            </a>
          </li>
          <li role="presentation" class="nav-item">
            <a href="#records" aria-controls="records" role="tab" data-toggle="tab" class="nav-link">
              <i aria-hidden="true" class="fa fa-th-list"></i>
                <?= $PMF_LANG['recordsControlCenter'] ?>
            </a>
          </li>
          <li role="presentation" class="nav-item">
            <a href="#search" aria-controls="search" role="tab" data-toggle="tab" class="nav-link">
              <i aria-hidden="true" class="fa fa-search"></i>
                <?= $PMF_LANG['searchControlCenter'] ?>
            </a>
          </li>
          <li role="presentation" class="nav-item">
            <a href="#security" aria-controls="security" role="tab" data-toggle="tab" class="nav-link">
              <i aria-hidden="true" class="fa fa-warning"></i>
                <?= $PMF_LANG['securityControlCenter'] ?>
            </a>
          </li>
          <li role="presentation" class="nav-item">
            <a href="#spam" aria-controls="spam" role="tab" data-toggle="tab" class="nav-link">
              <i aria-hidden="true" class="fa fa-thumbs-down"></i>
                <?= $PMF_LANG['spamControlCenter'] ?>
            </a>
          </li>
          <li role="presentation" class="nav-item">
            <a href="#seo" aria-controls="seo" role="tab" data-toggle="tab" class="nav-link">
              <i aria-hidden="true" class="fa fa-search"></i>
                <?= $PMF_LANG['seoCenter'] ?>
            </a>
          </li>
          <li role="presentation" class="nav-item">
            <a href="#social" aria-controls="social" role="tab" data-toggle="tab" class="nav-link">
              <i aria-hidden="true" class="fa fa-retweet"></i>
                <?= $PMF_LANG['socialNetworksControlCenter'] ?>
            </a>
          </li>
          <li role="presentation" class="nav-item">
            <a href="#mail" aria-controls="mail" role="tab" data-toggle="tab" class="nav-link">
              <i aria-hidden="true" class="fa fa-inbox"></i>
                <?= $PMF_LANG['mailControlCenter'] ?>
            </a>
          </li>
          <li role="presentation" class="nav-item">
            <a href="#ldap" aria-controls="ldap" role="tab" data-toggle="tab" class="nav-link">
              <i aria-hidden="true" class="fa fa-sitemap"></i>
                <?= 'LDAP' ?>
            </a>
          </li>
          <li role="presentation" class="nav-item">
            <a href="#api" aria-controls="ldap" role="tab" data-toggle="tab" class="nav-link">
              <i aria-hidden="true" class="fa fa-gears"></i>
                <?= 'API' ?>
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

  <script src="assets/js/configuration.js"></script>
    <?php
} else {
    echo $PMF_LANG['err_NotAuth'];
}
