<?php
/**
 * Cuts out a category.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2017 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      http://www.phpmyfaq.de
 * @since     2003-12-25
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($user->perm->checkRight($user->getUserId(), 'editcateg')) {
    $category = new PMF_Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $category->buildTree();

<<<<<<< HEAD
    $id        = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);
    $parent_id = $category->categoryName[$id]['parent_id'];

    $templateVars = array(
        'PMF_LANG'             => $PMF_LANG,
        'categoryName'         => $category->categoryName[$id]['name'],
        'categoryOptions'      => array(),
        'csrfToken'            => $user->getCsrfTokenFromSession(),
        'displayMainCatOption' => $parent_id != 0,
        'id'                   => $id
    );

    foreach ($category->catTree as $cat) {
        $indent = str_repeat('…', $cat['indent']);
=======
    $id = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);
    $parent_id = $category->categoryName[$id]['parent_id'];
    $header = sprintf('%s: <em>%s</em>', $PMF_LANG['ad_categ_move'], $category->categoryName[$id]['name']);
    ?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header"><i aria-hidden="true" class="fa fa-list"></i> <?php echo $header ?></h2>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-12">
                <form class="form-horizontal" action="?action=pastecategory" method="post" accept-charset="utf-8">
                    <input type="hidden" name="cat" value="<?php echo $id;
    ?>">
                    <input type="hidden" name="csrf" value="<?php echo $user->getCsrfTokenFromSession();
    ?>">
                    <div class="form-group">
                        <label class="col-lg-2 control-label"><?php echo $PMF_LANG['ad_categ_paste2'];
    ?></label>
                        <div class="col-lg-4">
                            <select name="after" size="1" class="form-control">
<?php

    foreach ($category->catTree as $cat) {
        $indent = '';
        for ($j = 0; $j < $cat['indent']; ++$j) {
            $indent .= '...';
        }
>>>>>>> 2.10
        if ($id != $cat['id']) {
            $templateVars['categoryOptions'][$cat['id']] = $indent . $cat['name'];
        }
    }

    $twig->loadTemplate('category/cut.twig')
        ->display($templateVars);

<<<<<<< HEAD
    unset($templateVars, $category, $id, $cat, $indent);
} else {
    require 'noperm.php';
}
=======
    ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-4">
                            <button class="btn btn-primary" type="submit" name="submit">
                                <?php echo $PMF_LANG['ad_categ_updatecateg'];
    ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
<?php

} else {
    echo $PMF_LANG['err_NotAuth'];
}
>>>>>>> 2.10
