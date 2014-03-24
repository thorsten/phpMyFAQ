<?php
/**
 * The main glossary index file
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-15
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON'){
        $protocol = 'https';
    }
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

?>
        <header>
            <h2 class="page-header">
                <i class="fa fa-list-ul"></i> <?php echo $PMF_LANG['ad_menu_glossary'] ?>
                <div class="pull-right">
                    <a class="btn btn-success" href="?action=addglossary">
                        <i class="fa fa-plus fa fa-white"></i> <?php echo $PMF_LANG['ad_glossary_add'] ?>
                    </a>
                </div>
            </h2>
        </header>
<?php
if ($permission['addglossary'] || $permission['editglossary'] || $permission['delglossary']) {

    $glossary = new PMF_Glossary($faqConfig);

    if ('saveglossary' == $action && $permission['addglossary']) {
        $item       = PMF_Filter::filterInput(INPUT_POST, 'item', FILTER_SANITIZE_SPECIAL_CHARS);
        $definition = PMF_Filter::filterInput(INPUT_POST, 'definition', FILTER_SANITIZE_SPECIAL_CHARS);
        if ($glossary->addGlossaryItem($item, $definition)) {
            echo '<p class="alert alert-success"><a href="#" class="close" data-dismiss="alert">×</a>';
            echo $PMF_LANG['ad_glossary_save_success'] . '</p>';
        } else {
            echo '<p class="alert alert-danger"><a href="#" class="close" data-dismiss="alert">×</a>';
            echo $PMF_LANG['ad_glossary_save_error'];
            echo '<br />'.$PMF_LANG["ad_adus_dberr"].'<br />';
            echo $faqConfig->getDb()->error() . '</p>';
        }
    }

    if ('updateglossary' == $action && $permission['editglossary']) {
        $id         = PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $item       = PMF_Filter::filterInput(INPUT_POST, 'item', FILTER_SANITIZE_SPECIAL_CHARS);
        $definition = PMF_Filter::filterInput(INPUT_POST, 'definition', FILTER_SANITIZE_SPECIAL_CHARS);
        if ($glossary->updateGlossaryItem($id, $item, $definition)) {
            echo '<p class="alert alert-success"><a href="#" class="close" data-dismiss="alert">×</a>';
            echo $PMF_LANG['ad_glossary_update_success'] . '</p>';
        } else {
            echo '<p class="alert alert-danger"><a href="#" class="close" data-dismiss="alert">×</a>';
            echo $PMF_LANG['ad_glossary_update_error'];
            echo '<br />'.$PMF_LANG["ad_adus_dberr"].'<br />';
            echo $faqConfig->getDb()->error() . '</p>';
        }
    }

    if ('deleteglossary' == $action && $permission['editglossary']) {
        $id = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if ($glossary->deleteGlossaryItem($id)) {
            echo '<p class="alert alert-success"><a href="#" class="close" data-dismiss="alert">×</a>';
            echo $PMF_LANG['ad_glossary_delete_success'] . '</p>';
        } else {
            echo '<p class="alert alert-danger"><a href="#" class="close" data-dismiss="alert">×</a>';
            echo $PMF_LANG['ad_glossary_delete_error'];
            echo '<br />'.$PMF_LANG["ad_adus_dberr"].'<br />';
            echo $faqConfig->getDb()->error() . '</p>';
        }
    }

    $glossaryItems = $glossary->getAllGlossaryItems();

    echo '<table class="table table-striped">';
    printf(
        '<thead><tr><th>%s</th><th>%s</th><th style="width: 16px">&nbsp;</th></tr></thead>',
        $PMF_LANG['ad_glossary_item'], 
        $PMF_LANG['ad_glossary_definition']
    );

    foreach ($glossaryItems as $items) {
        echo '<tr>';
        printf(
            '<td><a href="%s%d">%s</a></td>',
            '?action=editglossary&amp;id=', 
            $items['id'], 
            $items['item']
        );
        printf(
            '<td>%s</td>',
            $items['definition']
        );
        printf(
            '<td><a class="btn btn-danger" onclick="return confirm(\'%s\'); return false;" href="%s%d">',
            $PMF_LANG['ad_user_del_3'],
            '?action=deleteglossary&amp;id=', 
            $items['id']
        );
        printf(
            '<span title="%s"><i class="fa fa-trash fa fa-white"></i></span></a></td>',
            $PMF_LANG['ad_entry_delete']
        );
        echo '</tr>';
    }
    echo '</table>';

} else {
    echo $PMF_LANG["err_NotAuth"];
}