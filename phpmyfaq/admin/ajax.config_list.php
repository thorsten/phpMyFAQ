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
 * @copyright 2005-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-12-26
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

require_once PMF_ROOT_DIR . '/lang/language_en.php';
require_once PMF_ROOT_DIR . '/inc/libs/twitteroauth/twitteroauth.php';

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
        'social'  => 1);

/**
 * @param  $key
 * @param  $type
 * @return void
 */
function printInputFieldByType($key, $type)
{
    global $PMF_LANG, $faqConfig;

    switch ($type) {

        case 'area':
            printf('<textarea name="edit[%s]" cols="60" rows="6" style="width: 300px;">%s</textarea>',
                    $key,
                    str_replace('<', '&lt;', str_replace('>', '&gt;', $faqConfig->get($key))));
            printf("</div>\n");
            break;

        case 'input':
            if ('' == $faqConfig->get($key) && 'socialnetworks.twitterAccessTokenKey' == $key) {
                $value = $_SESSION['access_token']['oauth_token'];
            } elseif ('' == $faqConfig->get($key) && 'socialnetworks.twitterAccessTokenSecret' == $key) {
                $value = $_SESSION['access_token']['oauth_token_secret'];
            } else {
                $value = str_replace('"', '&quot;', $faqConfig->get($key));
            }
            printf('<input type="text" name="edit[%s]" size="75" value="%s" style="width: 300px;" />',
                    $key,
                    $value);
            printf("</div>\n");
            break;

        case 'select':
            printf('<select name="edit[%s]" size="1" style="width: 300px;">', $key);
            
            switch ($key) {
                
                case 'main.language':
                    $languages = PMF_Language::getAvailableLanguages();
                    if (count($languages) > 0) {
                        print PMF_Language::languageOptions(
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
                        print '<option value="language_en.php">English</option>';
                    }
                   break;
                
                case 'records.orderby':
                    print PMF_Configuration::sortingOptions($faqConfig->get($key));
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
                    print PMF_Perm::permOptions($faqConfig->get($key));
                    break;
                    
                case "main.templateSet":
                    /**
                     * TODO: do get available template sets in the PMF_Template
                     */
                    foreach (new DirectoryIterator('../template') as $item) {
                        if (!$item->isDot() && $item->isDir()) {
                            $selected = PMF_Template::getTplSetName() == $item ? ' selected="selected"' : '';
                            printf ("<option%s>%s</option>",
                                $selected,
                                $item);
                        }
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
            
            print "</select>\n</p>\n";
            break;

        case 'checkbox':
            printf('<input type="checkbox" name="edit[%s]" value="true"', $key);
            if ($faqConfig->get($key)) {
                print ' checked="checked"';
            }
            print " /></div>\n";
            break;
            
        case 'print':
            printf('<input type="hidden" name="edit[%s]" size="80" value="%s" />%s</div>',
                    $key,
                    str_replace('"', '&quot;', $faqConfig->get($key)),
                    $faqConfig->get($key));
            break;
    }
}

header("Content-type: text/html; charset=utf-8");

foreach ($LANG_CONF as $key => $value) {
    if (strpos($key, $configMode) === 0) {
        
        if ('socialnetworks.twitterConsumerKey' == $key) {
            print '<p>';
            if ('' == $faqConfig->get('socialnetworks.twitterConsumerKey') ||
                '' == $faqConfig->get('socialnetworks.twitterConsumerSecret')) {

                print '<a target="_blank" href="https://dev.twitter.com/apps/new">Create Twitter APP for your site</a>';
                print "<br />\n";
                print "Your Callback URL is: " .$faqConfig->get('main.referenceURL') . "/services/twitter/callback.php";
            }

            if (!isset($content)) {
                print '<a target="_blank" href="../services/twitter/redirect.php">';
                print '<img src="../images/twitter.signin.png" alt="Sign in with Twitter"/></a>';
                print "<br />\n<br />\n";
            } elseif (isset($content)) {
                print $content->screen_name . "<br />\n";
                print "<img src='" . $content->profile_image_url_https . "'><br />\n";
                print "Follower: " . $content->followers_count . "<br />\n";
                print "Status Count: " . $content->statuses_count . "<br />\n";
                print "Status: " . $content->status->text . "<br />\n";
                print "<br />\n";
            }
            print '</div>';
        }
?>
            <div class="control-group">
                <label>
<?php
        switch ($key) {

            case 'records.maxAttachmentSize':
                printf($value[1], ini_get('upload_max_filesize'));
                break;

            case 'main.googleTranslationKey':
                printf(
                    '<a target="_blank" href="http://code.google.com/apis/loader/signup.html">%s</a>',
                    $value[1]
                );
                break;

            case 'main.dateFormat':
                printf(
                    '<a target="_blank" href="http://www.php.net/manual/%s/function.date.php">%s</a>',
                    $LANGCODE,
                    $value[1]
                );
                break;

            default:
                print $value[1];
                break;
        }
?>
                </label>
                <div class="controls">
                    <?php printInputFieldByType($key, $value[0]); ?>
                </div>
<?php
    }
}
