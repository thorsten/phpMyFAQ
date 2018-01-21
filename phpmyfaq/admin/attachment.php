<?php

/**
 * Select an attachment and save it.
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2002-2018 phpMyFAQ
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2002-09-17
 */

use phpMyFAQ\Attachment\Exception;
use phpMyFAQ\Attachment\Factory;
use phpMyFAQ\Filter;
use phpMyFAQ\Language;
use phpMyFAQ\Template;
use phpMyFAQ\User\CurrentUser;

define('PMF_ROOT_DIR', dirname(__DIR__));

//
// Define the named constant used as a check by any included PHP file
//
define('IS_VALID_PHPMYFAQ', null);

//
// Bootstrapping
//
require PMF_ROOT_DIR.'/src/Bootstrap.php';

/*
 * Initialize attachment factory
 */
Factory::init(
    $faqConfig->get('records.attachmentsStorageType'),
    $faqConfig->get('records.defaultAttachmentEncKey'),
    $faqConfig->get('records.enableAttachmentEncryption')
);

$currentSave = Filter::filterInput(INPUT_POST, 'save', FILTER_SANITIZE_STRING);
$currentAction = Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
$currentToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);

$Language = new Language($faqConfig);
$LANGCODE = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));

require_once PMF_ROOT_DIR.'/lang/language_en.php';

if (isset($LANGCODE) && Language::isASupportedLanguage($LANGCODE)) {
    require_once PMF_ROOT_DIR.'/lang/language_'.$LANGCODE.'.php';
} else {
    $LANGCODE = 'en';
}

$auth = false;
$user = CurrentUser::getFromCookie($faqConfig);
if (!$user instanceof CurrentUser) {
    $user = CurrentUser::getFromSession($faqConfig);
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
<html lang="<?= $PMF_LANG['metaLanguage']; ?>" class="no-js">
<head>
    <meta charset="utf-8">

    <title><?= $faqConfig->get('main.titleFAQ') ?> - powered by phpMyFAQ</title>
    <base href="<?= $faqConfig->getDefaultUrl() ?>admin/">

    <meta name="description" content="Only Chuck Norris can divide by zero.">
    <meta name="author" content="phpMyFAQ Team">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="application-name" content="phpMyFAQ <?= $faqConfig->get('main.currentVersion') ?>">
    <meta name="publisher" content="phpMyFAQ Team">

    <link rel="stylesheet" href="assets/css/style.css?v=1">

    <script src="../assets/js/modernizr.min.js"></script>
    <script src="../assets/js/phpmyfaq.min.js"></script>

    <link rel="shortcut icon" href="../assets/themes/<?= Template::getTplSetName() ?>/favicon.ico">
</head>
<body class="pmf-attachment-upload">

<?php

}
if (is_null($currentAction) && $auth && $user->perm->checkRight($user->getUserId(), 'addattachment')) {
    $recordId = filter_input(INPUT_GET, 'record_id',   FILTER_VALIDATE_INT);
    $recordLang = filter_input(INPUT_GET, 'record_lang', FILTER_SANITIZE_STRING);
    ?>
        <form action="attachment.php?action=save" enctype="multipart/form-data" method="post">
            <fieldset>
            <legend>
                <?= $PMF_LANG['ad_att_addto'].' '.$PMF_LANG['ad_att_addto_2'] ?>
                (max <?= round($faqConfig->get('records.maxAttachmentSize') / pow(1024, 2), 2) ?> MB)
            </legend>
                <input type="hidden" name="MAX_FILE_SIZE" value="<?= $faqConfig->get('records.maxAttachmentSize') ?>">
                <input type="hidden" name="record_id" value="<?= $recordId ?>">
                <input type="hidden" name="record_lang" value="<?= $recordLang ?>">
                <input type="hidden" name="save" value="TRUE">
                <input type="hidden" name="csrf" value="<?= $user->getCsrfTokenFromSession() ?>">
                <?= $PMF_LANG['ad_att_att'] ?>

                <input name="filesToUpload[]" type="file" id="filesToUpload" multiple>
                <button class="btn btn-primary" type="submit">
                    <?= $PMF_LANG['ad_att_butt'] ?>
                </button>
                <?= $PMF_LANG['msgAttachmentsFilesize'] ?>: <output id="filesize"></output>
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
    <strong><?= $PMF_LANG['ad_att_addto'].' '.$PMF_LANG['ad_att_addto_2'] ?></strong>
</p>
<?php

    $files = Factory::rearrangeUploadedFiles($_FILES['filesToUpload']);
    $uploadedFiles = [];

    foreach ($files as $file) {
        if (
            is_uploaded_file($file['tmp_name']) &&
            !($file['size'] > $faqConfig->get('records.maxAttachmentSize')) &&
            $file['type'] !== "text/html"
        ) {
            $attachment = Factory::create();
            $attachment->setRecordId($recordId);
            $attachment->setRecordLang($recordLang);
            try {
                if ($attachment->save($file['tmp_name'], $file['name'])) {
                    echo '<p>'.$PMF_LANG['ad_att_suc'].'</p>';
                } else {
                    throw new Exception();
                }
            } catch (Exception $e) {
                $attachment->delete();
                echo '<p>'.$PMF_LANG['ad_att_fail'].'</p>';
            }
            $uploadedFiles[] = [
                'attachmentId' => $attachment->getId(),
                'fileName' => $attachment->getFilename(),
                'faqId' => $recordId,
                'faqLanguage' => $recordLang
            ];

            printf(
                '<p class="text-center"><a href="#" onclick="addAttachmentLink(%d, \'%s\', %d, \'%s\');">%s</a></p>',
                $attachment->getId(),
                $attachment->getFilename(),
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
        }
    }

    printf(
        '<p class="text-center"><a href="javascript:;" onclick="closeWindow();">%s</a></p>',
        $PMF_LANG['ad_att_close']
    );
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