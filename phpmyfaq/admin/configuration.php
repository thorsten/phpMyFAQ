<?php
/**
 * The main configuration frontend.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2005-12-26
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($user->perm->checkRight($user->getUserId(), 'editconfig')) {
    // actions defined by url: user_action=
    $userAction = PMF_Filter::filterInput(INPUT_GET, 'config_action', FILTER_SANITIZE_STRING, 'listConfig');
    $csrfToken = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
    $currentToken = $user->getCsrfTokenFromSession();

    // Save the configuration
    if ('saveConfig' === $userAction && $currentToken === $csrfToken) {
        $checks = array(
            'filter' => FILTER_UNSAFE_RAW,
            'flags' => FILTER_REQUIRE_ARRAY,
        );
        $editData = PMF_Filter::filterInputArray(INPUT_POST, array('edit' => $checks));
        $userAction = 'listConfig';
        $oldConfigValues = $faqConfig->config;

        // Set the new values
        $forbiddenValues = ['{', '}', '$'];
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
            $editData['edit']['main.enableWysiwygEditor'] = false; // Disable WYSIWG editor if Markdown is enabled
        }

        foreach ($editData['edit'] as $key => $value) {
            // Remove forbidden characters
            $newConfigValues[$key] = str_replace($forbiddenValues, '', $value);
            // Escape some values
            if (isset($escapeValues[$key])) {
                $newConfigValues[$key] = PMF_String::htmlspecialchars($value, ENT_QUOTES);
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
    }
    ?>
        <form class="form-horizontal" id="config_list" name="config_list" method="post"
              action="?action=config&amp;config_action=saveConfig">
            <input type="hidden" name="csrf" value="<?php echo $currentToken ?>">

            <header class="row">
                <div class="col-lg-12">
                    <h2 class="page-header">
                        <i aria-hidden="true" class="fa fa-wrench fa-fw"></i> <?php echo $PMF_LANG['ad_config_edit'] ?>
                        <div class="pull-right">
                            <button class="btn btn-success" type="submit">
                                <?php echo $PMF_LANG['ad_config_save'] ?>
                            </button>
                            <button class="btn btn-warning" type="reset">
                                <?php echo $PMF_LANG['ad_config_reset'] ?>
                            </button>
                        </div>
                    </h2>
                </div>
            </header>

            <div class="row">
                <div class="col-lg-12">

                    <ul class="nav nav-tabs" role="tablist">
                        <li role="presentation" class="active">
                            <a href="#main" aria-controls="main" role="tab" data-toggle="tab" class="toggleConfig">
                                <i aria-hidden="true" class="fa fa-home"></i>
                                <?php echo $PMF_LANG['mainControlCenter'] ?>
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#records" aria-controls="records" role="tab" data-toggle="tab" class="toggleConfig">
                                <i aria-hidden="true" class="fa fa-th-list"></i>
                                <?php echo $PMF_LANG['recordsControlCenter'] ?>
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#search" aria-controls="search" role="tab" data-toggle="tab" class="toggleConfig">
                                <i aria-hidden="true" class="fa fa-search"></i>
                                <?php echo $PMF_LANG['searchControlCenter'] ?>
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#security" aria-controls="security" role="tab" data-toggle="tab" class="toggleConfig">
                                <i aria-hidden="true" class="fa fa-warning"></i>
                                <?php echo $PMF_LANG['securityControlCenter'] ?>
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#spam" aria-controls="spam" role="tab" data-toggle="tab" class="toggleConfig">
                                <i aria-hidden="true" class="fa fa-thumbs-down"></i>
                                <?php echo $PMF_LANG['spamControlCenter'] ?>
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#seo" aria-controls="seo" role="tab" data-toggle="tab" class="toggleConfig">
                                <i aria-hidden="true" class="fa fa-search"></i>
                                <?php echo $PMF_LANG['seoCenter'] ?>
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#social" aria-controls="social" role="tab" data-toggle="tab" class="toggleConfig">
                                <i aria-hidden="true" class="fa fa-retweet"></i>
                                <?php echo $PMF_LANG['socialNetworksControlCenter'] ?>
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#mail" aria-controls="mail" role="tab" data-toggle="tab" class="toggleConfig">
                                <i aria-hidden="true" class="fa fa-inbox"></i>
                                <?php echo $PMF_LANG['mailControlCenter'] ?>
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" style="margin-top: 20px;">
                        <div role="tabpanel" class="tab-pane fade in active" id="main"></div>
                        <div role="tabpanel" class="tab-pane fade" id="records"></div>
                        <div role="tabpanel" class="tab-pane fade" id="search"></div>
                        <div role="tabpanel" class="tab-pane fade" id="security"></div>
                        <div role="tabpanel" class="tab-pane fade" id="spam"></div>
                        <div role="tabpanel" class="tab-pane fade" id="seo"></div>
                        <div role="tabpanel" class="tab-pane fade" id="social"></div>
                        <div role="tabpanel" class="tab-pane fade" id="mail"></div>
                    </div>
                </div>
            </div>

        </form>

        <script src="assets/js/configuration.js"></script>
<?php

} else {
    echo $PMF_LANG['err_NotAuth'];
}
