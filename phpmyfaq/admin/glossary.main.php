<?php
/**
 * The main glossary index file
 *
 * PHP Version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-15
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

printf('<header><h2>%s</h2></header>', $PMF_LANG['ad_menu_glossary']);

if ($permission['addglossary'] || $permission['editglossary'] || $permission['delglossary']) {

    $glossary = new PMF_Glossary($faqConfig);

    if ('saveglossary' == $action && $permission['addglossary']) {
        $item       = PMF_Filter::filterInput(INPUT_POST, 'item', FILTER_SANITIZE_STRIPPED);
        $definition = PMF_Filter::filterInput(INPUT_POST, 'definition', FILTER_SANITIZE_STRIPPED);
        if ($glossary->addGlossaryItem($item, $definition)) {
            print '<p class="alert alert-success">' . $PMF_LANG['ad_glossary_save_success'] . '</p>';
        } else {
            print '<p class="alert alert-error">' . $PMF_LANG['ad_glossary_save_error'];
            print '<br />'.$PMF_LANG["ad_adus_dberr"].'<br />';
            print $db->error() . '</p>';
        }
    }

    if ('updateglossary' == $action && $permission['editglossary']) {
        $id         = PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $item       = PMF_Filter::filterInput(INPUT_POST, 'item', FILTER_SANITIZE_STRIPPED);
        $definition = PMF_Filter::filterInput(INPUT_POST, 'definition', FILTER_SANITIZE_STRIPPED);
        if ($glossary->updateGlossaryItem($id, $item, $definition)) {
            print '<p class="alert alert-success">' . $PMF_LANG['ad_glossary_update_success'] . '</p>';
        } else {
            print '<p class="alert alert-error">' . $PMF_LANG['ad_glossary_update_error'];
            print '<br />'.$PMF_LANG["ad_adus_dberr"].'<br />';
            print $db->error() . '</p>';
        }
    }

    if ('deleteglossary' == $action && $permission['editglossary']) {
        $id = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if ($glossary->deleteGlossaryItem($id)) {
            print '<p class="alert alert-success">' . $PMF_LANG['ad_glossary_delete_success'] . '</p>';
        } else {
            print '<p class="alert alert-error">' . $PMF_LANG['ad_glossary_delete_error'];
            print '<br />'.$PMF_LANG["ad_adus_dberr"].'<br />';
            print $db->error() . '</p>';
        }
    }

    $glossaryItems = $glossary->getAllGlossaryItems();

    print sprintf('<p>[ <a href="?action=addglossary">%s</a> ]</p>', $PMF_LANG['ad_glossary_add']);

    print '<table class="table table-striped">';
    print sprintf("<thead><tr><th class=\"list\">%s</th><th class=\"list\">%s</th><th style=\"width: 16px\">&nbsp;</th></tr></thead>", 
        $PMF_LANG['ad_glossary_item'], 
        $PMF_LANG['ad_glossary_definition']);

    foreach ($glossaryItems as $items) {
        print '<tr>';
        printf('<td><a href="%s%d">%s</a></td>', 
            '?action=editglossary&amp;id=', 
            $items['id'], 
            $items['item']);
        printf('<td>%s</td>', 
            $items['definition']);
        printf('<td><a onclick="return confirm(\'%s\'); return false;" href="%s%d">',
            $PMF_LANG['ad_user_del_3'],
            '?action=deleteglossary&amp;id=', 
            $items['id']);
        printf('<img src="images/delete.png" width="16" height="16" alt="%s" title="%s" border="0" /></a></td>', 
            $PMF_LANG['ad_entry_delete'], 
            $PMF_LANG['ad_entry_delete']);
        print '</tr>';
    }
    print '</table>';

    print sprintf('<p>[ <a href="?action=addglossary">%s</a> ]</p>', $PMF_LANG['ad_glossary_add']);

} else {
    print $PMF_LANG["err_NotAuth"];
}