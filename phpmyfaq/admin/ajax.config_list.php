<?php
/**
 * AJAX: lists the complete configuration items as text/html.
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
 * @author    Thomas Zeithaml <tom@annatom.de>
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

if (!empty($_SESSION['access_token'])) {
    $connection = new TwitterOAuth(
        $faqConfig->get('socialnetworks.twitterConsumerKey'),
        $faqConfig->get('socialnetworks.twitterConsumerSecret'),
        $_SESSION['access_token']['oauth_token'],
        $_SESSION['access_token']['oauth_token_secret']
    );

    $content = $connection->get('account/verify_credentials');
}

$configMode = PMF_Filter::filterInput(INPUT_GET, 'conf', FILTER_SANITIZE_STRING, 'main');
$availableConfigModes = [
    'main' => 1,
    'records' => 1,
    'spam' => 1,
    'search' => 1,
    'social' => 1,
    'seo' => 1,
    'mail' => 1
];

/**
 * @param mixed  $key
 * @param string $type
 */
function renderInputForm($key, $type)
{
    global $PMF_LANG, $faqConfig;

    switch ($type) {

        case 'area':
            printf(
                '<textarea name="edit[%s]" rows="4" class="form-control">%s</textarea>',
                $key,
                str_replace('<', '&lt;', str_replace('>', '&gt;', $faqConfig->get($key)))
            );
            printf("</div>\n");
            break;

        case 'input':
            if ('' == $faqConfig->get($key) && 'socialnetworks.twitterAccessTokenKey' == $key &&
                isset($_SESSION['access_token'])) {
                $value = $_SESSION['access_token']['oauth_token'];
            } elseif ('' == $faqConfig->get($key) && 'socialnetworks.twitterAccessTokenSecret' == $key &&
                isset($_SESSION['access_token'])) {
                $value = $_SESSION['access_token']['oauth_token_secret'];
            } else {
                $value = str_replace('"', '&quot;', $faqConfig->get($key));
            }
            printf(
                '<input class="form-control" type="%s" name="edit[%s]" value="%s" step="1" min="0">',
                is_numeric($value) ? 'number' : 'text',
                $key,
                $value
            );
            echo "</div>\n";
            break;

        case 'password':
            printf(
                '<input class="form-control" type="password" name="edit[%s]" value="%s">',
                $key,
                $faqConfig->get($key)
            );
            echo "</div>\n";
            break;

        case 'select':
            printf('<select name="edit[%s]" size="1" class="form-control">', $key);

            switch ($key) {

                case 'main.language':
                    $languages = PMF_Language::getAvailableLanguages();
                    if (count($languages) > 0) {
                        echo PMF_Language::languageOptions(
                            str_replace(
                                array(
                                     'language_',
                                     '.php',
                                ),
                                '',
                                $faqConfig->get('main.language')
                            ),
                            false,
                            true
                        );
                    } else {
                        echo '<option value="language_en.php">English</option>';
                    }
                   break;

                case 'records.orderby':
                    echo PMF_Configuration::sortingOptions($faqConfig->get($key));
                    break;

                case 'records.sortby':
                    printf(
                        '<option value="DESC"%s>%s</option>',
                        ('DESC' == $faqConfig->get($key)) ? ' selected' : '',
                        $PMF_LANG['ad_conf_desc']
                    );
                    printf(
                        '<option value="ASC"%s>%s</option>',
                        ('ASC' == $faqConfig->get($key)) ? ' selected' : '',
                        $PMF_LANG['ad_conf_asc']
                    );
                    break;

                case 'security.permLevel':
                    echo PMF_Perm::permOptions($faqConfig->get($key));
                    break;

                case 'main.templateSet':
                    $faqSystem = new PMF_System();
                    $templates = $faqSystem->getAvailableTemplates();

                    foreach ($templates as $template => $selected) {
                        printf('<option%s>%s</option>',
                            ($selected === true ? ' selected' : ''),
                            $template
                        );
                    }
                    break;

                case 'records.attachmentsStorageType':
                    foreach ($PMF_LANG['att_storage_type'] as $i => $item) {
                        $selected = (int)$faqConfig->get($key) === $i ? ' selected' : '';
                        printf('<option value="%d"%s>%s</option>', $i, $selected, $item);
                    }
                    break;

                case 'records.orderingPopularFaqs':
                    printf(
                        '<option value="visits"%s>%s</option>',
                        ('visits' === $faqConfig->get($key)) ? ' selected' : '',
                        $PMF_LANG['records.orderingPopularFaqs.visits']
                    );
                    printf(
                        '<option value="voting"%s>%s</option>',
                        ('voting' === $faqConfig->get($key)) ? ' selected' : '',
                        $PMF_LANG['records.orderingPopularFaqs.voting']
                    );
                    break;

                case 'search.relevance':
                    printf(
                        '<option value="thema,content,keywords"%s>%s</option>',
                        ('thema,content,keywords' == $faqConfig->get($key)) ? ' selected' : '',
                        $PMF_LANG['search.relevance.thema-content-keywords']
                    );
                    printf(
                        '<option value="thema,keywords,content"%s>%s</option>',
                        (
                            'thema,keywords,content' == $faqConfig->get($key)) ? ' selected' : '',
                        $PMF_LANG['search.relevance.thema-keywords-content']
                    );
                    printf(
                        '<option value="content,thema,keywords"%s>%s</option>',
                        ('content,thema,keywords' == $faqConfig->get($key)) ? ' selected' : '',
                        $PMF_LANG['search.relevance.content-thema-keywords']
                    );
                    printf(
                        '<option value="content,keywords,thema"%s>%s</option>',
                        ('content,keywords,thema' == $faqConfig->get($key)) ? ' selected' : '',
                        $PMF_LANG['search.relevance.content-keywords-thema']
                    );
                    printf(
                        '<option value="keywords,content,thema"%s>%s</option>',
                        ('keywords,content,thema' == $faqConfig->get($key)) ? ' selected' : '',
                        $PMF_LANG['search.relevance.keywords-content-thema']
                    );
                    printf(
                        '<option value="keywords,thema,content"%s>%s</option>',
                        ('keywords,thema,content' == $faqConfig->get($key)) ? ' selected' : '',
                        $PMF_LANG['search.relevance.keywords-thema-content']
                    );
                    break;

                case 'seo.metaTagsHome':
                case 'seo.metaTagsFaqs':
                case 'seo.metaTagsCategories':
                case 'seo.metaTagsPages':
                case 'seo.metaTagsAdmin':
                    $adminHelper = new PMF_Helper_Administration();
                    echo $adminHelper->renderMetaRobotsDropdown($faqConfig->get($key));
                    break;
            }

            echo "</select>\n</div>\n";
            break;

        case 'checkbox':
            printf(
                '<div class="checkbox"><label><input type="checkbox" name="edit[%s]" value="true"',
                $key
            );
            if ($faqConfig->get($key)) {
                echo ' checked';
            }
            if ('security.ldapSupport' === $key && !extension_loaded('ldap')) {
                echo ' disabled';
            }
            if ('security.useSslOnly' === $key && empty($_SERVER['HTTPS'])) {
                echo ' disabled';
            }
            if ('security.ssoSupport' === $key && empty($_SERVER['REMOTE_USER'])) {
                echo ' disabled';
            }
            echo '>&nbsp;</label></div></div>';
            break;

        case 'print':
            printf(
                '<input type="text" readonly name="edit[%s]" class="form-control" value="%s"></div>',
                $key,
                str_replace('"', '&quot;', $faqConfig->get($key)),
                $faqConfig->get($key)
            );
            break;
    }
}

