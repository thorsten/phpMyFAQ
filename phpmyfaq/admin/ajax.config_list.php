<?php
/**
* $Id: ajax.config_list.php,v 1.17 2007-03-29 20:22:45 thorstenr Exp $
*
* AJAX: lists the complete configuration items
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2005-12-26
* @copyright    (c) 2005-2007 phpMyFAQ Team
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
*/

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

require_once(PMF_ROOT_DIR.'/lang/language_en.php');

$configMode = 'main';
$availableConfigModes = array(
        'main'      => 1,
        'records'   => 1,
        'spam'      => 1);

if (isset($_GET['conf']) && is_string($_GET['conf']) && isset($availableConfigModes[$_GET['conf']])) {
    $configMode = $_GET['conf'];
}

function printInputFieldByType($key, $type)
{
    global $PMF_CONF, $PMF_LANG;

    switch($type) {
        case 'area':

            printf('<textarea name="edit[%s]" cols="60" rows="6">%s</textarea>',
                    $key,
                    str_replace('<', '&lt;', str_replace('>', '&gt;', $PMF_CONF[$key])));
            printf("<br />\n");
            break;

        case 'input':

            printf('<input type="text" name="edit[%s]" size="80" value="%s" />',
                    $key,
                    str_replace('"', '&quot;', $PMF_CONF[$key]));
            printf("<br />\n");
            break;

        case 'select':

            printf('<select name="edit[%s]" size="1">', $key);
            if ('main.language' == $key) {
                $languages = getAvailableLanguages();
                if (count($languages) > 0) {
                    print languageOptions(str_replace(array("language_", ".php"), "", $PMF_CONF['main.language']), false, true);
                } else {
                    print '<option value="language_en.php">English</option>';
                }
            } else if ('records.orderby' == $key) {
                    print sortingOptions($PMF_CONF[$key]);
            } elseif ('records.sortby' == $key) {
                    printf('<option value="DESC"%s>%s</option>',
                        ('DESC' == $PMF_CONF[$key]) ? ' selected="selected"' : '',
                        $PMF_LANG['ad_conf_desc']);
                    printf('<option value="ASC"%s>%s</option>',
                        ('ASC' == $PMF_CONF[$key]) ? ' selected="selected"' : '',
                        $PMF_LANG['ad_conf_asc']);
            }
            print '</select>';
            printf("<br />\n");
            break;

        case 'checkbox':

            printf('<input type="checkbox" name="edit[%s]" value="true"', $key);
            if (isset($PMF_CONF[$key]) && $PMF_CONF[$key]) {
                print ' checked="checked"';
            }
            printf(' />&nbsp;%s', $PMF_LANG["ad_entry_active"]);
            printf("<br />\n");
            break;

        case 'print':

            print $PMF_CONF[$key];
            printf('<input type="hidden" name="edit[%s]" size="80" value="%s" />',
                    $key,
                    str_replace('"', '&quot;', $PMF_CONF[$key]));
            printf("<br />\n");
            break;
    }
}

header("Content-type: text/html; charset=".$PMF_LANG['metaCharset']);

foreach ($LANG_CONF as $key => $value) {
    if (strpos($key, $configMode) === 0) {
?>
<dl>
    <dt><strong><?php print $value[1]; ?></strong></dt>
        <dd>
<?php
            printInputFieldByType($key, $value[0]);
?>
        </dd>
    </dt>
</dl>
<?php
    }
}
