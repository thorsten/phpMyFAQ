<?php
/**
 * Adds a record in the database, handles the preview and checks for missing
 * category entries.
 *
 * @package    phpMyFAQ
 * @subpackage Administration
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2003-02-23
 * @version    SVN: $Id$ 
 * @copyright  2003-2009 phpMyFAQ Team
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
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

// Re-evaluate $user
$user = PMF_User_CurrentUser::getFromSession($faqconfig->get('main.ipCheck'));

// Evaluate the passed validity range, if any
$dateStart = PMF_Filter::filterInput(INPUT_POST, 'dateStart', FILTER_SANITIZE_STRING);
$dateEnd   = PMF_Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_SANITIZE_STRING);

if ($permission["editbt"]) {
    $submit = $_POST["submit"];

    if (    isset($submit[1])
         && isset($_POST["thema"]) && $_POST["thema"] != ""
         && isset($_POST['rubrik']) && is_array($_POST['rubrik'])
       ) {
        // new entry
        adminlog("Beitragcreatesave");
        printf("<h2>%s</h2>\n", $PMF_LANG['ad_entry_aor']);

        $category = new PMF_Category($current_admin_user, $current_admin_groups, false);
        $tagging  = new PMF_Tags();

        // Get the data
        $categories     = $_POST['rubrik'];
        $tags           = $db->escape_string(trim($_POST['tags']));
        $userperm       = isset($_POST['userpermission']) ?
                          $db->escape_string($_POST['userpermission']) : 'all';
        $user_allowed   = ('all' == $userperm) ? -1 : (int)$_POST['restricted_users'];
        $groupperm      = isset($_POST['grouppermission']) ?
                          $db->escape_string($_POST['grouppermission']) : 'all';
        $group_allowed  = ('all' == $groupperm) ? -1 : (int)$_POST['restricted_groups'];

        $recordData     = array(
            'lang'          => $db->escape_string($_POST['language']),
            'active'        => $db->escape_string($_POST['active']),
            'sticky'        => (int)isset($_POST['sticky']) && 'on' == $_POST['sticky'],
            'thema'         => $db->escape_string($_POST['thema']),
            'content'       => $db->escape_string($_POST['content']),
            'keywords'      => $db->escape_string($_POST['keywords']),
            'author'        => $db->escape_string($_POST['author']),
            'email'         => $db->escape_string($_POST['email']),
            'comment'       => (isset($_POST['comment']) ? 'y' : 'n'),
            'date'          => date('YmdHis'),
            'dateStart'     => $db->escape_string($dateStart),
            'dateEnd'       => $db->escape_string($dateEnd),
            'linkState'     => '',
            'linkDateCheck' => 0
        );


        // Add new record and get that ID
        $record_id = $faq->addRecord($recordData);

        if ($record_id) {
            // Create ChangeLog entry
            $faq->createChangeEntry($record_id, $user->getUserId(), nl2br($db->escape_string($_POST['changed'])), $recordData['lang']);
            // Create the visit entry
            $visits = PMF_Visits::getInstance();
            $visits->add($record_id, $recordData['lang']);
            // Insert the new category relations
            $faq->addCategoryRelations($categories, $record_id, $recordData['lang']);
            // Insert the tags
            if ($tags != '') {
                $tagging->saveTags($record_id, explode(',',$tags));
            }
            // Add user permissions
            $faq->addPermission('user', $record_id, $user_allowed);
            $category->addPermission('user', $categories, $user_allowed);
            // Add group permission
            if ($groupSupport) {
                $faq->addPermission('group', $record_id, $group_allowed);
                $category->addPermission('group', $categories, $group_allowed);
            }

            print $PMF_LANG['ad_entry_savedsuc'];

            // Call Link Verification
            link_ondemand_javascript($record_id, $recordData['lang']);
        } else {
            print $PMF_LANG['ad_entry_savedfail'].$db->error();
        }

    } elseif (    isset($submit[2])
               && isset($_POST['thema']) && $_POST['thema'] != ""
               && isset($_POST['rubrik']) && is_array($_POST['rubrik'])
             ) {
        // Preview
        $rubrik = $_POST['rubrik'];
        $cat = new PMF_Category($current_admin_user, $current_admin_groups, false);
        $cat->transform(0);
        $categorylist = '';
        foreach ($rubrik as $categories) {
            $categorylist .= $cat->getPath($categories).'<br />';
        }
        if (isset($_REQUEST['id']) && $_REQUEST['id'] != '') {
            $id = $_REQUEST['id'];
        } else {
            $id = '';
        }
        $content = $_POST['content'];
?>
    <h3><strong><em><?php print $categorylist; ?></em>
    <?php print $_POST["thema"]; ?></strong></h3>
    <?php print $content; ?>
    <p class="little"><?php print $PMF_LANG["msgLastUpdateArticle"].makeDate(date("YmdHis")); ?><br />
    <?php print $PMF_LANG["msgAuthor"].' '.$_POST["author"]; ?></p>

    <form action="?action=editpreview" method="post">
    <input type="hidden" name="id"                  value="<?php print $id; ?>" />
    <input type="hidden" name="thema"               value="<?php print htmlspecialchars($_POST['thema']); ?>" />
    <input type="hidden" name="content" class="mceNoEditor" value="<?php print htmlspecialchars($_POST['content']); ?>" />
    <input type="hidden" name="lang"                value="<?php print $_POST['language']; ?>" />
    <input type="hidden" name="keywords"            value="<?php print $_POST['keywords']; ?>" />
    <input type="hidden" name="tags"                value="<?php print $_POST['tags']; ?>" />
    <input type="hidden" name="author"              value="<?php print $_POST['author']; ?>" />
    <input type="hidden" name="email"               value="<?php print $_POST['email']; ?>" />
    <input type="hidden" name="sticky"              value="<?php print (isset($_REQUEST['sticky']) ? $_REQUEST['sticky'] : ''); ?>" />
<?php
        foreach ($rubrik as $key => $categories) {
            print '    <input type="hidden" name="rubrik['.$key.']" value="'.$categories.'" />';
        }
?>
    <input type="hidden" name="solution_id"         value="<?php print (int)$_POST['solution_id']; ?>" />
    <input type="hidden" name="revision"            value="<?php print (isset($_POST['revision']) ? (int)$_POST['revision'] : ''); ?>" />
    <input type="hidden" name="active"              value="<?php print $_POST['active']; ?>" />
    <input type="hidden" name="changed"             value="<?php print $_POST['changed']; ?>" />
    <input type="hidden" name="comment"             value="<?php print (isset($_POST['comment']) ? $_POST['comment'] : ''); ?>" />
    <input type="hidden" name="dateStart"           value="<?php print $dateStart; ?>" />
    <input type="hidden" name="dateEnd"             value="<?php print $dateEnd; ?>" />
    <input type="hidden" name="userpermission"      value="<?php print $_POST['userpermission']; ?>" />
    <input type="hidden" name="restricted_users"    value="<?php print $_POST['restricted_users']; ?>" />
    <input type="hidden" name="grouppermission"     value="<?php print $_POST['grouppermission']; ?>" />
    <input type="hidden" name="restricted_group"    value="<?php print $_POST['restricted_group']; ?>" />
    <p align="center"><input class="submit" type="submit" name="submit" value="<?php print $PMF_LANG["ad_entry_back"]; ?>" /></p>
    </form>
<?php
    } else {
        printf("<h2>%s</h2>\n", $PMF_LANG['ad_entry_aor']);
        printf("<p>%s</p>", $PMF_LANG['ad_entryins_fail']);
        $rubrik = isset($_POST['rubrik']) ? $_POST['rubrik'] : null;
?>
    <form action="?action=editpreview" method="post">
    <input type="hidden" name="thema"               value="<?php print htmlspecialchars($_POST['thema']); ?>" />
    <input type="hidden" name="content" class="mceNoEditor" value="<?php print htmlspecialchars($_POST['content']); ?>" />
    <input type="hidden" name="lang"                value="<?php print $_POST['language']; ?>" />
    <input type="hidden" name="keywords"            value="<?php print $_POST['keywords']; ?>" />
    <input type="hidden" name="tags"                value="<?php print $_POST['tags']; ?>" />
    <input type="hidden" name="author"              value="<?php print $_POST['author']; ?>" />
    <input type="hidden" name="email"               value="<?php print $_POST['email']; ?>" />
<?php
        if (is_array($rubrik)) {
            foreach ($rubrik as $key => $categories) {
                print '    <input type="hidden" name="rubrik['.$key.']" value="'.$categories.'" />';
            }
        }
?>
    <input type="hidden" name="solution_id"         value="<?php print $_POST['solution_id']; ?>" />
    <input type="hidden" name="revision"            value="<?php print $_POST['revision']; ?>" />
    <input type="hidden" name="active"              value="<?php print $_POST['active']; ?>" />
    <input type="hidden" name="changed"             value="<?php print $_POST['changed']; ?>" />
    <input type="hidden" name="comment"             value="<?php print isset($_POST['comment']) ? $_POST['comment'] : ''; ?>" />
    <input type="hidden" name="dateStart"           value="<?php print $dateStart; ?>" />
    <input type="hidden" name="dateEnd"             value="<?php print $dateEnd; ?>" />
    <input type="hidden" name="userpermission"      value="<?php print $_POST['userpermission']; ?>" />
    <input type="hidden" name="restricted_users"    value="<?php print $_POST['restricted_users']; ?>" />
    <input type="hidden" name="grouppermission"     value="<?php print $_POST['grouppermission']; ?>" />
    <input type="hidden" name="restricted_group"    value="<?php print $_POST['restricted_group']; ?>" />
    <p align="center"><input class="submit" type="submit" name="submit" value="<?php print $PMF_LANG['ad_entry_back']; ?>" /></p>
    </form>
<?php
    }
} else {
    print $PMF_LANG['err_NotAuth'];
}
