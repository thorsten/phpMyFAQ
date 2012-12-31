<?php
/**
 * AJAX: lists the complete configuration items as text/html
 * 
 * PHP 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 * 
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Thomas Zeithaml <tom@annatom.de>
 * @copyright 2005-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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
        $faqconfig->get('socialnetworks.twitterConsumerKey'),
        $faqconfig->get('socialnetworks.twitterConsumerSecret'),
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
    global $PMF_LANG;
    
    $faqconfig = PMF_Configuration::getInstance();

    switch ($type) {

        case 'area':
            printf('<textarea name="edit[%s]" cols="60" rows="6" style="width: 300px;">%s</textarea>',
                    $key,
                    str_replace('<', '&lt;', str_replace('>', '&gt;', $faqconfig->get($key))));
            printf("</p>\n");
            break;

        case 'input':
            if ('' == $faqconfig->get($key) && 'socialnetworks.twitterAccessTokenKey' == $key) {
                $value = $_SESSION['access_token']['oauth_token'];
            } elseif ('' == $faqconfig->get($key) && 'socialnetworks.twitterAccessTokenSecret' == $key) {
                $value = $_SESSION['access_token']['oauth_token_secret'];
            } else {
                $value = str_replace('"', '&quot;', $faqconfig->get($key));
            }
            printf('<input type="text" name="edit[%s]" size="75" value="%s" style="width: 300px;" />',
                    $key,
                    $value);
            printf("</p>\n");
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
                                $faqconfig->get('main.language')
                            ),
                            false,
                            true
                        );
                    } else {
                        print '<option value="language_en.php">English</option>';
                    }
                   break;
                
                case 'records.orderby':
                    print sortingOptions($faqconfig->get($key));
                    break;
                    
                case 'records.sortby':
                    printf('<option value="DESC"%s>%s</option>',
                        ('DESC' == $faqconfig->get($key)) ? ' selected="selected"' : '',
                        $PMF_LANG['ad_conf_desc']);
                    printf('<option value="ASC"%s>%s</option>',
                        ('ASC' == $faqconfig->get($key)) ? ' selected="selected"' : '',
                        $PMF_LANG['ad_conf_asc']);
                    break;
                    
                case 'security.permLevel':
                    print PMF_Perm::permOptions($faqconfig->get($key));
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
                        $selected = $faqconfig->get($key) == $i
                                  ? ' selected="selected"'
                                  : '';
                        printf('<option value="%d"%s>%s</option>',
                               $i, $selected, $item);
                    }
                    break;
                    
                case "records.orderingPopularFaqs":
                    printf('<option value="visits"%s>%s</option>',
                        ('visits' == $faqconfig->get($key)) ? ' selected="selected"' : '',
                        $PMF_LANG['records.orderingPopularFaqs.visits']);
                    printf('<option value="voting"%s>%s</option>',
                        ('voting' == $faqconfig->get($key)) ? ' selected="selected"' : '',
                        $PMF_LANG['records.orderingPopularFaqs.voting']);
                    break;

                case "search.relevance":
                    printf('<option value="thema,content,keywords"%s>%s</option>',
                        ('thema,content,keywords' == $faqconfig->get($key)) ? ' selected="selected"' : '',
                        $PMF_LANG['search.relevance.thema-content-keywords']);
                    printf('<option value="thema,keywords,content"%s>%s</option>',
                        ('thema,keywords,content' == $faqconfig->get($key)) ? ' selected="selected"' : '',
                        $PMF_LANG['search.relevance.thema-keywords-content']);
                    printf('<option value="content,thema,keywords"%s>%s</option>',
                        ('content,thema,keywords' == $faqconfig->get($key)) ? ' selected="selected"' : '',
                        $PMF_LANG['search.relevance.content-thema-keywords']);
                    printf('<option value="content,keywords,thema"%s>%s</option>',
                        ('content,keywords,thema' == $faqconfig->get($key)) ? ' selected="selected"' : '',
                        $PMF_LANG['search.relevance.content-keywords-thema']);
                    printf('<option value="keywords,content,thema"%s>%s</option>',
                        ('keywords,content,thema' == $faqconfig->get($key)) ? ' selected="selected"' : '',
                        $PMF_LANG['search.relevance.keywords-content-thema']);
                    printf('<option value="keywords,thema,content"%s>%s</option>',
                        ('keywords,thema,content' == $faqconfig->get($key)) ? ' selected="selected"' : '',
                        $PMF_LANG['search.relevance.keywords-thema-content']);
                    break;
            }
            
            print "</select>\n</p>\n";
            break;

        case 'checkbox':
            printf('<input type="checkbox" name="edit[%s]" value="true"', $key);
            if ($faqconfig->get($key)) {
                print ' checked="checked"';
            }
            print " /></p>\n";
            break;
            
        case 'print':
            printf('<input type="hidden" name="edit[%s]" size="80" value="%s" />%s</p>',
                    $key,
                    str_replace('"', '&quot;', $faqconfig->get($key)),
                    $faqconfig->get($key));
            break;
    }
}

header("Content-type: text/html; charset=utf-8");

foreach ($LANG_CONF as $key => $value) {
    if (strpos($key, $configMode) === 0) {
        
        if ('socialnetworks.twitterConsumerKey' == $key) {
            print '<p>';
            if ('' == $faqconfig->get('socialnetworks.twitterConsumerKey') ||
                '' == $faqconfig->get('socialnetworks.twitterConsumerSecret')) {

                print '<a target="_blank" href="https://dev.twitter.com/apps/new">Create Twitter APP for your site</a>';
                print "<br />\n";
                print "Your Callback URL is: " .$faqconfig->get('main.referenceURL') . "/services/twitter/callback.php";
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
            print '</p>';
        }
?>
            <p class="config">
                <label class="config">
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
                <?php printInputFieldByType($key, $value[0]); ?>
                <br />
<?php
    }
}
