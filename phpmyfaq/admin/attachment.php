<?php
/**
 * Select an attachment and save it
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
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2002-2012 phpMyFAQ
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2002-09-17 
 */

define('PMF_ROOT_DIR', dirname(__DIR__));

//
// Define the named constant used as a check by any included PHP file
//
define('IS_VALID_PHPMYFAQ', null);

//
// Autoload classes, prepend and start the PHP session
//
require_once PMF_ROOT_DIR.'/inc/Bootstrap.php';
PMF_Init::cleanRequest();
session_name(PMF_Session::PMF_COOKIE_NAME_AUTH);
session_start();

/**
 * Initialize attachment factory
 */
PMF_Attachment_Factory::init(
    $faqConfig->get('records.attachmentsStorageType'),
    $faqConfig->get('records.defaultAttachmentEncKey'),
    $faqConfig->get('records.enableAttachmentEncryption')
);

$currentSave   = PMF_Filter::filterInput(INPUT_POST, 'save', FILTER_SANITIZE_STRING);
$currentAction = PMF_Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
$currentToken  = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);

$Language = new PMF_Language($faqConfig);
$LANGCODE = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));

require_once PMF_ROOT_DIR . '/lang/language_en.php';

if (isset($LANGCODE) && PMF_Language::isASupportedLanguage($LANGCODE)) {
    require_once PMF_ROOT_DIR . '/lang/language_'.$LANGCODE.'.php';
} else {
    $LANGCODE = 'en';
}

$auth = false;
$user = PMF_User_CurrentUser::getFromSession($faqConfig);
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

    <title><?php print $faqConfig->get('main.titleFAQ'); ?> - powered by phpMyFAQ</title>
    <base href="<?php print $faqConfig->get('main.referenceURL'); ?>" />
    
    <meta name="description" content="Only Chuck Norris can divide by zero.">
    <meta name="author" content="phpMyFAQ Team">
    <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;">
    <meta name="application-name" content="phpMyFAQ <?php print $faqConfig->get('main.currentVersion'); ?>">
    <meta name="copyright" content="(c) 2001-2012 phpMyFAQ Team">
    <meta name="publisher" content="phpMyFAQ Team">
    <meta name="MSSmartTagsPreventParsing" content="true">

    <link rel="stylesheet" href="css/style.css?v=1">

    <script src="../assets/js/libs/modernizr.min.js"></script>
    <script src="../assets/js/libs/jquery.min.js"></script>
    <script src="../assets/js/phpmyfaq.js"></script>

    <link rel="shortcut icon" href="../assets/template/<?php print PMF_Template::getTplSetName(); ?>/favicon.ico">
    <link rel="apple-touch-icon" href="../assets/template/<?php print PMF_Template::getTplSetName(); ?>/apple-touch-icon.png">
</head>
<body class="attachments">

<?php
}
if (is_null($currentAction) && $auth && $permission['addattachment']) {
    $recordId   = filter_input(INPUT_GET, 'record_id',   FILTER_VALIDATE_INT);
    $recordLang = filter_input(INPUT_GET, 'record_lang', FILTER_SANITIZE_STRING);
?>
        <form action="?action=save" enctype="multipart/form-data" method="post">
            <fieldset>
            <legend><?php print $PMF_LANG["ad_att_addto"]." ".$PMF_LANG["ad_att_addto_2"]; ?></legend>
                <input type="hidden" name="MAX_FILE_SIZE" value="<?php print $faqConfig->get('records.maxAttachmentSize'); ?>" />
                <input type="hidden" name="record_id" value="<?php print $recordId; ?>" />
                <input type="hidden" name="record_lang" value="<?php print $recordLang; ?>" />
                <input type="hidden" name="save" value="TRUE" />
                <input type="hidden" name="csrf" value="<?php print $user->getCsrfTokenFromSession(); ?>" />
                <?php print $PMF_LANG["ad_att_att"]; ?> <input name="userfile" type="file" />
                <input class="btn-primary" type="submit" value="<?php print $PMF_LANG["ad_att_butt"]; ?>" />
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
    if (is_uploaded_file($_FILES["userfile"]["tmp_name"]) && !(filesize($_FILES["userfile"]["tmp_name"]) > $faqConfig->get('records.maxAttachmentSize'))) {

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
                $faqConfig->get('records.maxAttachmentSize')
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

$faqConfig->getDb()->close();
?>
</body>
</html>