<?php
/**
 * $Id: record.save.php,v 1.62 2007-05-15 18:51:46 thorstenr Exp $
 *
 * Save or delete a FAQ record
 *
 * @author       Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since        2003-02-23
 * @copyright    (c) 2003-2007 phpMyFAQ Team
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

$submit = $_REQUEST["submit"];
// Re-evaluate $user
$user = PMF_CurrentUser::getFromSession($faqconfig->get('main.ipCheck'));

// Evaluate the passed validity range, if any
$dateStart =
    (isset($_POST['dateStartYYYY']) && !empty($_POST['dateStartYYYY']) ? str_pad((int)$_POST['dateStartYYYY'], 4, '0', STR_PAD_LEFT) : '0001') .
    (isset($_POST['dateStartMM']) && !empty($_POST['dateStartMM']) ? str_pad((int)$_POST['dateStartMM'], 2, '0', STR_PAD_LEFT) : '01') .
    (isset($_POST['dateStartDD']) && !empty($_POST['dateStartDD']) ? str_pad((int)$_POST['dateStartDD'], 2, '0', STR_PAD_LEFT) : '01') .
    (isset($_POST['dateStartHH']) && !empty($_POST['dateStartHH']) ? str_pad((int)$_POST['dateStartHH'], 2, '0', STR_PAD_LEFT) : '00') .
    (isset($_POST['dateStartmm']) && !empty($_POST['dateStartss']) ? str_pad((int)$_POST['dateStartmm'], 2, '0', STR_PAD_LEFT) : '00') .
    (isset($_POST['dateStartss']) && !empty($_POST['dateStartMM']) ? str_pad((int)$_POST['dateStartss'], 2, '0', STR_PAD_LEFT) : '00');
$dateEnd =
    (isset($_POST['dateEndYYYY']) ? str_pad((int)$_POST['dateEndYYYY'], 4, '0', STR_PAD_LEFT) : '0000') .
    (isset($_POST['dateEndMM']) && !empty($_POST['dateEndMM']) ? str_pad((int)$_POST['dateEndMM'], 2, '0', STR_PAD_LEFT) : '01') .
    (isset($_POST['dateEndDD']) && !empty($_POST['dateEndDD']) ? str_pad((int)$_POST['dateEndDD'], 2, '0', STR_PAD_LEFT) : '01') .
    (isset($_POST['dateEndHH']) && !empty($_POST['dateEndHH']) ? str_pad((int)$_POST['dateEndHH'], 2, '0', STR_PAD_LEFT) : '00') .
    (isset($_POST['dateEndmm']) && !empty($_POST['dateEndmm']) ? str_pad((int)$_POST['dateEndmm'], 2, '0', STR_PAD_LEFT) : '00') .
    (isset($_POST['dateEndss']) && !empty($_POST['dateEndss']) ? str_pad((int)$_POST['dateEndss'], 2, '0', STR_PAD_LEFT) : '00');
// Sanity checks
if ('00000101000000' == $dateEnd) {
    $dateEnd = '99991231235959';
}

if (    isset($submit[2])
     && isset($_REQUEST["thema"]) && $_REQUEST["thema"] != ""
     && isset($_REQUEST['rubrik']) && is_array($_REQUEST['rubrik'])
    ) {
    // Preview
    $rubrik = $_REQUEST['rubrik'];
    $category = new PMF_Category;
    $category->transform(0);
    $categorylist = '';
    foreach ($rubrik as $categories) {
        $categorylist .= $category->getPath($categories).'<br />';
    }
?>
    <h2><?php print $PMF_LANG["ad_entry_preview"]; ?></h2>

    <h3><strong><em><?php print $categorylist; ?></em>
    <?php print $_REQUEST["thema"]; ?></strong></h3>
    <?php print $_REQUEST["content"]; ?>
    <p class="little"><?php print $PMF_LANG["msgLastUpdateArticle"].makeDate(date("YmdHis")); ?><br />
    <?php print $PMF_LANG["msgAuthor"].$_REQUEST["author"]; ?></p>

    <form action="?action=editpreview" method="post">
    <input type="hidden" name="id"                  value="<?php print (int)$_REQUEST['id']; ?>" />
    <input type="hidden" name="thema"               value="<?php print htmlspecialchars($_REQUEST['thema']); ?>" />
    <input type="hidden" name="content" class="mceNoEditor" value="<?php print htmlspecialchars($_REQUEST['content']); ?>" />
    <input type="hidden" name="lang"                value="<?php print $_REQUEST['language']; ?>" />
    <input type="hidden" name="keywords"            value="<?php print $_REQUEST['keywords']; ?>" />
    <input type="hidden" name="tags"                value="<?php print $_REQUEST['tags']; ?>" />
    <input type="hidden" name="author"              value="<?php print $_REQUEST['author']; ?>" />
    <input type="hidden" name="email"               value="<?php print $_REQUEST['email']; ?>" />
<?php
    foreach ($rubrik as $key => $categories) {
        print '    <input type="hidden" name="rubrik['.$key.']" value="'.$categories.'" />';
    }
?>
    <input type="hidden" name="solution_id"         value="<?php print (int)$_REQUEST['solution_id']; ?>" />
    <input type="hidden" name="revision"            value="<?php print (isset($_REQUEST['revision']) ? (int)$_REQUEST['revision'] : '') ?>" />
    <input type="hidden" name="active"              value="<?php print $_REQUEST['active']; ?>" />
    <input type="hidden" name="changed"             value="<?php print $_REQUEST['changed']; ?>" />
    <input type="hidden" name="comment"             value="<?php print (isset($_REQUEST['comment']) ? $_REQUEST['comment'] : ''); ?>" />
    <input type="hidden" name="dateStart"           value="<?php print $dateStart; ?>" />
    <input type="hidden" name="dateEnd"             value="<?php print $dateEnd; ?>" />
    <input type="hidden" name="userpermission"      value="<?php print $_POST['userpermission']; ?>" />
    <input type="hidden" name="restricted_users"    value="<?php print $_POST['restricted_users']; ?>" />
    <input type="hidden" name="grouppermission"     value="<?php print $_POST['grouppermission']; ?>" />
    <input type="hidden" name="restricted_group"    value="<?php print $_POST['restricted_group']; ?>" />
    <p align="center"><input type="submit" name="submit" value="<?php print $PMF_LANG["ad_entry_back"]; ?>" /></p>
    </form>
<?php
}

if (    isset($submit[1])
     && isset($_REQUEST["thema"]) && $_REQUEST["thema"] != ""
     && isset($_REQUEST['rubrik']) && is_array($_REQUEST['rubrik'])
    ) {
    // Wenn auf Speichern geklickt wurde...
    adminlog("Beitragsave", (int)$_REQUEST['id']);
    print "<h2>".$PMF_LANG["ad_entry_aor"]."</h2>\n";

    $tagging = new PMF_Tags($db, $LANGCODE);

    $categories  = $_REQUEST['rubrik'];
    $record_id   = (int)$_REQUEST['id'];
    $record_lang = $db->escape_string($_POST['language']);
    $revision    = $db->escape_string($_POST['revision']);
    $revision_id = (int)$_POST['revision_id'];

	if ('yes' == $revision) {
        // Add current version into revision table
        $faq->addNewRevision($record_id, $record_lang);
        $revision_id = $db->nextID(SQLPREFIX.'faqdata', 'revision_id');
	}

    $recordData = array(
        'id'            => $record_id,
        'lang'          => $record_lang,
        'solution_id'   => intval($_POST['solution_id']),
        'revision_id'   => $revision_id,
        'active'        => $db->escape_string($_POST['active']),
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

    // Create ChangeLog entry
    $faq->createChangeEntry($record_id, $user->getUserId(), nl2br($db->escape_string($_POST["changed"])), $record_lang);

    // save or update the FAQ record
    if ($faq->isAlreadyTranslated($record_id, $record_lang)) {
        $faq->updateRecord($recordData);
    } else {
        $record_id = $faq->addRecord($recordData, false);
    }

    if ($record_id) {
        print $PMF_LANG['ad_entry_savedsuc'];
        link_ondemand_javascript($record_id, $record_lang);
    } else {
        print $PMF_LANG['ad_entry_savedfail'].$db->error();
    }

    // delete category relations
    $faq->deleteCategoryRelations($record_id, $record_lang);
    // save or update the category relations
    $faq->addCategoryRelations($categories, $record_id, $record_lang);

    // Insert the tags
    $tags = $db->escape_string(trim($_POST['tags']));
    if ($tags != '') {
        $tagging->saveTags($record_id, explode(',', $tags));
    } else {
        $tagging->deleteTagsFromRecordId($record_id);
    }

    // Save the permissions
    $userperm       = isset($_POST['userpermission']) ?
                      $db->escape_string($_POST['userpermission']) : 'all';
    $user_allowed   = ('all' == $userperm) ? -1 : (int)$_POST['restricted_users'];
    $groupperm      = isset($_POST['grouppermission']) ?
                      $db->escape_string($_POST['grouppermission']) : 'all';
    $group_allowed  = ('all' == $groupperm) ? -1 : (int)$_POST['restricted_groups'];

    // Add user permissions
    $faq->deletePermission('user', $record_id);
    $faq->addPermission('user', $record_id, $user_allowed);
    $category->deletePermission('user', $categories);
    $category->addPermission('user', $categories, $user_allowed);
    // Add group permission
    if ($groupSupport) {
        $faq->deletePermission('group', $record_id);
        $faq->addPermission('group', $record_id, $group_allowed);
        $category->deletePermission('group', $categories);
        $category->addPermission('group', $categories, $group_allowed);
    }
}

if (isset($submit[0])) {
    if ($permission["delbt"])    {
        if (isset($_REQUEST["thema"]) && $_REQUEST["thema"] != "") {
            $thema = "<strong>".$_REQUEST["thema"]."</strong>";
        } else {
            $thema = '';
        }
        if (isset($_REQUEST["author"]) && $_REQUEST["author"] != "") {
            $author = $PMF_LANG["ad_entry_del_2"]."<strong>".$_REQUEST["author"]."</strong>";
        } else {
            $author = '';
        }
?>
    <p align="center"><?php print $PMF_LANG["ad_entry_del_1"]." ".$thema." ".$author." ".$PMF_LANG["ad_entry_del_3"]; ?></p>
    <div align="center">
    <form action="?action=delentry" method="post">
        <input type="hidden" name="referer" value="<?php print $_SERVER["HTTP_REFERER"]; ?>">
        <input type="hidden" name="id" value="<?php print (int)$_REQUEST["id"]; ?>">
        <input type="hidden" name="language" value="<?php print $_REQUEST["language"]; ?>">
        <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_gen_yes"] ?>" name="subm">
        <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_gen_no"] ?>" name="subm">
    </form>
    </div>
<?php
    } else {
        print $PMF_LANG["err_NotAuth"];
    }
}
