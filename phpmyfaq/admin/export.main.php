<?php
/**
 * XML, XHTML and PDF export - main page
 * 
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ 
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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

        <form action="?action=exportfile" method="post">
<?php

if (!PMF_Db::checkOnEmptyTable('faqdata')) {

    if (!PMF_Db::checkOnEmptyTable('faqcategories')) {
        
        $categoryData   = new PMF_Category_Tree_DataProvider_SingleQuery($LANGCODE);
        $categoryLayout = new PMF_Category_Layout(new PMF_Category_Tree_Helper(new PMF_Category_Tree($categoryData)));
?>
            <fieldset>
                <legend><?php print($PMF_LANG['ad_export_which_cat']); ?></legend>
                <p>
                    <label for="catid"><?php print($PMF_LANG["ad_entry_category"]); ?></label>
                    <select name="catid" id="catid" size="1">
                        <option value="0"><?php print $PMF_LANG['msgShowAllCategories'] ?></option>
<?php
        print $categoryLayout->renderOptions();
?>
                    </select>
                </p>

                <p>
                    <label for="downwards"><?php print($PMF_LANG['ad_export_cat_downwards']); ?></label>
                    <input type="checkbox" name="downwards" id="downwards" value="1" checked="checked" />
                </p>
            </fieldset>
        
<?php
    }
?>
            <fieldset>
                <legend><?php print($PMF_LANG['ad_export_type']); ?></legend>
                <p>
                    <label ><?php print($PMF_LANG['ad_export_type_choose']); ?></label><br />
                    <input type="radio" name="type" value="pdf" checked="checked" />&nbsp;<?php print($PMF_LANG["ad_export_generate_pdf"]); ?><br />
                    <input type="radio" name="type" value="xml" />&nbsp;<?php print($PMF_LANG["ad_xml_gen"]); ?><br />
                    <input type="radio" name="type" value="xhtml" />&nbsp;<?php print($PMF_LANG['ad_export_gen_xhtml']); ?>
                </p>
                <p>
                    <label><?php print($PMF_LANG['ad_export_download_view']); ?></label>
                    <input type="radio" name="dispos" value="<?php print PMF_HttpStreamer::EXPORT_DISPOSITION_ATTACHMENT; ?>" checked="checked"><?php print($PMF_LANG['ad_export_download']); ?></input>
                    <input type="radio" name="dispos" value="<?php print PMF_HttpStreamer::EXPORT_DISPOSITION_INLINE; ?>"><?php print($PMF_LANG['ad_export_view']); ?></input>
                </p>
            </fieldset>

            <p>
                <input class="submit" type="submit" name="submitExport" value="<?php print(strip_tags($PMF_LANG["ad_menu_export"])); ?>" />
                <input class="submit" type="reset" name="resetExport" value="<?php print(strip_tags($PMF_LANG["ad_config_reset"])); ?>" />
            </p>
        </form>
<?php
} else {
    print($PMF_LANG["err_noArticles"]);
}