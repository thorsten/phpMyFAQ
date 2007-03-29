<?php
/**
* $Id: attachment.php,v 1.31 2007-03-29 12:03:50 thorstenr Exp $
*
* Select an attachment and save it or create the SQL backup files
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2002-09-17
* @copyright    (c) 2001-2005 phpMyFAQ
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

require_once('../inc/functions.php');
require_once('../inc/Init.php');
define('IS_VALID_PHPMYFAQ_ADMIN', null);
PMF_Init::cleanRequest();
session_name('pmf_auth_'.$faqconfig->get('phpMyFAQToken'));
session_start();

define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));

if (isset($_REQUEST["action"]) && ($_REQUEST["action"] == "sicherdaten" || $_REQUEST["action"] == "sicherlog")) {
    Header("Content-Type: application/octet-stream");
    if ($_REQUEST["action"] == "sicherdaten") {
        Header("Content-Disposition: attachment; filename=\"phpmyfaq-data.".date("Y-m-d").".sql\"");
    } elseif ($_REQUEST["action"] == "sicherlog") {
        Header("Content-Disposition: attachment; filename=\"phpmyfaq-logs.".date("Y-m-d").".sql\"");
    }
    Header("Pragma: no-cache");
}

// get language (default: english)
$pmf = new PMF_Init();
$LANGCODE = $pmf->setLanguage((isset($PMF_CONF['main.languageDetection']) ? true : false), $PMF_CONF['main.language']);
// Preload English strings
require_once ('../lang/language_en.php');

if (isset($LANGCODE) && PMF_Init::isASupportedLanguage($LANGCODE)) {
    // Overwrite English strings with the ones we have in the current language
    require_once(PMF_ROOT_DIR.'/lang/language_'.$LANGCODE.'.php');
} else {
    $LANGCODE = 'en';
}

//
// Authenticate current user
//
require_once (PMF_ROOT_DIR.'/inc/PMF_User/CurrentUser.php');

$auth = false;
$user = PMF_CurrentUser::getFromSession($faqconfig->get('main.ipCheck'));
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

if (!isset($_REQUEST["action"]) || isset($_REQUEST["save"])) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $PMF_LANG["metaLanguage"]; ?>" lang="<?php print $PMF_LANG["metaLanguage"]; ?>">
<head>
    <title><?php print htmlentities($PMF_CONF["title"]); ?> - powered by phpMyFAQ</title>
    <meta name="copyright" content="(c) 2001-2006 phpMyFAQ Team" />
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
if (!isset($_REQUEST["action"]) && $auth && $permission["addatt"]) {
?>
<form action="<?php print $_SERVER["PHP_SELF"]; ?>" enctype="multipart/form-data" method="post">
<fieldset>
<legend><?php print $PMF_LANG["ad_att_addto"]." ".$PMF_LANG["ad_att_addto_2"]; ?></legend>
<input type="hidden" name="action" value="save" />
<input type="hidden" name="uin" value="<?php print $_REQUEST["uin"]; ?>" />
<input type="hidden" name="MAX_FILE_SIZE" value="<?php print $faqconfig->get('main.maxAttachmentSize'); ?>" />
<input type="hidden" name="id" value="<?php print $_REQUEST["id"]; ?>" />
<input type="hidden" name="save" value="TRUE" />
<?php print $PMF_LANG["ad_att_att"]; ?> <input name="userfile" type="file" />
<input class="submit" type="submit" value="<?php print $PMF_LANG["ad_att_butt"]; ?>" />
</fieldset>
</form>
<?php
}

if (isset($_REQUEST["action"]) && $auth && !$permission["addatt"]) {
    print $PMF_LANG["err_NotAuth"];
    die();
}

if (isset($_REQUEST["save"]) && $_REQUEST["save"] == TRUE && $auth && $permission["addatt"]) {
    $_REQUEST["id"] = (int)$_REQUEST["id"];
?>
<p><strong><?php print $PMF_LANG["ad_att_addto"]." ".$PMF_LANG["ad_att_addto_2"]; ?></strong></p>
<?php
    if (is_uploaded_file($_FILES["userfile"]["tmp_name"]) && !(@filesize($_FILES["userfile"]["tmp_name"]) > $faqconfig->get('main.maxAttachmentSize'))) {
        if (!is_dir(PMF_ROOT_DIR."/attachments/")) {
            mkdir(PMF_ROOT_DIR."/attachments/", 0777);
        }
        if (!is_dir(PMF_ROOT_DIR."/attachments/".$_REQUEST["id"])) {
            mkdir(PMF_ROOT_DIR."/attachments/".$_REQUEST["id"], 0777);
        }
        if (@move_uploaded_file($_FILES["userfile"]["tmp_name"], PMF_ROOT_DIR."/attachments/".$_REQUEST["id"]."/".$_FILES["userfile"]["name"])) {
            chmod (PMF_ROOT_DIR."/attachments/".$_REQUEST["id"]."/".$_FILES["userfile"]["name"], 0644);
            print "<p>".$PMF_LANG["ad_att_suc"]."</p>";
        }
        else {
            print "<p>".$PMF_LANG["ad_att_fail"]."</p>";
        }
    } else {
        print "<p>".$PMF_LANG["ad_attach_4"]."</p>";
    }
    print "<p align=\"center\"><a href=\"javascript:window.close()\">".$PMF_LANG["ad_att_close"]."</a></p>";
}
if (isset($_REQUEST["save"]) && $_REQUEST["save"] == TRUE && $auth && !$permission["addatt"]) {
    print $PMF_LANG["err_NotAuth"];
    die();
}

if (isset($_GET['action']) && ('sicherdaten' == $_GET['action']) && $auth && $permission['backup']) {
    // Get all table names
    $db->getTableNames(SQLPREFIX);
    $tablenames = '';
    foreach ($db->tableNames as $table) {
        $tablenames .= $table . ' ';
    }

    $text[] = "-- pmf2.0: " . $tablenames;
    $text[] = "-- DO NOT REMOVE THE FIRST LINE!";
    $text[] = "-- pmftableprefix: ".SQLPREFIX;
    $text[] = "-- DO NOT REMOVE THE LINES ABOVE!";
    $text[] = "-- Otherwise this backup will be broken.";
    foreach ($db->tableNames as $table) {
       print implode("\r\n", $text);
       $text = build_insert("SELECT * FROM ".$table, $table);
    }
} elseif (isset($_GET['action']) && ('sicherdaten' == $_GET['action']) && $auth && !$permission['backup']) {
    print $PMF_LANG['err_NotAuth'];
    die();
}

if (isset($_GET['action']) && ('sicherlog' == $_GET['action']) && $auth && $permission['backup']) {
    // Get all table names
    $db->getTableNames(SQLPREFIX);
    $tablenames = '';
    foreach ($db->tableNames as $table) {
        if (SQLPREFIX.'faqadminlog' == $table || SQLPREFIX.'faqsessions' ==  $table) {
            $tablenames .= $table . ' ';
        }
    }
    $text[] = "-- pmf2.0: " . $tablenames;
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
} elseif (isset($_GET['action']) && ('sicherlog' == $_GET['action']) && $auth && !$permission['backup']) {
    print $PMF_LANG['err_NotAuth'];
    die();
}

if (DEBUG) {
    print "<p>".$db->sqllog()."</p>";
}

if (isset($_GET['action']) && $_GET['action'] != 'sicherdaten' && $_GET['action'] != 'sicherlog') {
    print "</body></html>";
}

$db->dbclose();
