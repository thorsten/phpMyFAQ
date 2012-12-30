<?php
/**
 * Translates a category
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
 * @author    Rudi Ferrari <bookcrossers@gmx.de>
 * @copyright 2006-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2006-09-10
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission["editcateg"]) {
    $category = new PMF_Category($faqConfig, array(), false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $category->getMissingCategories();
    $id     = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
    $header = sprintf('%s %s: <em>%s</em>',
        $PMF_LANG['ad_categ_trans_1'],
        $PMF_LANG['ad_categ_trans_2'],
        $category->categoryName[$id]['name']);

    $selectedLanguage = PMF_Filter::filterInput(INPUT_GET, 'trlang', FILTER_SANITIZE_STRING, $LANGCODE);
    if ($selectedLanguage !== $LANGCODE) {
        $action  = "showcategory";
        $showcat = "yes";
    } else {
        $action  = "updatecategory";
        $showcat = "no";
    }

    $user_permission  = $category->getPermissions('user', array($id));
    $group_permission = $category->getPermissions('group', array($id));
?>
        <header>
            <h2><?php print $header ?></h2>
        </header>
        <form class="form-horizontal" action="?action=updatecategory" method="post">
            <input type="hidden" name="id" value="<?php print $id; ?>" />
            <input type="hidden" name="parent_id" value="<?php print $category->categoryName[$id]["parent_id"]; ?>" />
            <input type="hidden" name="showcat" value="<?php print $showcat; ?>" />
            <?php if ($faqConfig->get('security.permLevel') !== 'basic'): ?>
            <input type="hidden" name="restricted_groups" value="<?php print $group_permission[0]; ?>" />
            <?php else: ?>
            <input type="hidden" name="restricted_groups" value="-1" />
            <?php endif; ?>
            <input type="hidden" name="restricted_users" value="<?php print $user_permission[0]; ?>" />
            <input type="hidden" name="csrf" value="<?php print $user->getCsrfTokenFromSession(); ?>" />

            <div class="control-group">
                <label class="control-label"><?php print $PMF_LANG["ad_categ_titel"]; ?>:</label>
                <div class="controls">
                    <input type="text" name="name" value="" />
                </div>
            </div>

            <div class="control-group">
                <label class="control-label"><?php print $PMF_LANG["ad_categ_lang"]; ?>:</label>
                <div class="controls">
                    <select name="catlang" size="1">
                        <?php print $category->getCategoryLanguagesToTranslate($id, $selectedLanguage); ?>
                    </select>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label"><?php print $PMF_LANG["ad_categ_desc"]; ?>:</label>
                <div class="controls">
                    <textarea name="description" rows="3" cols="80"></textarea>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label"><?php print $PMF_LANG["ad_categ_owner"]; ?>:</label>
                <div class="controls">
                    <select name="user_id" size="1">
                        <?php print $user->getAllUserOptions($category->categoryName[$id]['user_id']); ?>
                    </select>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label"><?php print $PMF_LANG['ad_categ_transalready']; ?></label>
                <div class="controls">
                    <ul>
                        <?php
                        foreach ($category->getCategoryLanguagesTranslated($id) as $language => $namedesc) {
                            print "<li><strong>" . $language . "</strong>: " . $namedesc . "</li>";
                        }
                        ?>
                    </ul>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit" name="submit">
                    <?php print $PMF_LANG["ad_categ_translatecateg"]; ?>
                </button>
            </div>

        </form>
<?php
} else {
    print $PMF_LANG["err_NotAuth"];
}
