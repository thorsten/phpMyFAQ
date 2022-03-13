<?php

/**
 * AJAX: onDemandURL.
 *
 * Usage:
 *   index.php?action=ajax&ajax=onDemandURL&id=<id>&artlang=<lang>[&lookup=1]
 *
 * Performs link verification at demand of the user.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * The Initial Developer of the Original Code is released for external use
 * with permission from NetJapan, Inc. IT Administration Group.
 *
 * @package phpMyFAQ
 * @author Minoru TODA <todam@netjapan.co.jp>
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2022 NetJapan, Inc.
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2005-09-30
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\LinkVerifier;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$httpHeader = new HttpHelper();
$httpHeader->setContentType('text/html');
$httpHeader->addHeader();

$linkVerifier = new LinkVerifier($faqConfig, $user->getLogin());
if ($linkVerifier->isReady() === false) {
    if (count(ob_list_handlers()) > 0) {
        ob_clean();
    }
    echo 'disabled';
    exit();
}

$id = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$artlang = Filter::filterInput(INPUT_GET, 'artlang', FILTER_UNSAFE_RAW);
$lookup = Filter::filterInput(INPUT_GET, 'lookup', FILTER_VALIDATE_INT);

if (count(ob_list_handlers()) > 0) {
    ob_clean();
}
?>
<!DOCTYPE html>
<html lang="<?= $PMF_LANG['metaLanguage']; ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <title><?= $faqConfig->getTitle(); ?> - powered by phpMyFAQ</title>
    <base href="<?= $faqConfig->getDefaultUrl(); ?>">

    <meta name="description" content="Only Chuck Norris can divide by zero.">
    <meta name="author" content="phpMyFAQ Team">
    <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;">
    <meta name="application-name" content="phpMyFAQ <?= $faqConfig->getVersion(); ?>">
    <meta name="copyright" content="(c) 2001-<?= date('Y') ?> phpMyFAQ Team">
    <meta name="publisher" content="phpMyFAQ Team">
    <meta name="MSSmartTagsPreventParsing" content="true">

  <link rel="stylesheet" href="../assets/dist/admin-styles.css">

  <script src="../assets/dist/vendors.js"></script>
  <script src="../assets/dist/phpmyfaq.js"></script>
  <script src="../../assets/dist/backend.js"></script>
</head>
<body dir="<?= $PMF_LANG['dir']; ?>">
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
    Error: No entry for #<?= $id ?>(<?= $artlang ?>) available.
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
<?php LinkVerifier::linkOndemandJavascript($id, $artlang); ?>
</body>
</html>
