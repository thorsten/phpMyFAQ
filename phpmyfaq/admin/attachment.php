<?php
/**
 * Select an attachment and save it or create the SQL backup files
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
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2002-2013 phpMyFAQ
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2002-09-17 
 */

error_reporting(E_ALL);

define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));

//
// Define the named constant used as a check by any included PHP file
//
define('IS_VALID_PHPMYFAQ', null);

//
// Autoload classes, prepend and start the PHP session
//
require_once PMF_ROOT_DIR.'/inc/Init.php';
PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH.trim($faqconfig->get('main.phpMyFAQToken')));
session_start();

/**
 * Initialize attachment factory
 */
PMF_Attachment_Factory::init($faqconfig->get('records.attachmentsStorageType'),
                             $faqconfig->get('records.defaultAttachmentEncKey'),
                             $faqconfig->get('records.enableAttachmentEncryption'));

$currentSave   = PMF_Filter::filterInput(INPUT_POST, 'save', FILTER_SANITIZE_STRING);
$currentAction = PMF_Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
$currentToken  = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);

$Language = new PMF_Language();
$LANGCODE = $Language->setLanguage($faqconfig->get('main.languageDetection'), $faqconfig->get('main.language'));

require_once PMF_ROOT_DIR . '/lang/language_en.php';

if (isset($LANGCODE) && PMF_Language::isASupportedLanguage($LANGCODE)) {
    require_once PMF_ROOT_DIR . '/lang/language_'.$LANGCODE.'.php';
} else {
    $LANGCODE = 'en';
}

$auth = false;
$user = PMF_User_CurrentUser::getFromSession($faqconfig->get('security.ipCheck'));
if ($user) {
    $auth = true;
} else {
    $error = $PMF_LANG['ad_auth_sess'];
    $user  = null;
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
<!DOCTYPE html>
<!--[if lt IE 7 ]> <html lang="<?php print $PMF_LANG['metaLanguage']; ?>" class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]> <html lang="<?php print $PMF_LANG['metaLanguage']; ?>" class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]> <html lang="<?php print $PMF_LANG['metaLanguage']; ?>" class="no-js ie8"> <![endif]-->
<!--[if IE 9 ]> <html lang="<?php print $PMF_LANG['metaLanguage']; ?>" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="<?php print $PMF_LANG['metaLanguage']; ?>" class="no-js"> <!--<![endif]-->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <title><?php print $faqconfig->get('main.titleFAQ'); ?> - powered by phpMyFAQ</title>
    <base href="<?php print PMF_Link::getSystemUri('index.php'); ?>" />
    
    <meta name="description" content="Only Chuck Norris can divide by zero.">
    <meta name="author" content="phpMyFAQ Team">
    <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;">
    <meta name="application-name" content="phpMyFAQ <?php print $faqconfig->get('main.currentVersion'); ?>">
    <meta name="copyright" content="(c) 2001-2013 phpMyFAQ Team">
    <meta name="publisher" content="phpMyFAQ Team">
    <meta name="MSSmartTagsPreventParsing" content="true">

    <link rel="stylesheet" href="style/admin.css?v=1">

    <script src="../inc/js/modernizr.min.js"></script>
    <script src="../inc/js/jquery.min.js"></script>
    <script src="../inc/js/functions.js"></script>
    
    <link rel="shortcut icon" href="../template/<?php print PMF_Template::getTplSetName(); ?>/favicon.ico">
    <link rel="apple-touch-icon" href="../template/<?php print PMF_Template::getTplSetName(); ?>/apple-touch-icon.png">
</head>
<body class="attachments">

    <div id="mainContent">
