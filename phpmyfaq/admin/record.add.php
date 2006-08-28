<?php
/**
* $Id: record.add.php,v 1.41 2006-08-28 19:56:05 thorstenr Exp $
*
* Adds a record in the database
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-02-23
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

// Re-evaluate $user
$user = PMF_CurrentUser::getFromSession($faqconfig->get('ipcheck'));

if ($permission["editbt"]) {
    $submit = $_POST["submit"];

    if (    isset($submit[1])
         && isset($_POST["thema"]) && $_POST["thema"] != ""
         && isset($_POST['rubrik']) && is_array($_POST['rubrik'])
       ) {
        // new entry
        adminlog("Beitragcreatesave");
        printf("<h2>%s</h2>\n", $PMF_LANG['ad_entry_aor']);

        $tagging = new PMF_Tags($db, $LANGCODE);

        $dateStart = $_POST['dateStartYYYY'] .
                     $_POST['dateStartMM'] .
                     $_POST['dateStartDD'] .
                     $_POST['dateStartHH'] .
                     $_POST['dateStartmm'] .
                     $_POST['dateStartss'];
        $dateStart = str_pad($dateStart, 14, '0', STR_PAD_RIGHT);
        $dateEnd   = $_POST['dateEndYYYY'] .
                     $_POST['dateEndMM'] .
                     $_POST['dateEndDD'] .
                     $_POST['dateEndHH'] .
                     $_POST['dateEndmm'] .
                     $_POST['dateEndss'];
        $dateEnd   = str_pad($dateEnd, 14, '0', STR_PAD_RIGHT);
        // Sanity checks
        if ('00000000000000' == $dateEnd) {
            $dateEnd = '99991231235959';
        }
        $recordData = array(
            'lang'          => $db->escape_string($_POST['language']),
            'active'        => $db->escape_string($_POST['active']),
            'thema'         => $db->escape_string($_POST['thema']),
            'content'       => $db->escape_string($_POST['content']),
            'keywords'      => $db->escape_string($_POST['keywords']),
            'author'        => $db->escape_string($_POST['author']),
            'email'         => $db->escape_string($_POST['email']),
            'comment'       => (isset($_POST['comment']) ? 'y' : 'n'),
            'date'          => date('YmdHis'),
            'dateStart'     => ('' == $dateStart) ? '00000000000000' : $db->escape_string($dateStart),
            'dateEnd'       => ('' == $dateEnd)   ? '99991231235959' : $db->escape_string($dateEnd),
            'linkState'     => '',
            'linkDateCheck' => 0
        );

        $categories = $_POST['rubrik'];
        $tags       = $db->escape_string($_POST['tags']);

        // Add new record and get that ID
        $nextID = $faq->addRecord($recordData);

        if ($nextID) {
            // Create ChangeLog entry
            $faq->createChangeEntry($nextID, $user->getUserId(), nl2br($db->escape_string($_POST["changed"])), $recordData['lang']);
            // Create the visit entry
            $faq->createNewVisit($nextID, $recordData['lang']);
            // Insert the new category relations
            $faq->addCategoryRelation($categories, $nextID, $recordData['lang']);
            // Insert the tags
            $tagging->saveTags($nextID, explode(' ',$tags));

            print $PMF_LANG["ad_entry_savedsuc"];

            // Call Link Verification
            link_ondemand_javascript($nextID, $recordData['lang']);
        } else {
            print $PMF_LANG["ad_entry_savedfail"].$db->error();
        }

    } elseif (    isset($submit[2])
               && isset($_POST["thema"]) && $_POST["thema"] != ""
               && isset($_POST['rubrik']) && is_array($_POST['rubrik'])
             ) {
        // Preview
        $rubrik = $_POST["rubrik"];
        $cat = new PMF_Category;
        $cat->transform(0);
        $categorylist = '';
        foreach ($rubrik as $categories) {
            $categorylist .= $cat->getPath($categories).'<br />';
        }
        if (isset($_REQUEST["id"]) && $_REQUEST["id"] != "") {
            $id = $_REQUEST["id"];
        } else {
            $id = "";
        }
        $content = $_POST['content'];
?>
    <h3><strong><em><?php print $categorylist; ?></em>
    <?php print $_POST["thema"]; ?></strong></h3>
    <?php print $content; ?>
    <p class="little"><?php print $PMF_LANG["msgLastUpdateArticle"].makeDate(date("YmdHis")); ?><br />
    <?php print $PMF_LANG["msgAuthor"].' '.$_POST["author"]; ?></p>

    <form action="<?php print $_SERVER['PHP_SELF'].$linkext; ?>&amp;action=editpreview" method="post">
    <input type="hidden" name="id"       value="<?php print $id; ?>" />
    <input type="hidden" name="thema"    value="<?php print htmlspecialchars($_POST["thema"]); ?>" />
    <input type="hidden" name="content"  value="<?php print htmlspecialchars($_POST['content']); ?>" />
    <input type="hidden" name="lang"     value="<?php print $_POST["language"]; ?>" />
    <input type="hidden" name="keywords" value="<?php print $_POST["keywords"]; ?>" />
    <input type="hidden" name="author"   value="<?php print $_POST["author"]; ?>" />
    <input type="hidden" name="email"    value="<?php print $_POST["email"]; ?>" />
<?php
        foreach ($rubrik as $key => $categories) {
            print '    <input type="hidden" name="rubrik['.$key.']" value="'.$categories.'" />';
        }
?>
    <input type="hidden" name="solution_id" value="<?php print $_POST["solution_id"]; ?>" />
    <input type="hidden" name="revision"    value="<?php print $_POST["revision"]; ?>" />
    <input type="hidden" name="active"      value="<?php print $_POST["active"]; ?>" />
    <input type="hidden" name="changed"     value="<?php print $_POST["changed"]; ?>" />
    <input type="hidden" name="comment"     value="<?php print $_POST["comment"]; ?>" />
    <p align="center"><input class="submit" type="submit" name="submit" value="<?php print $PMF_LANG["ad_entry_back"]; ?>" /></p>
    </form>
<?php
    } else {
        print "<h2>".$PMF_LANG["ad_entry_aor"]."</h2>\n";
        print "<p>".$PMF_LANG["ad_entryins_fail"]."</p>";
        $rubrik = isset($_POST['rubrik']) ? $_POST['rubrik'] : null;
?>
    <form action="<?php print $_SERVER['PHP_SELF'].$linkext; ?>&amp;action=editpreview" method="post">
    <input type="hidden" name="thema"    value="<?php print htmlspecialchars($_POST['thema']); ?>" />
    <input type="hidden" name="content"  value="<?php print htmlspecialchars($_POST['content']); ?>" />
    <input type="hidden" name="lang"     value="<?php print $_POST['language']; ?>" />
    <input type="hidden" name="keywords" value="<?php print $_POST['keywords']; ?>" />
    <input type="hidden" name="author"   value="<?php print $_POST['author']; ?>" />
    <input type="hidden" name="email"    value="<?php print $_POST['email']; ?>" />
<?php
        if (is_array($rubrik)) {
            foreach ($rubrik as $key => $categories) {
                print '    <input type="hidden" name="rubrik['.$key.']" value="'.$categories.'" />';
            }
        }
?>
    <input type="hidden" name="solution_id" value="<?php print $_POST["solution_id"]; ?>" />
    <input type="hidden" name="revision"    value="<?php print $_POST["revision"]; ?>" />
    <input type="hidden" name="active"      value="<?php print $_POST['active']; ?>" />
    <input type="hidden" name="changed"     value="<?php print $_POST['changed']; ?>" />
    <input type="hidden" name="comment"     value="<?php print isset($_POST['comment']) ? $_POST['comment'] : ''; ?>" />
    <p align="center"><input class="submit" type="submit" name="submit" value="<?php print $PMF_LANG['ad_entry_back']; ?>" /></p>
    </form>
<?php
    }
} else {
    print $PMF_LANG["err_NotAuth"];
}
