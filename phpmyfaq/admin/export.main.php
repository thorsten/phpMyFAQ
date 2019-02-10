<?php
/**
 * XML, XHTML and PDF export - main page.
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
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2003-04-17
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
        <header>
            <div class="col-lg-12">
                <h2 class="page-header"><i aria-hidden="true" class="fa fa-book fa-fw"></i> <?php echo $PMF_LANG['ad_menu_export'] ?></h2>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-12">
<?php
if ($user->perm->checkRight($user->getUserId(), 'export') && !PMF_Db::checkOnEmptyTable('faqdata')) {
    if (!PMF_Db::checkOnEmptyTable('faqcategories')) {
        $category = new PMF_Category($faqConfig, array(), false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $category->buildTree();

        $categoryHelper = new PMF_Helper_Category();
        $categoryHelper->setCategory($category);
        ?>
                <form class="form-horizontal" action="?action=exportfile" method="post" accept-charset="utf-8">
                    <h4><?php echo $PMF_LANG['ad_export_which_cat'] ?></h4>
                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="catid">
                            <?php echo $PMF_LANG['ad_entry_category'];
        ?>
                        </label>
                        <div class="col-lg-4">
                            <select name="catid" id="catid" size="1" class="form-control">
                                <option value="0"><?php echo $PMF_LANG['msgShowAllCategories'] ?></option>
                                <?php echo $categoryHelper->renderOptions(0);
        ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-4">
                            <div class="checkbox">
                                <label for="downwards">
                                    <input type="checkbox" name="downwards" id="downwards" value="1" checked>
                                    <?php echo $PMF_LANG['ad_export_cat_downwards'] ?>
                                </label>
                            </div>
                        </div>
                    </div>
<?php

    }
    ?>
                    <h4><?php echo $PMF_LANG['ad_export_type'] ?></h4>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-8 radio">
                            <p><?php echo $PMF_LANG['ad_export_type_choose'] ?></p>
                            <label>
                                <input type="radio" name="export-type" value="json" id="json" checked>
                                <?php echo $PMF_LANG['ad_export_generate_json'] ?>
                            </label>
                            <br>
                            <label>
                                <input type="radio" name="export-type" value="pdf" id="pdf">
                                <?php echo $PMF_LANG['ad_export_generate_pdf'] ?>
                            </label>
                            <br>
                            <label>
                                <input type="radio" name="export-type" value="xml" id="xml">
                                <?php echo $PMF_LANG['ad_xml_gen'] ?>
                            </label>
                            <br>
                            <label>
                                <input type="radio" name="export-type" value="xhtml" id="xhtml">
                                <?php echo $PMF_LANG['ad_export_gen_xhtml'] ?>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-4 radio">
                            <p><?php echo $PMF_LANG['ad_export_download_view'] ?></p>
                            <label>
                                <input type="radio" name="dispos" value="<?php echo PMF_HttpStreamer::EXPORT_DISPOSITION_ATTACHMENT ?>"
                                       id="<?php echo PMF_HttpStreamer::EXPORT_DISPOSITION_ATTACHMENT;
    ?>" checked>
                                    <?php echo $PMF_LANG['ad_export_download'] ?>
                            </label>
                            <br>
                            <label>
                                <input type="radio" name="dispos" value="<?php echo PMF_HttpStreamer::EXPORT_DISPOSITION_INLINE ?>"
                                       id="<?php echo PMF_HttpStreamer::EXPORT_DISPOSITION_INLINE ?>">
                                <?php echo $PMF_LANG['ad_export_view'] ?>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-4">
                            <button class="btn btn-primary" type="submit" name="submitExport">
                                <?php echo $PMF_LANG['ad_menu_export'];
    ?>
                            </button>
                            <button class="btn btn-info" type="reset" name="resetExport">
                                <?php echo $PMF_LANG['ad_config_reset'];
    ?>
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
