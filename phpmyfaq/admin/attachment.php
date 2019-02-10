<?php
/**
 * Select an attachment and save it.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2002-2019 phpMyFAQ
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-09-17
 */
define('PMF_ROOT_DIR', dirname(__DIR__));

//
// Define the named constant used as a check by any included PHP file
//
define('IS_VALID_PHPMYFAQ', null);

//
// Bootstrapping
//
require PMF_ROOT_DIR.'/inc/Bootstrap.php';

/*
 * Initialize attachment factory
 */
PMF_Attachment_Factory::init(
    $faqConfig->get('records.attachmentsStorageType'),
    $faqConfig->get('records.defaultAttachmentEncKey'),
    $faqConfig->get('records.enableAttachmentEncryption')
);

$currentSave = PMF_Filter::filterInput(INPUT_POST, 'save', FILTER_SANITIZE_STRING);
$currentAction = PMF_Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
$currentToken = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);

$Language = new PMF_Language($faqConfig);
$LANGCODE = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));

require_once PMF_ROOT_DIR.'/lang/language_en.php';

if (isset($LANGCODE) && PMF_Language::isASupportedLanguage($LANGCODE)) {
    require_once PMF_ROOT_DIR.'/lang/language_'.$LANGCODE.'.php';
} else {
    $LANGCODE = 'en';
}

$auth = false;
$user = PMF_User_CurrentUser::getFromCookie($faqConfig);
if (!$user instanceof PMF_User_CurrentUser) {
    $user = PMF_User_CurrentUser::getFromSession($faqConfig);
}
if ($user) {
    $auth = true;
} else {
    $error = $PMF_LANG['ad_auth_sess'];
    $user = null;
    unset($user);
}

if (is_null($currentAction) || !is_null($currentSave)) {
?>
<!DOCTYPE html>
<!--[if IE 9 ]> <html lang="<?php echo $PMF_LANG['metaLanguage']; ?>" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="<?php echo $PMF_LANG['metaLanguage']; ?>" class="no-js"> <!--<![endif]-->
<head>
    <meta charset="utf-8">

    <title><?php echo $faqConfig->get('main.titleFAQ') ?> - powered by phpMyFAQ</title>
    <base href="<?php echo $faqConfig->getDefaultUrl() ?>admin/" />

    <meta name="description" content="Only Chuck Norris can divide by zero.">
    <meta name="author" content="phpMyFAQ Team">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="application-name" content="phpMyFAQ <?php echo $faqConfig->get('main.currentVersion') ?>">
    <meta name="publisher" content="phpMyFAQ Team">

    <link rel="stylesheet" href="assets/css/style.css?v=1">

    <script src="../assets/js/modernizr.min.js"></script>
    <script src="../assets/js/phpmyfaq.min.js"></script>

    <link rel="shortcut icon" href="../assets/template/<?php echo PMF_Template::getTplSetName() ?>/favicon.ico">
</head>
<body class="attachments">

<?php

}
if (is_null($currentAction) && $auth && $user->perm->checkRight($user->getUserId(), 'addattachment')) {
    $recordId = filter_input(INPUT_GET, 'record_id',   FILTER_VALIDATE_INT);
    $recordLang = filter_input(INPUT_GET, 'record_lang', FILTER_SANITIZE_STRING);
    ?>
        <form action="attachment.php?action=save" enctype="multipart/form-data" method="post" accept-charset="utf-8">
            <fieldset>
            <legend>
                <?php echo $PMF_LANG['ad_att_addto'].' '.$PMF_LANG['ad_att_addto_2'] ?>
                (max <?php echo round($faqConfig->get('records.maxAttachmentSize') / pow(1024, 2), 2) ?> MB)
            </legend>
                <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $faqConfig->get('records.maxAttachmentSize') ?>">
                <input type="hidden" name="record_id" value="<?php echo $recordId ?>">
                <input type="hidden" name="record_lang" value="<?php echo $recordLang ?>">
                <input type="hidden" name="save" value="TRUE">
                <input type="hidden" name="csrf" value="<?php echo $user->getCsrfTokenFromSession() ?>">
                <?php echo $PMF_LANG['ad_att_att'] ?>

                <input name="userfile" type="file" id="fileUpload">
                <button class="btn btn-primary" type="submit">
                    <?php echo $PMF_LANG['ad_att_butt'] ?>
                </button>
                <?php echo $PMF_LANG['msgAttachmentsFilesize'] ?>: <output id="filesize"></output>
            </fieldset>
        </form>
<?php

}

if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $currentToken) {
    $auth = false;
}

if (!is_null($currentAction) && $auth && !$user->perm->checkRight($user->getUserId(), 'addattachment')) {
    echo $PMF_LANG['err_NotAuth'];
}

if (!is_null($currentSave) && $currentSave == true && $auth &&
    $user->perm->checkRight($user->getUserId(), 'addattachment')) {
    $recordId = filter_input(INPUT_POST, 'record_id',   FILTER_VALIDATE_INT);
    $recordLang = filter_input(INPUT_POST, 'record_lang', FILTER_SANITIZE_STRING);
    ?>
<p>
    <strong><?php echo $PMF_LANG['ad_att_addto'].' '.$PMF_LANG['ad_att_addto_2'] ?></strong>
</p>
<?php
    if (
        is_uploaded_file($_FILES['userfile']['tmp_name']) &&
        !($_FILES['userfile']['size'] > $faqConfig->get('records.maxAttachmentSize')) &&
        $_FILES['userfile']['type'] !== "text/html"
    ) {
        $att = PMF_Attachment_Factory::create();
        $att->setRecordId($recordId);
        $att->setRecordLang($recordLang);

        /*
         * To add user defined key
         * $att->setKey($somekey, false);
         */
        try {
            $uploaded = $att->save($_FILES['userfile']['tmp_name'], $_FILES['userfile']['name']);

            if ($uploaded) {
                echo '<p>'.$PMF_LANG['ad_att_suc'].'</p>';
            } else {
                throw new Exception();
            }
        } catch (Exception $e) {
            $att->delete();
            echo '<p>'.$PMF_LANG['ad_att_fail'].'</p>';
        }

        printf(
            '<p class="text-center"><a href="#" onclick="addAttachmentLink(%d, \'%s\', %d, \'%s\');">%s</a></p>',
            $att->getId(),
            $att->getFilename(),
            $recordId,
            $recordLang,
            $PMF_LANG['ad_att_close']
        );
    } else {
        printf(
            '<p>%s</p>',
            sprintf(
                $PMF_LANG['ad_attach_4'],
                round($faqConfig->get('records.maxAttachmentSize') / pow(1024, 2), 2)
            )
        );

        printf(
            '<p class="text-center"><a href="javascript:;" onclick="closeWindow();">%s</a></p>',
            $PMF_LANG['ad_att_close']
        );
    }
}
if (!is_null($currentSave) && $currentSave == true && $auth &&
    !$user->perm->checkRight($user->getUserId(), 'addattachment')) {
    echo $PMF_LANG['err_NotAuth'];
}

$faqConfig->getDb()->close();
?>
<script src="assets/js/uploadcheck.js"></script>
</body>
</html>