<?php
/**
 * Select an attachment and save it or create the SQL backup files
 *
 * @package    phpMyFAQ
 * @subpackage Administration
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2002-09-17
 * @version    SVN: $Id$ 
 * @copyright  2002-2009 phpMyFAQ
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

define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));

//
// Define the named constant used as a check by any included PHP file
//
define('IS_VALID_PHPMYFAQ_ADMIN', null);

//
// Autoload classes, prepend and start the PHP session
//
require_once PMF_ROOT_DIR.'/inc/Init.php';
PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH.trim($faqconfig->get('main.phpMyFAQToken')));
session_start();

$currentAction = filter_input(INPUT_GET,  'action', FILTER_SANITIZE_STRING);
$currentSave   = filter_input(INPUT_POST, 'save',   FILTER_SANITIZE_STRING);

if ($currentAction == 'savedcontent' || $currentAction == 'savedlogs') {
    header('Content-Type: application/octet-stream');
    switch($currentAction) {
    case 'savedcontent':
        header('Content-Disposition: attachment; filename="phpmyfaq-data.'.date("Y-m-d-H-i-s").'.sql');
        break;
    case 'savedlogs':
        header('Content-Disposition: attachment; filename="phpmyfaq-logs.'.date("Y-m-d-H-i-s").'.sql');
        break;
    }
    header('Pragma: no-cache');
 }


$pmf      = new PMF_Init();
$LANGCODE = $pmf->setLanguage($faqconfig->get('main.languageDetection'), $faqconfig->get('main.language'));

require_once PMF_ROOT_DIR . '/lang/language_en.php';

if (isset($LANGCODE) && PMF_Init::isASupportedLanguage($LANGCODE)) {
    require_once PMF_ROOT_DIR . '/lang/language_'.$LANGCODE.'.php';
} else {
    $LANGCODE = 'en';
}

$auth = false;
$user = PMF_User_CurrentUser::getFromSession($faqconfig->get('main.ipCheck'));
if ($user) {
    $auth = true;
} else {
    // error
    $error = $PMF_LANG['ad_auth_sess'];
    $user = null;
    unset($user);
}

//
// Get current user rights
//
$permission = array();
if ($auth === true) {
    // read all rights, set them FALSE
    $allRights = $user->perm->getAllRightsData();
    foreach ($allRights as $right) {
        $permission[$right['name']] = false;
    }
    // check user rights, set them TRUE
    $allUserRights = $user->perm->getAllUserRights($user->getUserId());
    foreach ($allRights as $right) {
        if (in_array($right['right_id'], $allUserRights))
            $permission[$right['name']] = true;
    }
}

if (is_null($currentAction) || !is_null($currentSave)) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $PMF_LANG["metaLanguage"]; ?>" lang="<?php print $PMF_LANG["metaLanguage"]; ?>">
<head>
    <title><?php print PMF_htmlentities($faqconfig->get('main.titleFAQ'), ENT_QUOTES, $PMF_LANG['metaCharset']); ?> - powered by phpMyFAQ</title>
    <meta name="copyright" content="(c) 2001-2009 phpMyFAQ Team" />
    <meta http-equiv="Content-Type" content="text/html; charset=<?php print $PMF_LANG["metaCharset"]; ?>" />

    <link rel="shortcut icon" href="../template/favicon.ico" type="image/x-icon" />

    <link rel="icon" href="../template/favicon.ico" type="image/x-icon" />
    <style type="text/css">
    @import url(../template/admin.css);
    body { margin: 5px; }
    </style>
    <script type="text/javascript" src="../inc/js/functions.js"></script>
</head>
<body>
<?php
}
if (is_null($currentAction) && $auth && $permission["addatt"]) {
?>
    <form action="?action=save" enctype="multipart/form-data" method="post">
    <fieldset>
    <legend><?php print $PMF_LANG["ad_att_addto"]." ".$PMF_LANG["ad_att_addto_2"]; ?></legend>
        <input type="hidden" name="MAX_FILE_SIZE" value="<?php print $faqconfig->get('main.maxAttachmentSize'); ?>" />
        <input type="hidden" name="id" value="<?php print filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT); ?>" />
        <input type="hidden" name="save" value="TRUE" />
        <?php print $PMF_LANG["ad_att_att"]; ?> <input name="userfile" type="file" />
        <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_att_butt"]; ?>" />
    </fieldset>
    </form>
<?php
}

if (!is_null($currentAction) && $auth && !$permission["addatt"]) {
    print $PMF_LANG["err_NotAuth"];
    die();
}

if (!is_null($currentSave) && $currentSave == true && $auth && $permission["addatt"]) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
?>
<p><strong><?php print $PMF_LANG["ad_att_addto"]." ".$PMF_LANG["ad_att_addto_2"]; ?></strong></p>
<?php
    if (is_uploaded_file($_FILES["userfile"]["tmp_name"]) && !(filesize($_FILES["userfile"]["tmp_name"]) > $faqconfig->get('main.maxAttachmentSize'))) {
        if (!is_dir(PMF_ATTACHMENTS_DIR)) {
            mkdir(PMF_ATTACHMENTS_DIR, 0777);
        }

        $recordAttachmentsDir = PMF_ATTACHMENTS_DIR . DIRECTORY_SEPARATOR . $id;
        if (!is_dir($recordAttachmentsDir)) {
            mkdir($recordAttachmentsDir, 0777);
        }
        
        $attachmentFilepath = $recordAttachmentsDir . DIRECTORY_SEPARATOR . $_FILES["userfile"]["name"];
        if (move_uploaded_file($_FILES["userfile"]["tmp_name"], $attachmentFilepath)) {
            chmod ($attachmentFilepath, 0644);
            print "<p>".$PMF_LANG["ad_att_suc"]."</p>";
        }
        else {
            print "<p>".$PMF_LANG["ad_att_fail"]."</p>";
        }
    } else {
        printf("<p>%s</p>", sprintf($PMF_LANG['ad_attach_4'], $faqconfig->get('main.maxAttachmentSize')));
    }
    print "<p align=\"center\"><a href=\"javascript:window.close()\">".$PMF_LANG["ad_att_close"]."</a></p>";
}
if (!is_null($currentSave) && $currentSave == true && $auth && !$permission["addatt"]) {
    print $PMF_LANG["err_NotAuth"];
    die();
}

if (!is_null($currentAction) && ('savedcontent' == $currentAction) && $auth && $permission['backup']) {
    // Get all table names
    $db->getTableNames(SQLPREFIX);
    $tablenames = '';
    foreach ($db->tableNames as $table) {
        $tablenames .= $table . ' ';
    }

    $text[] = "-- pmf2.5: " . $tablenames;
    $text[] = "-- DO NOT REMOVE THE FIRST LINE!";
    $text[] = "-- pmftableprefix: ".SQLPREFIX;
    $text[] = "-- DO NOT REMOVE THE LINES ABOVE!";
    $text[] = "-- Otherwise this backup will be broken.";
    foreach ($db->tableNames as $table) {
       print implode("\r\n", $text);
       $text = build_insert("SELECT * FROM ".$table, $table);
    }
} elseif (!is_null($currentAction) && ('savedcontent' == $currentAction) && $auth && !$permission['backup']) {
    print $PMF_LANG['err_NotAuth'];
    die();
}

if (!is_null($currentAction) && ('savedlogs' == $currentAction) && $auth && $permission['backup']) {
    // Get all table names
    $db->getTableNames(SQLPREFIX);
    $tablenames = '';
    foreach ($db->tableNames as $table) {
        if (SQLPREFIX.'faqadminlog' == $table || SQLPREFIX.'faqsessions' ==  $table) {
            $tablenames .= $table . ' ';
        }
    }
    $text[] = "-- pmf2.5: " . $tablenames;
    $text[] = "-- DO NOT REMOVE THE FIRST LINE!";
    $text[] = "-- pmftableprefix: ".SQLPREFIX;
    $text[] = "-- DO NOT REMOVE THE LINES ABOVE!";
    $text[] = "-- Otherwise this backup will be broken.";
    foreach ($db->tableNames as $table) {
        if (SQLPREFIX.'faqadminlog' == $table || SQLPREFIX.'faqsessions' ==  $table) {
            print implode("\r\n", $text);
            $text = build_insert("SELECT * FROM ".$table, $table);
        }
    }
} elseif (!is_null($currentAction) && ('savedlogs' == $currentAction) && $auth && !$permission['backup']) {
    print $PMF_LANG['err_NotAuth'];
    die();
}

if (DEBUG) {
    print "\n\n-- Debug information:\n<p>".$db->sqllog()."</p>";
}

if (!is_null($currentSave) && $currentAction != 'savedcontent' && $currentAction != 'savedlogs') {
    print "</body></html>";
}

$db->dbclose();
