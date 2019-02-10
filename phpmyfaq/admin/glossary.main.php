<?php
/**
 * The main glossary index file.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-15
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}
?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header">
                    <i aria-hidden="true" class="fa fa-list-ul"></i> <?php echo $PMF_LANG['ad_menu_glossary'] ?>
                    <div class="pull-right">
                        <a class="btn btn-success" href="?action=addglossary">
                            <i aria-hidden="true" class="fa fa-plus fa-fw"></i> <?php echo $PMF_LANG['ad_glossary_add'] ?>
                        </a>
                    </div>
                </h2>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-12">
<?php

$csrfTokenFromPost = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
$csrfTokenFromGet = PMF_Filter::filterInput(INPUT_GET, 'csrf', FILTER_SANITIZE_STRING);
if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfTokenFromPost) {
    $csrfCheck = false;
} else {
    $csrfCheck = true;
}
if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfTokenFromGet) {
    $csrfCheckDelete = false;
} else {
    $csrfCheckDelete = true;
}

if ($user->perm->checkRight($user->getUserId(), 'addglossary') ||
    $user->perm->checkRight($user->getUserId(), 'editglossary') ||
    $user->perm->checkRight($user->getUserId(), 'delglossary')) {
    $glossary = new PMF_Glossary($faqConfig);

    if ('saveglossary' == $action && $user->perm->checkRight($user->getUserId(), 'addglossary') && $csrfCheck) {
        $item = PMF_Filter::filterInput(INPUT_POST, 'item', FILTER_SANITIZE_SPECIAL_CHARS);
        $definition = PMF_Filter::filterInput(INPUT_POST, 'definition', FILTER_SANITIZE_SPECIAL_CHARS);
        if ($glossary->addGlossaryItem($item, $definition)) {
            echo '<p class="alert alert-success"><a href="#" class="close" data-dismiss="alert">×</a>';
            echo $PMF_LANG['ad_glossary_save_success'].'</p>';
        } else {
            echo '<p class="alert alert-danger"><a href="#" class="close" data-dismiss="alert">×</a>';
            echo $PMF_LANG['ad_glossary_save_error'];
            echo '<br />'.$PMF_LANG['ad_adus_dberr'].'<br />';
            echo $faqConfig->getDb()->error().'</p>';
        }
    }

    if ('updateglossary' == $action && $user->perm->checkRight($user->getUserId(), 'editglossary') && $csrfCheck) {
        $id = PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $item = PMF_Filter::filterInput(INPUT_POST, 'item', FILTER_SANITIZE_SPECIAL_CHARS);
        $definition = PMF_Filter::filterInput(INPUT_POST, 'definition', FILTER_SANITIZE_SPECIAL_CHARS);
        if ($glossary->updateGlossaryItem($id, $item, $definition)) {
            echo '<p class="alert alert-success"><a href="#" class="close" data-dismiss="alert">×</a>';
            echo $PMF_LANG['ad_glossary_update_success'].'</p>';
        } else {
            echo '<p class="alert alert-danger"><a href="#" class="close" data-dismiss="alert">×</a>';
            echo $PMF_LANG['ad_glossary_update_error'];
            echo '<br />'.$PMF_LANG['ad_adus_dberr'].'<br />';
            echo $faqConfig->getDb()->error().'</p>';
        }
    }

    if ('deleteglossary' == $action && $user->perm->checkRight($user->getUserId(), 'editglossary') && $csrfCheckDelete) {
        $id = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if ($glossary->deleteGlossaryItem($id)) {
            echo '<p class="alert alert-success"><a href="#" class="close" data-dismiss="alert">×</a>';
            echo $PMF_LANG['ad_glossary_delete_success'].'</p>';
        } else {
            echo '<p class="alert alert-danger"><a href="#" class="close" data-dismiss="alert">×</a>';
            echo $PMF_LANG['ad_glossary_delete_error'];
            echo '<br />'.$PMF_LANG['ad_adus_dberr'].'<br />';
            echo $faqConfig->getDb()->error().'</p>';
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
            '<td><a class="btn btn-danger" onclick="return confirm(\'%s\'); return false;" href="%s%d%s%s">',
            $PMF_LANG['ad_user_del_3'],
            '?action=deleteglossary&amp;id=',
            $items['id'],
            '&csrf=',
            $user->getCsrfTokenFromSession()
        );
        printf(
            '<span title="%s"><i aria-hidden="true" class="fa fa-trash-o"></i></span></a></td>',
            $PMF_LANG['ad_entry_delete']
        );
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo $PMF_LANG['err_NotAuth'];
}
?>
            </div>
        </div>