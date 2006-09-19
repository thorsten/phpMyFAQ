<?php
/**
* NEW - Name category.translate.php
*
* translates a category
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Rudi Ferrari <bookcrossers@gmx.de>
* @since        2006-09-10
* @copyright    (c) 2003-2006 phpMyFAQ Team
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

if ($permission["editcateg"]) {
    $cat = new PMF_Category($LANGCODE);
    $cat->getMissingCategories();
    $id = $_GET["cat"];
    print "<h2>".$PMF_LANG["ad_categ_trans_1"]." <em>".$cat->categoryName[$id]["name"]."</em> ".$PMF_LANG["ad_categ_trans_2"]."</h2>";
?>
    <form action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>" method="post">
    <fieldset>
    <legend><?php print $PMF_LANG["ad_categ_trans_1"]." <em>".$cat->categoryName[$id]["name"]."</em> ".$PMF_LANG["ad_categ_trans_2"]; ?></legend>

        <input type="hidden" name="action" value="updatecategory" />
        <input type="hidden" name="id" value="<?php print $id; ?>" />
        <input type="hidden" name="parent_id" value="<?php print $cat->categoryName[$id]["parent_id"]; ?>" />

        <label class="left"><?php print $PMF_LANG["ad_categ_titel"]; ?>:</label>
        <input type="text" name="name" size="30" style="width: 250px;" value="" /><br />

        <label class="left"><?php print $PMF_LANG["ad_categ_lang"]; ?>:</label>
        <select name="lang" size="1">
        <?php print $cat->getCategoryLanguagesToTranslate($id, $LANGCODE); ?>
        </select><br />

        <label class="left"><?php print $PMF_LANG["ad_categ_desc"]; ?>:</label>
        <input type="text" name="description" size="30" style="width: 250px;" value="" /><br />

        <label class="left"><?php print $PMF_LANG["ad_categ_owner"]; ?>:</label>
        <select name="user_id" size="1">
        <?php print $user->getAllUserOptions($cat->categories[$id]["user_id"]); ?>
        </select><br />

        <input class="submit" style="margin-left: 190px;" type="submit" name="submit" value="<?php print $PMF_LANG["ad_categ_translatecateg"]; ?>" /> 
        <br /><hr />
        <?php
           print '<strong>'.$PMF_LANG["ad_categ_transalready"].'</strong><br />';
           foreach ($cat->getCategoryLanguagesTranslated($id) as $language => $namedesc) {
              print "&nbsp;&nbsp;&nbsp;<strong style=\"vertical-align: top;\">&middot; " . $language . "</strong>: " . $namedesc . "\n<br />";
           }
        ?>
    </fieldset>
    </form>
<?php
} else {
    print $PMF_LANG["err_NotAuth"];
}
