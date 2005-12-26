<?php
/**
* $Id: ajax.config_list.php,v 1.2 2005-12-26 21:38:09 thorstenr Exp $
*
* AJAX: lists the complete configuration items
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2005-12-26
* @copyright    (c) 2005 phpMyFAQ Team
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

require_once(PMF_ROOT_DIR.'/inc/config.php');
require_once(PMF_ROOT_DIR.'/lang/language_en.php');

foreach ($LANG_CONF as $key => $value) {
?>
<dl>
    <dt><strong><?php print $value[1]; ?></strong></dt>
    <dd><?php
        
    switch($value[0]) {
            
        case 'area':        printf('<textarea class="admin" name="edit[%s]" cols="50" rows="6" style="width: 400px;">%s</textarea>', $key, $PMF_CONF[$key]);
                            break;
            
        case 'input':       printf('<input class="admin" type="text" name="edit[%s]" size="64" style="width: 400px;" value="%s" />', $key, $PMF_CONF[$key]);
                            break;
            
        case 'select':      printf('<select name="edit[%s]" size="1">', $key);
                            if ($dir = @opendir(PMF_ROOT_DIR.'/lang')) {
                                while ($dat = @readdir($dir)) {
                                    if (substr($dat, -4) == '.php') {
                                        printf('<option value="%s"', $dat);
            				            if ($dat == $PMF_CONF['language']) {
                                            print ' selected="selected"';
                                        }
                                        printf('>%s</option>', $languageCodes[substr(strtoupper($dat), 9, 2)]);
                                    }
                                }
                            } else {
                                print '<option>English</option>';
                            }
                            print '</select>';
                            break;
            
        case 'checkbox':    printf('<input type="checkbox" name="edit[%s]" value="true"', $key);
                            if (isset($PMF_CONF[$key]) && true == $PMF_CONF[$key]) {
                                print ' checked="checked"';
                            }
                            print ' /> '.strtolower($PMF_LANG['ad_entry_active']);
                            break;
            
        case 'print':       print $PMF_CONF[$key];
                            printf('<input class="admin" type="hidden" name="edit[%s]" size="64" style="width: 400px;" value="%s" />', $key, $PMF_CONF[$key]);
                            break;
    }
?></dd>
</dl><?php
}