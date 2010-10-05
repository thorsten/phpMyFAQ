<?php
/**
 * AJAX: onDemandURL
 * 
 * Usage:
 *   index.php?action=ajax&ajax=onDemandURL&id=<id>&lang=<lang>[&lookup=1]
 *
 * Performs link verification at demand of the user.
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
 * The Initial Developer of the Original Code is released for external use
 * with permission from NetJapan, Inc. IT Administration Group.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Minoru TODA <todam@netjapan.co.jp>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since     2005-09-30
 * @copyright 2005-2010 NetJapan, Inc.
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

$linkverifier = new PMF_Linkverifier($user->getLogin());
if ($linkverifier->isReady() == false) {
    if (count(ob_list_handlers()) > 0) {
        ob_clean();
    }
    print "disabled";
    exit();
}

$linkverifier->loadConfigurationFromDB();

$id     = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$lang   = PMF_Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_STRING);
$lookup = PMF_Filter::filterInput(INPUT_GET, 'lookup', FILTER_VALIDATE_INT);

if (count(ob_list_handlers()) > 0) {
    ob_clean();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $PMF_LANG["metaLanguage"]; ?>" lang="<?php print $PMF_LANG["metaLanguage"]; ?>">
<head>
    <title><?php print $faqconfig->get('main.titleFAQ'); ?> - powered by phpMyFAQ</title>
    <meta name="copyright" content="(c) 2001-2010 phpMyFAQ Team" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style type="text/css"> @import url(../template/<?php echo PMF_Template::getTplSetName(); ?>/admin.css); </style>
    <script type="text/javascript" src="../inc/js/jquery.min.js"></script>
</head>
<body id="body" dir="<?php print $PMF_LANG["dir"]; ?>">
<?php

if (!(isset($id) && isset($lang))) {
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
    Error: No entry for #<?php print $id; ?>(<?php print $lang; ?>) available.
</body>
</html>
<?php
    exit();
}

if (!is_null($lookup)) {
    if (count(ob_list_handlers()) > 0) {
        ob_clean();
    }
    print $linkverifier->verifyArticleURL($faq->faqRecord['content'], $id, $lang);
    exit();
}

?>
<?php link_ondemand_javascript($id, $lang); ?>
</body>
</html>