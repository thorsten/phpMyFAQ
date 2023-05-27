<?php

/**
 * JSON, HTML5 and PDF export - main page.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-04-17
 */

use phpMyFAQ\Category;
use phpMyFAQ\Database;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\HttpStreamer;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i aria-hidden="true" class="fa fa-book fa-fw"></i>
        <?= Translation::get('ad_menu_export') ?>
    </h1>
</div>

<div class="row">
    <div class="col-lg-12">
        <?php
        if ($user->perm->hasPermission($user->getUserId(), 'export') && !Database::checkOnEmptyTable('faqdata')) {
            if (!Database::checkOnEmptyTable('faqcategories')) {
                $category = new Category($faqConfig, [], false);
                $category->setUser($currentAdminUser);
                $category->setGroups($currentAdminGroups);
                $category->buildCategoryTree();

                $categoryHelper = new CategoryHelper();
                $categoryHelper->setCategory($category);
                ?>
                <form  action="?action=exportfile" method="post" accept-charset="utf-8">
                <h4><?= Translation::get('ad_export_which_cat') ?></h4>
                <div class="row mb-2">
                    <label class="col-lg-2 col-form-label" for="catid">
                        <?= Translation::get('ad_entry_category') ?>
                    </label>
                    <div class="col-lg-4">
                        <select name="catid" id="catid" class="form-select">
                            <option value="0"><?= Translation::get('msgShowAllCategories') ?></option>
                            <?= $categoryHelper->renderOptions(0) ?>
                        </select>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="offset-lg-2 col-lg-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="downwards" name="downwards"
                                   checked>
                            <label class="form-check-label" for="downwards">
                                <?= Translation::get('ad_export_cat_downwards') ?>
                            </label>
                        </div>

                    </div>
                </div>
                <?php
            }
            ?>
            <h4><?= Translation::get('ad_export_type') ?></h4>
            <div class="row mb-2">
                <div class="offset-lg-2 col-lg-8">
                    <p><?= Translation::get('ad_export_type_choose') ?></p>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="export-type" id="json" value="json" checked>
                        <label class="form-check-label" for="json">
                            <?= Translation::get('ad_export_generate_json') ?>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="export-type" id="pdf" value="pdf">
                        <label class="form-check-label" for="pdf">
                            <?= Translation::get('ad_export_generate_pdf') ?>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="export-type" id="html5" value="html5">
                        <label class="form-check-label" for="html5">
                            HTML5
                        </label>
                    </div>
                </div>
            </div>

            <div class="row mb-2">
                <div class="offset-lg-2 col-lg-4">
                    <p><?= Translation::get('ad_export_download_view') ?></p>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="disposition"
                               id="<?= HttpStreamer::EXPORT_DISPOSITION_ATTACHMENT; ?>"
                               value="<?= HttpStreamer::EXPORT_DISPOSITION_ATTACHMENT ?>" checked>
                        <label class="form-check-label" for="<?= HttpStreamer::EXPORT_DISPOSITION_ATTACHMENT; ?>">
                            <?= Translation::get('ad_export_download') ?>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="disposition"
                               id="<?= HttpStreamer::EXPORT_DISPOSITION_INLINE ?>"
                               value="<?= HttpStreamer::EXPORT_DISPOSITION_INLINE ?>">
                        <label class="form-check-label" for="<?= HttpStreamer::EXPORT_DISPOSITION_INLINE ?>">
                            <?= Translation::get('ad_export_view') ?>
                        </label>
                    </div>
                </div>
            </div>

            <div class="row mb-2">
                <div class="offset-lg-2 col-lg-4">
                    <button class="btn btn-secondary" type="reset" name="resetExport">
                        <?= Translation::get('ad_config_reset'); ?>
                    </button>
                    <button class="btn btn-primary" type="submit" name="submitExport" formtarget="_blank">
                        <?= Translation::get('ad_menu_export'); ?>
                    </button>
                </div>
            </div>
            </form>
            <?php
        } else {
            echo Translation::get('err_noArticles');
        }
        ?>
    </div>
</div>