<?php
}
if (is_null($currentAction) && $auth && $permission['addattachment']) {
    $recordId   = filter_input(INPUT_GET, 'record_id',   FILTER_VALIDATE_INT);
    $recordLang = filter_input(INPUT_GET, 'record_lang', FILTER_SANITIZE_STRING);
?>
        <form action="?action=save" enctype="multipart/form-data" method="post">
            <fieldset>
            <legend><?php print $PMF_LANG["ad_att_addto"]." ".$PMF_LANG["ad_att_addto_2"]; ?></legend>
                <input type="hidden" name="MAX_FILE_SIZE" value="<?php print $faqconfig->get('records.maxAttachmentSize'); ?>" />
                <input type="hidden" name="record_id" value="<?php print $recordId; ?>" />
                <input type="hidden" name="record_lang" value="<?php print $recordLang; ?>" />
                <input type="hidden" name="save" value="TRUE" />
                <input type="hidden" name="csrf" value="<?php print $user->getCsrfTokenFromSession(); ?>" />
                <?php print $PMF_LANG["ad_att_att"]; ?> <input name="userfile" type="file" />
                <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_att_butt"]; ?>" />
            </fieldset>
        </form>
<?php
}

if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $currentToken) {
    $auth = false;
}

if (!is_null($currentAction) && $auth && !$permission['addattachment']) {
    print $PMF_LANG['err_NotAuth'];
}

if (!is_null($currentSave) && $currentSave == true && $auth && $permission['addattachment']) {
    $recordId   = filter_input(INPUT_POST, 'record_id',   FILTER_VALIDATE_INT);
    $recordLang = filter_input(INPUT_POST, 'record_lang', FILTER_SANITIZE_STRING);
?>
<p><strong><?php print $PMF_LANG["ad_att_addto"]." ".$PMF_LANG["ad_att_addto_2"]; ?></strong></p>
<?php
    if (is_uploaded_file($_FILES["userfile"]["tmp_name"]) && !(filesize($_FILES["userfile"]["tmp_name"]) > $faqconfig->get('records.maxAttachmentSize'))) {

        $att = PMF_Attachment_Factory::create();
        $att->setRecordId($recordId);
        $att->setRecordLang($recordLang);
        
        /**
         * To add user difined key
         * $att->setKey($somekey, false);
         */
        try {
            $uploaded = $att->save($_FILES["userfile"]["tmp_name"], $_FILES["userfile"]["name"]);
            
            if ($uploaded) {
                print "<p>".$PMF_LANG["ad_att_suc"]."</p>";
            } else {
                throw new Exception;
            }
        } catch (Exception $e) {
            $att->delete();
            print "<p>".$PMF_LANG["ad_att_fail"]."</p>";
        }

        printf(
            '<p align="center"><a href="javascript:;" onclick="addAttachmentLink(%d, \'%s\');">%s</a></p>',
            $att->getId(),
            $att->getFilename(),
            $PMF_LANG['ad_att_close']
        );

    } else {
        printf(
            '<p>%s</p>',
            sprintf(
                $PMF_LANG['ad_attach_4'],
                $faqconfig->get('records.maxAttachmentSize')
            )
        );

        printf(
            '<p align="center"><a href="javascript:;" onclick="closeWindow();">%s</a></p>',
            $PMF_LANG['ad_att_close']
        );

    }


}
if (!is_null($currentSave) && $currentSave == true && $auth && !$permission['addattachment']) {
    print $PMF_LANG["err_NotAuth"];
}

if (DEBUG) {
    print "\n\n-- Debug information:\n<p>".$db->sqllog()."</p>";
}

$db->dbclose();
?>
    </div>

    <script type="text/javascript">
        /**
         * Adds the link to the attachment in the main FAQ window
         * @param integer attachmentId
         * @param string
         */
        function addAttachmentLink(attachmentId, fileName)
        {
            window.opener.
                $('.adminAttachments').
                append('<li><a href="../index.php?action=attachment&id=' + attachmentId +'">' + fileName + '</a></li>');
            window.close();
        }

        /**
         * Closes the current window
         *
         */
        function closeWindow()
        {
            window.close();
        }
    </script>

</body>
</html>