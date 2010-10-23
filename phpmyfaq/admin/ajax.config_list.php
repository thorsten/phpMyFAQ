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
 * @copyright 2005-2010 phpMyFAQ Team
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
    $connection = new TwitterOAuth($faqconfig->get('socialnetworks.twitterConsumerKey'),
                                   $faqconfig->get('socialnetworks.twitterConsumerSecret'),
                                   $_SESSION['access_token']['oauth_token'],
                                   $_SESSION['access_token']['oauth_token_secret']);
    $content = $connection->get('account/verify_credentials');
}

$configMode           = PMF_Filter::filterInput(INPUT_GET, 'conf', FILTER_SANITIZE_STRING, 'main');
$availableConfigModes = array(
        'main'    => 1,
        'records' => 1,
        'spam'    => 1,
        'social'  => 1);



function printInputFieldByType($key, $type)
{
    global $PMF_LANG;
    
    $faqconfig = PMF_Configuration::getInstance();

    switch ($type) {

        case 'area':
            printf('<textarea name="edit[%s]" cols="60" rows="6" style="width: 500px;">%s</textarea>',
                    $key,
                    str_replace('<', '&lt;', str_replace('>', '&gt;', $faqconfig->get($key))));
            printf("<br />\n");
            break;

        case 'input':
            if ('' == $faqconfig->get($key) && 'socialnetworks.twitterAccessTokenKey' == $key) {
                $value = $_SESSION['access_token']['oauth_token'];
            } elseif ('' == $faqconfig->get($key) && 'socialnetworks.twitterAccessTokenSecret' == $key) {
                $value = $_SESSION['access_token']['oauth_token_secret'];
            } else {
                $value = str_replace('"', '&quot;', $faqconfig->get($key));
            }
            printf('<input type="text" name="edit[%s]" size="75" value="%s" style="width: 500px;" />',
                    $key,
                    $value);
            printf("<br />\n");
            break;

        case 'select':
            printf('<select name="edit[%s]" size="1" style="width: 500px;">', $key);
            
            switch ($key) {
                
                case 'main.language':
                    $languages = PMF_Language::getAvailableLanguages();
                    if (count($languages) > 0) {
                        print PMF_Language::languageOptions(str_replace(array("language_", ".php"), "", $faqconfig->get('main.language')), false, true);
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
                    
                case 'main.permLevel':
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
                    
                case "main.attachmentsStorageType":
                    foreach($PMF_LANG['att_storage_type'] as $i => $item) {
                        $selected = $faqconfig->get($key) == $i
                                  ? ' selected="selected"'
                                  : '';
                        printf('<option value="%d"%s>%s</option>',
                               $i, $selected, $item);
                    }
                    break;
                    
                case "main.orderingPopularFaqs":
                    printf('<option value="visits"%s>%s</option>',
                        ('visits' == $faqconfig->get($key)) ? ' selected="selected"' : '',
                        $PMF_LANG['main.orderingPopularFaqs.visits']);
                    printf('<option value="voting"%s>%s</option>',
                        ('voting' == $faqconfig->get($key)) ? ' selected="selected"' : '',
                        $PMF_LANG['main.orderingPopularFaqs.voting']);
                    break;
            }
            
            print "</select>\n<br />\n";
            break;

        case 'checkbox':
            printf('<input type="checkbox" name="edit[%s]" value="true"', $key);
            if ($faqconfig->get($key)) {
                print ' checked="checked"';
            }
            print " /><br />\n";
            break;
            
        case 'print':
            printf('<input type="hidden" name="edit[%s]" size="80" value="%s" />%s<br />',
                    $key,
                    str_replace('"', '&quot;', $faqconfig->get($key)),
                    $faqconfig->get($key));
            break;
    }
}

header("Content-type: text/html; charset=utf-8");

foreach ($LANG_CONF as $key => $value) {
    if (strpos($key, $configMode) === 0) {
?>
    <label class="leftconfig"><?php
        if ('socialnetworks.twitterConsumerKey' == $key) {
            if ('' == $faqconfig->get('socialnetworks.twitterConsumerKey') ||
                '' == $faqconfig->get('socialnetworks.twitterConsumerSecret')) {

                print '<a taget="_blank" href="http://dev.twitter.com/apps/new">Create Twitter APP for your site</a>';
                print "<br />\n";
                print "Your Callback URL is: " .$faqconfig->get('main.referenceURL') . "/services/twitter/callback.php";
            }
            if ('' == $faqconfig->get('socialnetworks.twitterAccessTokenKey') ||
                '' == $faqconfig->get('socialnetworks.twitterAccessTokenSecret')) {

                print '<a href="../services/twitter/redirect.php"><img src="../images/twitter.signin.png" alt="Sign in with Twitter"/></a>';
                print "<br />\n<br />\n";
            } else {

                print $content->screen_name . "<br />\n";
                print "<img src='" . $content->profile_image_url . "'><br />\n";
                print "Follower: " . $content->followers_count . "<br />\n";
                print "Status Count: " . $content->statuses_count . "<br />\n";
                print "Following: " . $content->following . "<br />\n";
                print "Status: " . $content->status->text . "<br />\n";
                print "<br />\n";
            }
        }
        if ('main.maxAttachmentSize' == $key) {
            printf($value[1], ini_get('upload_max_filesize'));
        } else {
            print $value[1];
        } ?></label>
    <?php printInputFieldByType($key, $value[0]); ?><br />
<?php
    }
}
