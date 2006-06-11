<?php
/**
* $Id: header.php,v 1.16 2006-06-11 08:12:03 thorstenr Exp $
*
* header of the admin area
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-02-26
* @copyright    (c) 2001-2006 phpMyFAQ Team
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

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-type: text/html; charset=".$PMF_LANG["metaCharset"]);
header("Vary: Negotiate,Accept");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $PMF_LANG["metaLanguage"]; ?>" lang="<?php print $PMF_LANG["metaLanguage"]; ?>">
<head>
    <title><?php print $PMF_CONF["title"]; ?> - powered by phpMyFAQ</title>
    <meta name="copyright" content="(c) 2001-2006 phpMyFAQ Team" />
    <meta http-equiv="Content-Type" content="text/html; charset=<?php print $PMF_LANG["metaCharset"]; ?>" />
    <link rel="shortcut icon" href="../template/favicon.ico" type="image/x-icon" />
    <link rel="icon" href="../template/favicon.ico" type="image/x-icon" />
    <style type="text/css"> @import url(../template/admin.css); </style>
    <script type="text/javascript" src="../inc/js/functions.js"></script>
    <script type="text/javascript" src="../inc/js/prototype.js"></script>
</head>
<body id="body" dir="<?php print $PMF_LANG["dir"]; ?>" onload="javascript:focusOnUsernameField();"><a name="top"></a>
<!-- Header -->
<div id="header">
    <h1>phpMyFAQ <?php print $PMF_CONF["version"]; ?></h1>
</div>

<!-- Navigation -->
<div class="sideBox">
