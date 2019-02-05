<?php
/**
 * Displays a form to add a glossary item.
 *
 *
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2005-09-15
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
                    <i aria-hidden="true" class="fas fa-list-ul"></i> <?= $PMF_LANG['ad_glossary_add'] ?>
                </h2>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-12">

<?php
if ($user->perm->checkRight($user->getUserId(), 'addglossary')) {
    ?>
                <form  action="?action=saveglossary" method="post" accept-charset="utf-8">
                    <input type="hidden" name="csrf" value="<?= $user->getCsrfTokenFromSession() ?>">

                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="item"><?= $PMF_LANG['ad_glossary_item'] ?>:</label>
                        <div class="col-lg-4">
                            <input class="form-control" type="text" name="item" id="item" required>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-2 col-form-label" for="definition">
                            <?= $PMF_LANG['ad_glossary_definition'] ?>:
                        </label>
                        <div class="col-lg-4">
                            <textarea class="form-control" name="definition" id="definition" cols="50" rows="5" required></textarea>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="offset-lg-2 col-lg-4">
                            <button class="btn btn-primary" type="submit">
                                <?= $PMF_LANG['ad_glossary_save'] ?>
                            </button>
                            <a class="btn btn-info" href="?action=glossary">
                                <?= $PMF_LANG['ad_entry_back'] ?>
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
