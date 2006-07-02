<?php
/**
* $Id: ajax.config_list.php,v 1.7 2006-07-02 16:44:57 matteo Exp $
*
* AJAX: lists the complete configuration items
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2005-12-26
* @copyright    (c) 2006 phpMyFAQ Team
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
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

require_once(PMF_ROOT_DIR.'/lang/language_en.php');

$configMode = 'std';
$availableConfigModes = array(
        'spam' => 1
        );
if (isset($_GET['conf']) && is_string($_GET['conf']) && isset($availableConfigModes[$_GET['conf']])) {
    $configMode = $_GET['conf'];
}

function printInputFieldByType($key, $type)
{
    global $PMF_CONF, $PMF_LANG;

    switch($type) {
        case 'area':
            printf('<textarea name="edit[%s]" cols="60" rows="6">%s</textarea>', $key, htmlentities($PMF_CONF[$key]));
            printf("<br />\n");
            break;

        case 'input':
            printf('<input type="text" name="edit[%s]" size="80" value="%s" />', $key, htmlentities($PMF_CONF[$key]));
            printf("<br />\n");
            break;

        case 'select':
            printf('<select name="edit[%s]" size="1">', $key);
            $languages = getAvailableLanguages();
            if (count($languages) > 0) {
                print languageOptions(str_replace(array("language_", ".php"), "", $PMF_CONF['language']), false, true);
            } else {
                print '<option value="language_en.php">English</option>';
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
            printf('<input type="hidden" name="edit[%s]" size="80" value="%s" />', $key, $PMF_CONF[$key]);
            printf("<br />\n");
            break;
    }
}

    foreach ($LANG_CONF as $key => $value) {
        $filterConfigParams = ($configMode == 'std'? false === strpos($key, 'spam') : 0 === strpos($key, $configMode));
        if ($filterConfigParams) {
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
