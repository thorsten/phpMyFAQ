<?php
/**
 * AJAX: onDemandURL.
 *
 * Usage:
 *   index.php?action=ajax&ajax=onDemandURL&id=<id>&artlang=<lang>[&lookup=1]
 *
 * Performs link verification at demand of the user.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * The Initial Developer of the Original Code is released for external use
 * with permission from NetJapan, Inc. IT Administration Group.
 *
 * @category  phpMyFAQ
 * @author    Minoru TODA <todam@netjapan.co.jp>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2019 NetJapan, Inc.
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-30
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$httpHeader = new PMF_Helper_Http();
$httpHeader->setContentType('text/html');
$httpHeader->addHeader();

$linkVerifier = new PMF_Linkverifier($faqConfig, $user->getLogin());
if ($linkVerifier->isReady() === false) {
    if (count(ob_list_handlers()) > 0) {
        ob_clean();
    }
    echo 'disabled';
    exit();
}

$id = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$artlang = PMF_Filter::filterInput(INPUT_GET, 'artlang', FILTER_SANITIZE_STRING);
$lookup = PMF_Filter::filterInput(INPUT_GET, 'lookup', FILTER_VALIDATE_INT);

if (count(ob_list_handlers()) > 0) {
    ob_clean();
}
?>
<!DOCTYPE html>
<!--[if IE 9 ]> <html lang="<?php echo $PMF_LANG['metaLanguage']; ?>" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="<?php echo $PMF_LANG['metaLanguage']; ?>" class="no-js"> <!--<![endif]-->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <title><?php echo $faqConfig->get('main.titleFAQ'); ?> - powered by phpMyFAQ</title>
    <base href="<?php echo $faqConfig->getDefaultUrl(); ?>">

    <meta name="description" content="Only Chuck Norris can divide by zero.">
    <meta name="author" content="phpMyFAQ Team">
    <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;">
    <meta name="application-name" content="phpMyFAQ <?php echo $faqConfig->get('main.currentVersion'); ?>">
    <meta name="copyright" content="(c) 2001-2019 phpMyFAQ Team">
    <meta name="publisher" content="phpMyFAQ Team">
    <meta name="MSSmartTagsPreventParsing" content="true">

    <link rel="stylesheet" href="assets/css/style.min.css?v=1">

    <script src="../assets/js/modernizr.min.js"></script>
    <script src="../assets/js/phpmyfaq.min.js"></script>

</head>
<body dir="<?php echo $PMF_LANG['dir']; ?>">
<?php

if (!(isset($id) && isset($artlang))) {
    ?>
    Error: Entry ID and Language needs to be specified.
</body>
</html>
<?php
    exit();
}

$faq->faqRecord = null;
$faq->getRecord($id, null, true);

if (!isset($faq->faqRecord['content'])) {
    ?>
    Error: No entry for #<?php echo $id;
    ?>(<?php echo $artlang;
    ?>) available.
</body>
</html>
<?php
    exit();
}

if (!is_null($lookup)) {
    if (count(ob_list_handlers()) > 0) {
        ob_clean();
    }

    echo $linkVerifier->verifyArticleURL($faq->faqRecord['content'], $id, $artlang);
    exit();
}

?>
<?php PMF_Helper_Linkverifier::linkOndemandJavascript($id, $artlang); ?>
</body>
</html>