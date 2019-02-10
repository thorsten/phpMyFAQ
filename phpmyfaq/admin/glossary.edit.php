<?php
/**
 * Displays a form to edit an existing glossary item.
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
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
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
                    <i aria-hidden="true" class="fa fa-list-ul"></i> <?php echo $PMF_LANG['ad_glossary_edit'] ?>
                </h2>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-12">
<?php
if ($user->perm->checkRight($user->getUserId(), 'editglossary')) {
    $id = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $glossary = new PMF_Glossary($faqConfig);
    $glossaryItem = $glossary->getGlossaryItem($id);
    ?>
                <form class="form-horizontal" action="?action=updateglossary" method="post" accept-charset="utf-8">
                    <input type="hidden" name="csrf" value="<?php echo $user->getCsrfTokenFromSession() ?>">
                    <input type="hidden" name="id" value="<?php echo $glossaryItem['id'];
    ?>" />
                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="item"><?php echo $PMF_LANG['ad_glossary_item'];
    ?>:</label>
                        <div class="col-lg-4">
                            <input class="form-control" type="text" name="item" id="item"
                                   value="<?php echo $glossaryItem['item'];
    ?>" required>
                        </div>
                    </div>

            <div class="control-group">
                <label class="control-label" for="definition">
                    <?php echo $PMF_LANG['ad_glossary_definition'];
    ?>:
                </label>
                <div class="controls">
                    <textarea  class="input-xxlarge" name="definition" id="definition" cols="50" rows="3" required><?php echo $glossaryItem['definition'];
    ?></textarea>
                </div>
            </div>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-4">
                            <button class="btn btn-primary" type="submit">
                                <?php echo $PMF_LANG['ad_glossary_save'];
    ?>
                            </button>
                            <a class="btn btn-info" href="?action=glossary">
                                <?php echo $PMF_LANG['ad_entry_back'];
    ?>
                            </a>
                        </div>
                    </div>
                </form>
<?php

} else {
    echo $PMF_LANG['err_NotAuth'];
}
?>
            </div>
        </div>