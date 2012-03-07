<?php
/**
 * AJAX: onDemandURL
 *
 * Usage:
 *   index.php?action=ajax&ajax=onDemandURL&id=<id>&artlang=<lang>[&lookup=1]
 *
 * Performs link verification at demand of the user.
 *
 * PHP Version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * The Initial Developer of the Original Code is released for external use
 * with permission from NetJapan, Inc. IT Administration Group.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Minoru TODA <todam@netjapan.co.jp>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2012 NetJapan, Inc.
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-30
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

header("Expires: Thu, 7 Apr 1977 14:47:00 GMT");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-type: text/html");
header("Vary: Negotiate,Accept");

$linkverifier = new PMF_Linkverifier($faqConfig, $user->getLogin());
if ($linkverifier->isReady() == false) {
    if (count(ob_list_handlers()) > 0) {
        ob_clean();
    }
    print "disabled";
    exit();
}


$linkverifier->loadConfigurationFromDB();

$id      = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$artlang = PMF_Filter::filterInput(INPUT_GET, 'artlang', FILTER_SANITIZE_STRING);
$lookup  = PMF_Filter::filterInput(INPUT_GET, 'lookup', FILTER_VALIDATE_INT);

if (count(ob_list_handlers()) > 0) {
    ob_clean();
}
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
    <base href="<?php print PMF_Link::getSystemUri('index.php'); ?>" />

    <meta name="description" content="Only Chuck Norris can divide by zero.">
    <meta name="author" content="phpMyFAQ Team">
    <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;">
    <meta name="application-name" content="phpMyFAQ <?php print $faqConfig->get('main.currentVersion'); ?>">
    <meta name="copyright" content="(c) 2001-2012 phpMyFAQ Team">
    <meta name="publisher" content="phpMyFAQ Team">
    <meta name="MSSmartTagsPreventParsing" content="true">

    <link rel="stylesheet" href="style/admin.css?v=1">

    <script src="../inc/js/modernizr.min.js"></script>
    <script src="../inc/js/jquery.min.js"></script>
    <script src="../inc/js/functions.js"></script>
</head>
<body dir="<?php print $PMF_LANG["dir"]; ?>">
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
$faq->getRecord($id);

if (!isset($faq->faqRecord['content'])) {
?>
    Error: No entry for #<?php print $id; ?>(<?php print $artlang; ?>) available.
</body>
</html>
<?php
    exit();
}

if (!is_null($lookup)) {
    if (count(ob_list_handlers()) > 0) {
        ob_clean();
    }

    print $linkverifier->verifyArticleURL($faq->faqRecord['content'], $id, $artlang);
    exit();
}

?>
<?php link_ondemand_javascript($id, $artlang); ?>
</body>
</html>