header('Content-type: text/html; charset=utf-8');

PMF_Utils::moveToTop($LANG_CONF, 'main.maintenanceMode');

foreach ($LANG_CONF as $key => $value) {
    if (strpos($key, $configMode) === 0) {
        if ('socialnetworks.twitterConsumerKey' == $key) {
            echo '<div class="form-group"><label class="control-label col-lg-3"></label>';
            echo '<div class="col-lg-9">';
            if ('' == $faqConfig->get('socialnetworks.twitterConsumerKey') ||
                '' == $faqConfig->get('socialnetworks.twitterConsumerSecret')) {
                echo '<a target="_blank" href="https://dev.twitter.com/apps/new">Create Twitter App for your FAQ</a>';
                echo "<br />\n";
                echo 'Your Callback URL is: '.$faqConfig->getDefaultUrl().'services/twitter/callback.php';
            }

            if (!isset($content)) {
                echo '<br><a target="_blank" href="../services/twitter/redirect.php">';
                echo '<img src="../assets/img/twitter.signin.png" alt="Sign in with Twitter"/></a>';
            } elseif (isset($content)) {
                echo $content->screen_name."<br />\n";
                echo "<img src='".$content->profile_image_url_https."'><br />\n";
                echo 'Follower: '.$content->followers_count."<br />\n";
                echo 'Status Count: '.$content->statuses_count."<br />\n";
                echo 'Status: '.$content->status->text;
            }
            echo '</div></div>';
        }
        ?>
            <div class="form-group">
                <label class="control-label col-lg-3">
<?php
        switch ($key) {

            case 'records.maxAttachmentSize':
                printf($value[1], ini_get('upload_max_filesize'));
                break;

            case 'main.dateFormat':
                printf(
                    '<a target="_blank" href="http://www.php.net/manual/%s/function.date.php">%s</a>',
                    $LANGCODE,
                    $value[1]
                );
                break;

            default:
                echo $value[1];
                break;
        }
        ?>
                </label>
                <div class="col-lg-6">
                    <?php renderInputForm($key, $value[0]);
        ?>
                </div>
<?php

    }
}
