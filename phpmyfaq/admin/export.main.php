<?php

/**
 * JSON, HTML5 and PDF export - main page.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2003-04-17
 */

use phpMyFAQ\Category;
use phpMyFAQ\Database;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\HttpStreamer;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}
?>
<header>
    <div class="col-lg-12">
        <h2 class="page-header"><i aria-hidden="true" class="fa fa-book fa-fw"></i> <?= $PMF_LANG['ad_menu_export'] ?>
        </h2>
    </div>
</header>

<div class="row">
    <div class="col-lg-12">
        <?php
        if ($user->perm->hasPermission($user->getUserId(), 'export') && !Database::checkOnEmptyTable('faqdata')) {
            if (!Database::checkOnEmptyTable('faqcategories')) {
                $category = new Category($faqConfig, array(), false);
                $category->setUser($currentAdminUser);
                $category->setGroups($currentAdminGroups);
                $category->buildCategoryTree();

                $categoryHelper = new CategoryHelper();
                $categoryHelper->setCategory($category);
                ?>
                <form  action="?action=exportfile" method="post" accept-charset="utf-8">
                <h5><?= $PMF_LANG['ad_export_which_cat'] ?></h5>
                <div class="form-group row">
                    <label class="col-lg-2 col-form-label" for="catid">
                        <?= $PMF_LANG['ad_entry_category'] ?>
                    </label>
                    <div class="col-lg-4">
                        <select name="catid" id="catid" class="form-control">
                            <option value="0"><?= $PMF_LANG['msgShowAllCategories'] ?></option>
                            <?= $categoryHelper->renderOptions(0) ?>
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="offset-lg-2 col-lg-4">
                        <div class="checkbox">
                            <label for="downwards">
                                <input type="checkbox" name="downwards" id="downwards" value="1" checked>
                                <?= $PMF_LANG['ad_export_cat_downwards'] ?>
                            </label>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
            <h5><?= $PMF_LANG['ad_export_type'] ?></h5>
            <div class="form-group row">
                <div class="offset-lg-2 col-lg-8 radio">
                    <p><?= $PMF_LANG['ad_export_type_choose'] ?></p>
                    <label>
                        <input type="radio" name="export-type" value="json" id="json" checked>
                        <?= $PMF_LANG['ad_export_generate_json'] ?>
                    </label>
                    <br>
                    <label>
                        <input type="radio" name="export-type" value="pdf" id="pdf">
                        <?= $PMF_LANG['ad_export_generate_pdf'] ?>
                    </label>
                    <br>
                    <label>
                        <input type="radio" name="export-type" value="html5" id="xhtml">
                        HTML5
                    </label>
                </div>
            </div>

            <div class="form-group row">
                <div class="offset-lg-2 col-lg-4 radio">
                    <p><?= $PMF_LANG['ad_export_download_view'] ?></p>
                    <label>
                        <input type="radio" name="disposition" value="<?= HttpStreamer::EXPORT_DISPOSITION_ATTACHMENT ?>"
                               id="<?= HttpStreamer::EXPORT_DISPOSITION_ATTACHMENT; ?>" checked>
                        <?= $PMF_LANG['ad_export_download'] ?>
                    </label>
                    <br>
                    <label>
                        <input type="radio" name="disposition" value="<?= HttpStreamer::EXPORT_DISPOSITION_INLINE ?>"
                               id="<?= HttpStreamer::EXPORT_DISPOSITION_INLINE ?>">
                        <?= $PMF_LANG['ad_export_view'] ?>
                    </label>
                </div>
            </div>

            <div class="form-group row">
                <div class="offset-lg-2 col-lg-4">
                    <button class="btn btn-primary" type="submit" name="submitExport">
                        <?= $PMF_LANG['ad_menu_export']; ?>
                    </button>
                    <button class="btn btn-info" type="reset" name="resetExport">
                        <?= $PMF_LANG['ad_config_reset']; ?>
                    </button>
                </div>
            </div>
            </form>
            <?php
        } else {
            echo $PMF_LANG['err_noArticles'];
        }
        ?>
    </div>
</div>
