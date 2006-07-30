<?php
/**
* $Id: export.main.php,v 1.24 2006-07-30 06:38:52 matteo Exp $
*
* XML, XML DocBook, XHTML and PDF export - main page
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Matteo Scaramuccia <matteo@scaramuccia.com>
* @author       Peter Beauvain <pbeauvain@web.de>
* @since        2003-04-17
* @copyright    (c) 2001-2006 phpMyFAQ Team
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
*/

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));

    exit();
}
// {{{ Includes
require_once("../inc/Export.php");
// }}}
?>
    <h2><?php print($PMF_LANG["ad_menu_export"]); ?></h2>
    <form method="get">
        <input type="hidden" name="uin" value="<?php isset($uin) ? print($uin) : '' ; ?>" />
        <input type="hidden" name="action" value="exportfile" />
<?php
if (!emptyTable(SQLPREFIX."faqdata")) {

    if (!emptyTable(SQLPREFIX."faqcategories")) {
        $tree = new PMF_Category();
        $tree->buildTree();
        // TODO: ENHANCEMENT/VERY LOW PRIORITY
        //       Give the user a multple selection and add support
        //       for multiple category ids (multiple="multiple")
        //       on export.php, $nCatid->$aCatid
        //       Currently the user can choose ONLY 1 category (not a big limitation)
?>
        <fieldset><legend><?php print($PMF_LANG['ad_export_which_cat']); ?></legend>
            <label class="left" for="rubrik"><?php print($PMF_LANG["ad_entry_category"]); ?></label>
            <select name="<?php print HTTP_PARAMS_GET_CATID; ?>" id="<?php print HTTP_PARAMS_GET_CATID; ?>" size="10">
<?php
        print($tree->printCategoryOptions());
?>
            </select>
            <br />
            <label class="left" for="<?php print(HTTP_PARAMS_GET_DOWNWARDS); ?>"><?php print($PMF_LANG['ad_export_cat_downwards']); ?></label>
            <input type="checkbox" name="<?php print(HTTP_PARAMS_GET_DOWNWARDS); ?>" value="1" checked="checked"></input>
        </fieldset>
        <br />
<?php
    }
?>
        <fieldset><legend><?php print($PMF_LANG['ad_export_type']); ?></legend>
            <p>
                <label for="export_type"><?php print($PMF_LANG['ad_export_type_choose']); ?></label><br />
                <input type="radio" name="type" value="<?php print(EXPORT_TYPE_PDF); ?>" checked="checked" />&nbsp;<?php print($PMF_LANG["ad_export_generate_pdf"]); ?><br />
                <input type="radio" name="type" value="<?php print(EXPORT_TYPE_XML); ?>" />&nbsp;<?php print($PMF_LANG["ad_xml_gen"]); ?><br />
                <input type="radio" name="type" value="<?php print(EXPORT_TYPE_XHTML); ?>" />&nbsp;<?php print($PMF_LANG['ad_export_gen_xhtml']); ?><br />
                <input type="radio" name="type" value="<?php print(EXPORT_TYPE_DOCBOOK); ?>" />&nbsp;<?php print($PMF_LANG['ad_export_gen_docbook']); ?>
            </p>
            <p>
                <label for="disposition"><?php print($PMF_LANG['ad_export_download_view']); ?></label>
                <input type="radio" name="<?php print(HTTP_PARAMS_GET_DISPOSITION); ?>" value="<?php print(EXPORT_DISPOSITION_ATTACHMENT); ?>" checked="checked"><?php print($PMF_LANG['ad_export_download']); ?></input>
                <input type="radio" name="<?php print(HTTP_PARAMS_GET_DISPOSITION); ?>" value="<?php print(EXPORT_DISPOSITION_INLINE); ?>"><?php print($PMF_LANG['ad_export_view']); ?></input>
            </p>
        </fieldset>
        <div align="center">
            <p>
                <input class="submit" type="submit" name="submitExport" value="<?php print(strip_tags($PMF_LANG["ad_menu_export"])); ?>" />
                &nbsp;<input class="submit" type="reset" name="resetExport" value="<?php print(strip_tags($PMF_LANG["ad_config_reset"])); ?>" />
            </p>
        </div>
    </form>
<?php
} else {
    print($PMF_LANG["err_noArticles"]);
}
?>
