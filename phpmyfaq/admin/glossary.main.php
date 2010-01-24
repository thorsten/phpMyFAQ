<?php
/**
 * The main glossary index file
 *
 * @package    phpMyFAQ
 * @subpackage Administration
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2005-09-15
 * @version    SVN: $Id$
 * @copyright  2005-2009 phpMyFAQ Team
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

print sprintf('<h2>%s</h2>', $PMF_LANG['ad_menu_glossary']);

if ($permission['addglossary'] || $permission['editglossary'] || $permission['delglossary']) {

    require_once(PMF_ROOT_DIR.'/inc/Glossary.php');
    $glossary = new PMF_Glossary();

    if ('saveglossary' == $action && $permission['addglossary']) {
    	$item       = PMF_Filter::filterInput(INPUT_POST, 'item', FILTER_SANITIZE_STRIPPED);
    	$definition = PMF_Filter::filterInput(INPUT_POST, 'definition', FILTER_SANITIZE_STRIPPED);
        if ($glossary->addGlossaryItem($item, $definition)) {
            print '<p>' . $PMF_LANG['ad_glossary_save_success'] . '</p>';
        } else {
            print '<p>' . $PMF_LANG['ad_glossary_save_error'];
            print '<br />'.$PMF_LANG["ad_adus_dberr"].'<br />';
            print $db->error() . '</p>';
        }
    }

    if ('updateglossary' == $action && $permission['editglossary']) {
    	$id         = PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $item       = PMF_Filter::filterInput(INPUT_POST, 'item', FILTER_SANITIZE_STRIPPED);
        $definition = PMF_Filter::filterInput(INPUT_POST, 'definition', FILTER_SANITIZE_STRIPPED);
        if ($glossary->updateGlossaryItem($id, $item, $definition)) {
            print '<p>' . $PMF_LANG['ad_glossary_update_success'] . '</p>';
        } else {
            print '<p>' . $PMF_LANG['ad_glossary_update_error'];
            print '<br />'.$PMF_LANG["ad_adus_dberr"].'<br />';
            print $db->error() . '</p>';
        }
    }

    if ('deleteglossary' == $action && $permission['editglossary']) {
    	$id = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if ($glossary->deleteGlossaryItem($id)) {
            print '<p>' . $PMF_LANG['ad_glossary_delete_success'] . '</p>';
        } else {
            print '<p>' . $PMF_LANG['ad_glossary_delete_error'];
            print '<br />'.$PMF_LANG["ad_adus_dberr"].'<br />';
            print $db->error() . '</p>';
        }
    }

    $glossaryItems = $glossary->getAllGlossaryItems();

    print sprintf('<p>[ <a href="?action=addglossary">%s</a> ]</p>', $PMF_LANG['ad_glossary_add']);

    print '<table id="tableGlossary">';
    print sprintf("<thead><tr><th>%s</th><th>%s</th><th>&nbsp;</th></tr></thead>", 
        $PMF_LANG['ad_glossary_item'], 
        $PMF_LANG['ad_glossary_definition']);

    foreach ($glossaryItems as $items) {
        print '<tr>';
        print sprintf('<td><a href="%s%d">%s</a></td>', 
            '?action=editglossary&amp;id=', 
            $items['id'], 
            $items['item']);
        print sprintf('<td>%s</td>', 
            $items['definition']);
        print sprintf('<td><a href="%s%d"><img src="images/delete.png" width="17" height="18" alt="%s" title="%s" border="0" /></a></td>', 
            '?action=deleteglossary&amp;id=', 
            $items['id'], 
            $PMF_LANG['ad_user_del_3'], 
            $PMF_LANG['ad_user_del_3']);
        print '</tr>';
    }
    print '</table>';

    print sprintf('<p>[ <a href="%s?action=addglossary">%s</a> ]</p>', $_SERVER['PHP_SELF'], $PMF_LANG['ad_glossary_add']);

} else {
    print $PMF_LANG["err_NotAuth"];
}
