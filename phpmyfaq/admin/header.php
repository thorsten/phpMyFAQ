<?php
/**
* $Id: header.php,v 1.27 2006-09-19 21:28:33 matteo Exp $
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
    header('Location: http://'.$_SERVER['HTTP_HOST]'].dirname($_SERVER['SCRIPT_NAME']));
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
    <script type="text/javascript" src="editor/tiny_mce.js"></script>
    <script type="text/javascript" src="../inc/js/scriptaculous/scriptaculous.js"></script>
</head>
<body id="body" dir="<?php print $PMF_LANG["dir"]; ?>" onload="javascript:focusOnUsernameField();"><a name="top"></a>

<!-- header -->
<div id="header">
    <h1>phpMyFAQ <?php print $PMF_CONF["version"]; ?></h1>
<?php if (isset($auth)) { ?>
<?php if ('' == $_action) { ?>
    <div id="langform">
        <form action="<?php print($linkext); ?>" method="post">
        <label for="language"><?php print $PMF_LANG['msgLangaugeSubmit']; ?></label>
        <?php print selectLanguages($LANGCODE, true); ?>
        <input type="hidden" name="action" value="<?php print($_action); ?>" />
        </form>
    </div>
<?php } ?>
    <div id="sessionexpiration">
        <label for="session">Time to your session expiration</label>
        <div id="sessioncounter">Loading...</div>
    </div>
<?php } ?>
</div>

<?php if (isset($auth)) { ?>
<!-- administration menu -->
<div id="navcontainer">
    <ul id="navlist">
        <li><a href="index.php"><?php print $PMF_LANG['admin_mainmenu_home']; ?></a></li>
        <li><a href="index.php?action=user"><?php print $PMF_LANG['admin_mainmenu_users']; ?></a></li>
        <li><a href="index.php?action=content"><?php print $PMF_LANG['admin_mainmenu_content']; ?></a></li>
        <li><a href="index.php?action=statistics"><?php print $PMF_LANG['admin_mainmenu_statistics']; ?></a></li>
        <li><a href="index.php?action=export"><?php print $PMF_LANG['admin_mainmenu_exports']; ?></a></li>
        <li><a href="index.php?action=backup"><?php print $PMF_LANG['admin_mainmenu_backup']; ?></a></li>
        <li><a href="index.php?action=config"><?php print $PMF_LANG['admin_mainmenu_configuration']; ?></a></li>
        <li><a id="logout" href="index.php?action=logout"><?php print $PMF_LANG['admin_mainmenu_logout']; ?></a></li>
    </ul>
</div>

<!-- sub-administration menu -->
<div id="subnavcontainer">
    <ul id="subnavlist">
<?php
    // check for group support
    require_once(PMF_ROOT_DIR.'/inc/PMF_User/User.php');
    $user = new PMF_User();
    $groupSupport = is_a($user->perm, "PMF_PermMedium");

    switch ($_action) {
        case 'user':
        case 'group':
        case 'passwd':
        case 'cookies':
            addMenuEntry('adduser,edituser,deluser',             'user',             'ad_menu_user_administration');
            if ($groupSupport) {
                addMenuEntry('adduser,edituser,deluser',         'group',            'ad_menu_group_administration');
            }
            addMenuEntry('passwd',                               'passwd',           'ad_menu_passwd');
            addMenuEntry('',                                     'cookies',          'ad_menu_cookie');
            break;
        case 'content':
        case 'category':
        case 'addcategory':
        case 'savecategory':
        case 'editcategory':
        case 'translatecategory':
        case 'updatecategory':
        case 'deletecategory':
        case 'removecategory':
        case 'cutcategory':
        case 'pastecategory':
        case 'movecategory':
        case 'changecategory':
        case 'editentry':
        case 'accept':
        case 'view':
        case 'glossary':
        case 'saveglossary':
        case 'updateglossary':
        case 'deleteglossary':
        case 'addglossary':
        case 'editglossary':
        case 'news':
        case 'question':
            addMenuEntry('addcateg,editcateg,delcateg',          'category',         'ad_menu_categ_edit');
            addMenuEntry('addbt',                                'editentry',        'ad_entry_add');
            addMenuEntry('editbt,delbt',                         'accept',           'ad_menu_entry_aprove');
            addMenuEntry('editbt,delbt',                         'view',             'ad_menu_entry_edit');
            addMenuEntry('delquestion',                          'question',         'ad_menu_open');
            addMenuEntry('addglossary,editglossary,delglossary', 'glossary',         'ad_menu_glossary');
            addMenuEntry('addnews,editnews,delnews',             'news&amp;do=edit', 'ad_menu_news_edit');
            break;
        case 'statistics':
        case 'viewsessions':
        case 'adminlog':
            addMenuEntry('viewlog',                              'statistics',       'ad_menu_stat');
            addMenuEntry('viewlog',                              'viewsessions',     'ad_menu_session');
            addMenuEntry('adminlog',                             'adminlog',         'ad_menu_adminlog');
            break;
        case 'export':
        case 'plugins':
            addMenuEntry('',                                     'export',           'ad_menu_export');
            addMenuEntry('',                                     'plugins',          'ad_menu_searchplugin');
            break;
        case 'config':
        case 'linkconfig':
            addMenuEntry('editconfig',                           'config',           'ad_menu_editconfig');
            addMenuEntry('editconfig,editbt,delbt',              'linkconfig',       'ad_menu_linkconfig');
            break;
    }
?>
    </ul>
</div>
<?php } ?>
<!-- content of body -->
<div id="bodyText">
