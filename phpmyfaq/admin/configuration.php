<?php
/**
 * The main configuration frontend
 *
 * PHP 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2005-2015 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-12-26
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON'){
        $protocol = 'https';
    }
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($user->perm->checkRight($user->getUserId(), 'editconfig')) {
    // actions defined by url: user_action=
    $userAction = PMF_Filter::filterInput(INPUT_GET, 'config_action', FILTER_SANITIZE_STRING, 'listConfig');
    $csrfToken  = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);

    // Save the configuration
    if ('saveConfig' === $userAction && isset($_SESSION['phpmyfaq_csrf_token']) &&
        $_SESSION['phpmyfaq_csrf_token'] === $csrfToken) {

        $checks = array(
            'filter' => FILTER_UNSAFE_RAW,
            'flags'  => FILTER_REQUIRE_ARRAY
        );
        $editData        = PMF_Filter::filterInputArray(INPUT_POST, array('edit' => $checks));
        $message         = '';
        $userAction      = 'listConfig';
        $oldConfigValues = $faqConfig->config;

        // Set the new values
        $forbiddenValues = ['{', '}', '$'];
        $newConfigValues = [];
        $escapeValues    = ['main.contactInformations', 'main.customPdfHeader', 'main.customPdfFooter'];

        // Special checks
        if (isset($editData['edit']['main.enableMarkdownEditor'])) {
            $editData['edit']['main.enableWysiwygEditor'] = false; // Disable WYSIWG editor if Markdown is enabled
        }

        foreach ($editData['edit'] as $key => $value) {
            // Remove forbidden characters
            $newConfigValues[$key] = str_replace($forbiddenValues, '', $value);
            // Escape some values
            if (isset($escapeValues[$key])) {
                $newConfigValues[$key] = PMF_String::htmlspecialchars($value, ENT_HTML5);
            }
            $keyArray              = array_values(explode('.', $key));
            $newConfigClass        = array_shift($keyArray);
        }

        foreach ($oldConfigValues as $key => $value) {
            $keyArray       = array_values(explode('.', $key));
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

        if (! is_null($editData)) {
            $faqConfig->update($newConfigValues);
        }
    }
    // Lists the current configuration
    if ('listConfig' === $userAction) {
        $message    = '';
        $userAction = 'listConfig';
?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header">
                    <i class="fa fa-wrench fa-fw"></i> <?php echo $PMF_LANG['ad_config_edit'] ?>
                </h2>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-12">

                <div id="user_message"><?php echo $message; ?></div>
                <form class="form-horizontal" id="config_list" name="config_list" accept-charset="utf-8"
                      action="?action=config&amp;config_action=saveConfig" method="post">
                    <input type="hidden" name="csrf" value="<?php echo $user->getCsrfTokenFromSession(); ?>">

                    <p>
                        <button class="btn btn-inverse toggleConfig" data-toggle="Main">
                            <i class="fa fa-home"></i>
                            <?php echo $PMF_LANG['mainControlCenter']; ?>
                        </button>
                    </p>
                    <div id="configMain" class="hide"></div>

                    <p>
                        <button class="btn btn-inverse toggleConfig" data-toggle="Records">
                            <i class="fa fa-th-list"></i>
                            <?php echo $PMF_LANG['recordsControlCenter']; ?>
                        </button>
                    </p>
                    <div id="configRecords" class="hide"></div>

                    <p>
                        <button class="btn btn-inverse toggleConfig" data-toggle="Search">
                            <i class="fa fa-search"></i>
                            <?php echo $PMF_LANG['searchControlCenter']; ?>
                        </button>
                    </p>
                    <div id="configSearch" class="hide"></div>

                    <p>
                        <button class="btn btn-inverse toggleConfig" data-toggle="Security">
                            <i class="fa fa-warning"></i>
                            <?php echo $PMF_LANG['securityControlCenter']; ?>
                        </button>
                    </p>
                    <div id="configSecurity" class="hide"></div>

                    <p>
                        <button class="btn btn-inverse toggleConfig"  data-toggle="Spam">
                            <i class="fa fa-thumbs-down"></i>
                            <?php echo $PMF_LANG['spamControlCenter']; ?>
                        </button>
                    </p>
                    <div id="configSpam" class="hide"></div>

                    <p>
                        <button class="btn btn-inverse toggleConfig"  data-toggle="Seo">
                            <i class="fa fa-search"></i>
                            <?php echo $PMF_LANG['seoCenter']; ?>
                        </button>
                    </p>
                    <div id="configSeo" class="hide"></div>

                    <p>
                        <button class="btn btn-inverse toggleConfig" data-toggle="SocialNetworks">
                            <i class="fa fa-retweet"></i>
                            <?php echo $PMF_LANG['socialNetworksControlCenter']; ?>
                        </button>
                    </p>
                    <div id="configSocialNetworks" class="hide"></div>

                    <p>
                        <button class="btn btn-inverse toggleConfig" data-toggle="Mail">
                            <i class="fa fa-inbox"></i>
                            <?php echo $PMF_LANG['mailControlCenter']; ?>
                        </button>
                    </p>
                    <div id="configMail" class="hide"></div>

                    <p>
                        <button class="btn btn-primary" type="submit">
                            <?php echo $PMF_LANG['ad_config_save']; ?>
                        </button>
                        <button class="btn btn-warning" type="reset">
                            <?php echo $PMF_LANG['ad_config_reset']; ?>
                        </button>
                    </p>
                </form>

            </div>
        </div>

        <script type="text/javascript">
            toggleConfig = function (e) {
                e.preventDefault();
                var configContainer = $("#config" + $(this).data('toggle'));

                if ("hide" === configContainer.attr("class")) {
                    $.get("index.php", {
                        action: "ajax",
                        ajax: "config_list",
                        conf: $(this).data('toggle').toLowerCase()
                    }, function (data) {
                        configContainer.empty().append(data);
                    });
                    configContainer.fadeIn("slow").removeAttr("class");
                } else {
                    configContainer.fadeOut("slow").attr("class", "hide").empty();
                }
            }
            $('button.toggleConfig').on('click', toggleConfig);
        </script>
<?php
    }
} else {
    echo $PMF_LANG['err_NotAuth'];
}
