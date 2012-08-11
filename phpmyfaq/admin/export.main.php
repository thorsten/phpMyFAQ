<?php
/**
 * XML, XHTML and PDF export - main page
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ 
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-04-17
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

require PMF_INCLUDE_DIR . '/Export.php';
?>
        <header>
            <h2><?php print($PMF_LANG["ad_menu_export"]); ?></h2>
        </header>

        <form class="form-horizontal" action="?action=exportfile" method="post">
<?php

if (!PMF_Db::checkOnEmptyTable('faqdata')) {

    if (!PMF_Db::checkOnEmptyTable('faqcategories')) {
        $category = new PMF_Category($faqConfig);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $category->buildTree();

        $categoryHelper = new PMF_Helper_Category();
        $categoryHelper->setCategory($category);
?>
            <fieldset>
                <legend><?php print($PMF_LANG['ad_export_which_cat']); ?></legend>
                <div class="control-group">
                    <label class="control-label" for="catid"><?php print($PMF_LANG["ad_entry_category"]); ?></label>
                    <div class="controls">
                        <select name="catid" id="catid" size="1">
                            <option value="0"><?php print $PMF_LANG['msgShowAllCategories'] ?></option>
                            <?php print $categoryHelper->renderOptions(); ?>
                        </select>
                    </div>
                </div>

                <div class="control-group">
                    <div class="controls">
                        <label class="checkbox" for="downwards">
                            <input type="checkbox" name="downwards" id="downwards" value="1" checked="checked" />
                            <?php print($PMF_LANG['ad_export_cat_downwards']); ?>
                        </label>
                    </div>
                </div>
            </fieldset>

<?php
    }
?>
            <fieldset>
                <legend><?php print($PMF_LANG['ad_export_type']); ?></legend>
                <div class="control-group">
                    <div class="controls">
                        <label><?php print($PMF_LANG['ad_export_type_choose']); ?></label>
                        <label class="radio">
                            <input type="radio" name="type" value="pdf" checked="checked" />
                            <?php print($PMF_LANG["ad_export_generate_pdf"]); ?>
                        </label>
                        <label class="radio">
                            <input type="radio" name="type" value="xml" />
                            <?php print($PMF_LANG["ad_xml_gen"]); ?>
                        </label>
                        <label class="radio">
                            <input type="radio" name="type" value="xhtml" />
                            <?php print($PMF_LANG['ad_export_gen_xhtml']); ?>
                        </label>
                    </div>
                </div>
                <div class="control-group">
                    <div class="controls">
                        <label><?php print($PMF_LANG['ad_export_download_view']); ?></label>
                        <label class="radio">
                            <input type="radio" name="dispos" value="<?php print PMF_HttpStreamer::EXPORT_DISPOSITION_ATTACHMENT; ?>" checked="checked" />
                            <?php print($PMF_LANG['ad_export_download']); ?>
                        </label>
                        <label class="radio">
                            <input type="radio" name="dispos" value="<?php print PMF_HttpStreamer::EXPORT_DISPOSITION_INLINE; ?>">
                            <?php print($PMF_LANG['ad_export_view']); ?>
                        </label>
                    </div>
                </div>
            </fieldset>

            <div class="form-actions">
                <input class="btn-primary" type="submit" name="submitExport" value="<?php print(strip_tags($PMF_LANG["ad_menu_export"])); ?>" />
                <input class="btn-info" type="reset" name="resetExport" value="<?php print(strip_tags($PMF_LANG["ad_config_reset"])); ?>" />
            </div>
        </form>
<?php
} else {
    print($PMF_LANG["err_noArticles"]);
}