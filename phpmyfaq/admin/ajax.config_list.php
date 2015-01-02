<?php
/**
 * AJAX: lists the complete configuration items as text/html
 *
 * PHP 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Thomas Zeithaml <tom@annatom.de>
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

require PMF_ROOT_DIR . '/inc/libs/twitteroauth/twitteroauth.php';

if (!empty($_SESSION['access_token'])) {
    $connection = new TwitterOAuth(
        $faqConfig->get('socialnetworks.twitterConsumerKey'),
        $faqConfig->get('socialnetworks.twitterConsumerSecret'),
        $_SESSION['access_token']['oauth_token'],
        $_SESSION['access_token']['oauth_token_secret']
    );

    $content = $connection->get('account/verify_credentials');
}

$configMode           = PMF_Filter::filterInput(INPUT_GET, 'conf', FILTER_SANITIZE_STRING, 'main');
$availableConfigModes = array(
        'main'    => 1,
        'records' => 1,
        'spam'    => 1,
        'search'  => 1,
        'social'  => 1
);

/**
 * @param  $key
 * @param  $type
 * @return void
 */
function renderInputForm($key, $type)
{
    global $PMF_LANG, $faqConfig;

    switch ($type) {

        case 'area':
            printf('<textarea name="edit[%s]" cols="60" rows="6" class="input-xxlarge">%s</textarea>',
                    $key,
                    str_replace('<', '&lt;', str_replace('>', '&gt;', $faqConfig->get($key))));
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
                '<input class="%s" type="%s" name="edit[%s]" size="75" value="%s" />',
                is_numeric($value) ? 'input-small' : 'input-xxlarge',
                is_numeric($value) ? 'number' : 'text',
                $key,
                $value
            );
            echo "</div>\n";
            break;

        case 'select':
            printf('<select name="edit[%s]" size="1" class="input-xlarge">', $key);
            
            switch ($key) {
                
                case 'main.language':
                    $languages = PMF_Language::getAvailableLanguages();
                    if (count($languages) > 0) {
                        echo PMF_Language::languageOptions(
                            str_replace(
                                array(
                                     'language_',
                                     '.php'
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
                    printf('<option value="DESC"%s>%s</option>',
                        ('DESC' == $faqConfig->get($key)) ? ' selected="selected"' : '',
                        $PMF_LANG['ad_conf_desc']);
                    printf('<option value="ASC"%s>%s</option>',
                        ('ASC' == $faqConfig->get($key)) ? ' selected="selected"' : '',
                        $PMF_LANG['ad_conf_asc']);
                    break;
                    
                case 'security.permLevel':
                    echo PMF_Perm::permOptions($faqConfig->get($key));
                    break;
                    
                case 'main.templateSet':
                    $faqSystem = new PMF_System();
                    $templates = $faqSystem->getAvailableTemplates();

                    foreach ($templates as $template => $selected) {
                        printf ("<option%s>%s</option>",
                            ($selected === true ? ' selected="selected"' : ''),
                            $template
                        );
                    }
                    break;
                    
                case "records.attachmentsStorageType":
                    foreach($PMF_LANG['att_storage_type'] as $i => $item) {
                        $selected = $faqConfig->get($key) == $i
                                  ? ' selected="selected"'
                                  : '';
                        printf('<option value="%d"%s>%s</option>',
                               $i, $selected, $item);
                    }
                    break;
                    
                case "records.orderingPopularFaqs":
                    printf('<option value="visits"%s>%s</option>',
                        ('visits' == $faqConfig->get($key)) ? ' selected="selected"' : '',
                        $PMF_LANG['records.orderingPopularFaqs.visits']);
                    printf('<option value="voting"%s>%s</option>',
                        ('voting' == $faqConfig->get($key)) ? ' selected="selected"' : '',
                        $PMF_LANG['records.orderingPopularFaqs.voting']);
                    break;

                case "search.relevance":
                    printf('<option value="thema,content,keywords"%s>%s</option>',
                        ('thema,content,keywords' == $faqConfig->get($key)) ? ' selected="selected"' : '',
                        $PMF_LANG['search.relevance.thema-content-keywords']);
                    printf('<option value="thema,keywords,content"%s>%s</option>',
                        ('thema,keywords,content' == $faqConfig->get($key)) ? ' selected="selected"' : '',
                        $PMF_LANG['search.relevance.thema-keywords-content']);
                    printf('<option value="content,thema,keywords"%s>%s</option>',
                        ('content,thema,keywords' == $faqConfig->get($key)) ? ' selected="selected"' : '',
                        $PMF_LANG['search.relevance.content-thema-keywords']);
                    printf('<option value="content,keywords,thema"%s>%s</option>',
                        ('content,keywords,thema' == $faqConfig->get($key)) ? ' selected="selected"' : '',
                        $PMF_LANG['search.relevance.content-keywords-thema']);
                    printf('<option value="keywords,content,thema"%s>%s</option>',
                        ('keywords,content,thema' == $faqConfig->get($key)) ? ' selected="selected"' : '',
                        $PMF_LANG['search.relevance.keywords-content-thema']);
                    printf('<option value="keywords,thema,content"%s>%s</option>',
                        ('keywords,thema,content' == $faqConfig->get($key)) ? ' selected="selected"' : '',
                        $PMF_LANG['search.relevance.keywords-thema-content']);
                    break;
            }
            
            echo "</select>\n</div>\n";
            break;

        case 'checkbox':
            printf('<input type="checkbox" name="edit[%s]" value="true"', $key);
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
            echo " /></div>\n";
            break;
            
        case 'print':
            printf(
                '<input type="text" readonly name="edit[%s]" class="input-mini uneditable-input" value="%s" /></div>',
                $key,
                str_replace('"', '&quot;', $faqConfig->get($key)),
                $faqConfig->get($key)
            );
            break;
    }
}

header("Content-type: text/html; charset=utf-8");

foreach ($LANG_CONF as $key => $value) {
    if (strpos($key, $configMode) === 0) {

        if ('socialnetworks.twitterConsumerKey' == $key) {
            echo '<div class="control-group"><label class="control-label admin-config-label"></label>';
            echo '<div class="controls admin-config-control">';
            if ('' == $faqConfig->get('socialnetworks.twitterConsumerKey') ||
                '' == $faqConfig->get('socialnetworks.twitterConsumerSecret')) {

                echo '<a target="_blank" href="https://dev.twitter.com/apps/new">Create Twitter App for your FAQ</a>';
                echo "<br />\n";
                echo "Your Callback URL is: " .$faqConfig->get('main.referenceURL') . "/services/twitter/callback.php";
            }

            if (!isset($content)) {
                echo '<a target="_blank" href="../services/twitter/redirect.php">';
                echo '<img src="../assets/img/twitter.signin.png" alt="Sign in with Twitter"/></a>';
            } elseif (isset($content)) {
                echo $content->screen_name . "<br />\n";
                echo "<img src='" . $content->profile_image_url_https . "'><br />\n";
                echo "Follower: " . $content->followers_count . "<br />\n";
                echo "Status Count: " . $content->statuses_count . "<br />\n";
                echo "Status: " . $content->status->text;
            }
            echo '</div>';
            echo '</div>';
        }
?>
            <div class="control-group">
                <label class="control-label admin-config-label">
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
                <div class="controls admin-config-control">
                    <?php renderInputForm($key, $value[0]); ?>
                </div>
<?php
    }
}
