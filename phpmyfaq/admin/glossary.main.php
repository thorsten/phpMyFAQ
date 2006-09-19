<?php
/**
* $Id: glossary.main.php,v 1.12 2006-09-19 21:39:39 matteo Exp $
*
* The main glossary index file
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2005-09-15
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
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

print sprintf('<h2>%s</h2>', $PMF_LANG['ad_menu_glossary']);

if ($permission['addglossary'] || $permission['editglossary'] || $permission['delglossary']) {

    require_once('../inc/Glossary.php');
    $glossary = new PMF_Glossary($db, $LANGCODE);

    if ('saveglossary' == $_action && $permission['addglossary']) {
        if ($glossary->addGlossaryItem($_POST['item'], $_POST['definition'])) {
            print '<p>' . $PMF_LANG['ad_glossary_save_success'] . '</p>';
        } else {
            print '<p>' . $PMF_LANG['ad_glossary_save_error'];
            print '<br />'.$PMF_LANG["ad_adus_dberr"].'<br />';
            print $db->error() . '</p>';
        }
    }

    if ('updateglossary' == $_action && $permission['editglossary']) {
        if ($glossary->updateGlossaryItem($_POST['id'], $_POST['item'], $_POST['definition'])) {
            print '<p>' . $PMF_LANG['ad_glossary_update_success'] . '</p>';
        } else {
            print '<p>' . $PMF_LANG['ad_glossary_update_error'];
            print '<br />'.$PMF_LANG["ad_adus_dberr"].'<br />';
            print $db->error() . '</p>';
        }
    }

    if ('deleteglossary' == $_action && $permission['editglossary']) {
        if ($glossary->deleteGlossaryItem($_GET['id'])) {
            print '<p>' . $PMF_LANG['ad_glossary_delete_success'] . '</p>';
        } else {
            print '<p>' . $PMF_LANG['ad_glossary_delete_error'];
            print '<br />'.$PMF_LANG["ad_adus_dberr"].'<br />';
            print $db->error() . '</p>';
        }
    }

    $glossaryItems = $glossary->getAllGlossaryItems();

    print '<table class="list">';
    print sprintf("<tr><th class=\"list\">%s</th><th class=\"list\">%s</th><th>&nbsp;</th></tr>", $PMF_LANG['ad_glossary_item'], $PMF_LANG['ad_glossary_definition']);

    foreach ($glossaryItems as $items) {
        print '<tr>';
        print sprintf('<td class="list"><a href="%s%d">%s</a></td>', $linkext.'&amp;action=editglossary&amp;id=', $items['id'], $items['item']);
        print sprintf('<td class="list">%s</td>', $items['definition']);
        print sprintf('<td class="list"><a href="%s%d"><img src="images/delete.gif" width="17" height="18" alt="%s" title="%s" border="0" /></a></td>', $linkext.'&amp;action=deleteglossary&amp;id=', $items['id'], $PMF_LANG['ad_user_del_3'], $PMF_LANG['ad_user_del_3']);
        print '</tr>';
    }
    print '</table>';

    print sprintf('<p>[ <a href="%s&amp;action=addglossary">%s</a> ]</p>', $_SERVER['PHP_SELF'].$linkext, $PMF_LANG['ad_glossary_add']);

} else {
    print $PMF_LANG["err_NotAuth"];
}
