<?php
/**
 * Header of the admin area
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
 * @package   Administraion
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-26
 */

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

header("Expires: Thu, 7 Apr 1977 14:47:00 GMT");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-type: text/html; charset=utf-8");
header("Vary: Negotiate,Accept");

$secLevelEntries   = '';
$dashboardPage     = true;
$contentPage       = false;
$userPage          = false;
$statisticsPage    = false;
$exportsPage       = false;
$backupPage        = false;
$configurationPage = false;

switch ($action) {
    case 'user':
    case 'group':
    case 'passwd':
    case 'cookies':
        $secLevelHeader = $PMF_LANG['admin_mainmenu_users'];
        $secLevelEntries .= addMenuEntry('adduser+edituser+deluser', 'user', 'ad_menu_user_administration', $action);
        if ($faqconfig->get('main.permLevel') != 'basic') {
            $secLevelEntries .= addMenuEntry('adduser+edituser+deluser', 'group', 'ad_menu_group_administration', $action);
        }
        $secLevelEntries .= addMenuEntry('passwd', 'passwd', 'ad_menu_passwd', $action);
        $dashboardPage    = false;
        $userPage         = true;
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
    case 'showcategory':
    case 'editentry':
    case 'insertentry':
    case 'view':
    case 'glossary':
    case 'saveglossary':
    case 'updateglossary':
    case 'deleteglossary':
    case 'addglossary':
    case 'editglossary':
    case 'addnews':
    case 'editnews':
    case 'delnews':
    case 'question':
    case 'comments':
        $secLevelHeader = $PMF_LANG['admin_mainmenu_content'];
        $secLevelEntries .= addMenuEntry('addcateg+editcateg+delcateg', 'category', 'ad_menu_categ_edit', $action);
        $secLevelEntries .= addMenuEntry('addbt', 'editentry', 'ad_entry_add', $action);
        $secLevelEntries .= addMenuEntry('editbt+delbt', 'view', 'ad_menu_entry_edit', $action);
        $secLevelEntries .= addMenuEntry('delcomment', 'comments', 'ad_menu_comments', $action);
        $secLevelEntries .= addMenuEntry('delquestion', 'question', 'ad_menu_open', $action);
        $secLevelEntries .= addMenuEntry('addglossary+editglossary+delglossary', 'glossary', 'ad_menu_glossary', $action);
        $secLevelEntries .= addMenuEntry('addnews+editnews+delnews', 'news', 'ad_menu_news_edit', $action);
        $dashboardPage    = false;
        $contentPage      = true;
        break;
    case 'statistics':
    case 'viewsessions':
    case 'sessionbrowse':
    case 'sessionsuche':
    case 'adminlog':
    case 'searchstats':
        $secLevelHeader   = $PMF_LANG['admin_mainmenu_statistics'];
        $secLevelEntries .= addMenuEntry('viewlog', 'statistics', 'ad_menu_stat', $action);
        $secLevelEntries .= addMenuEntry('viewlog', 'viewsessions', 'ad_menu_session', $action);
        $secLevelEntries .= addMenuEntry('adminlog', 'adminlog', 'ad_menu_adminlog', $action);
        $secLevelEntries .= addMenuEntry('viewlog', 'searchstats', 'ad_menu_searchstats', $action);
        $dashboardPage    = false;
        $statisticsPage   = true;
        break;
    case 'export':
        $secLevelHeader   = $PMF_LANG['admin_mainmenu_exports'];
        $secLevelEntries .= addMenuEntry('', 'export', 'ad_menu_export', $action);
        $dashboardPage    = false;
        $exportsPage      = true;
        break;
    case 'backup':
        $secLevelHeader   = $PMF_LANG['admin_mainmenu_backup'];
        $secLevelEntries .= addMenuEntry('', 'backup', 'ad_menu_export', $action);
        $dashboardPage    = false;
        $backupPage       = true;
        break;
    case 'config':
    case 'linkconfig':
    case 'stopwordsconfig':
    case 'translist':
    case 'transedit':
    case 'transadd':
    case 'upgrade':
        $secLevelHeader    = $PMF_LANG['admin_mainmenu_configuration'];
        $secLevelEntries  .= addMenuEntry('editconfig', 'config', 'ad_menu_editconfig', $action);
        $secLevelEntries  .= addMenuEntry('editconfig+editbt+delbt', 'linkconfig', 'ad_menu_linkconfig', $action);
        $secLevelEntries  .= addMenuEntry('editconfig', 'stopwordsconfig', 'ad_menu_stopwordsconfig', $action);
        $secLevelEntries  .= addMenuEntry('edittranslation+addtranslation+deltranslation', 'translist', 'ad_menu_translations', $action);
        $dashboardPage     = false;
        $configurationPage = true;
        break;
    default:
        $secLevelHeader   = $PMF_LANG['admin_mainmenu_home'];
        $secLevelEntries .= addMenuEntry('addcateg+editcateg+delcateg', 'category', 'ad_menu_categ_edit');
        $secLevelEntries .= addMenuEntry('addbt', 'editentry', 'ad_quick_record');
        $secLevelEntries .= addMenuEntry('editbt+delbt', 'view', 'ad_menu_entry_edit');
        $secLevelEntries .= addMenuEntry('delquestion', 'question', 'ad_menu_open');
        $dashboardPage    = true;
        break;
}
?>
<!DOCTYPE html>
<html lang="<?php print $PMF_LANG['metaLanguage']; ?>">
<head>
    <title><?php print $faqconfig->get('main.titleFAQ'); ?> - powered by phpMyFAQ</title>
    <meta charset="utf-8">
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <meta http-equiv="content-language" content="<?php print $PMF_LANG['metaLanguage']; ?>">
    <meta name="application-name" content="phpMyFAQ <?php print $faqconfig->get('main.currentVersion'); ?>">
    <meta name="copyright" content="(c) 2001-2010 phpMyFAQ Team">

    <link rel="shortcut icon" href="../template/<?php print PMF_Template::getTplSetName(); ?>/favicon.ico" type="image/x-icon">
    <link rel="icon" href="../template/<?php print PMF_Template::getTplSetName(); ?>/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="style/admin.css" type="text/css">
    <link rel="stylesheet" href="../inc/js/plugins/autocomplete/jquery.autocomplete.css" type="text/css">
    <link rel="stylesheet" href="../inc/js/plugins/datePicker/datePicker.css" type="text/css">

    <script type="text/javascript" src="../inc/js/functions.js"></script>
    <script type="text/javascript" src="../inc/js/jquery.min.js"></script>
    <script type='text/javascript' src='../inc/js/plugins/autocomplete/jquery.autocomplete.pack.js'></script>
    <script type="text/javascript" src="../inc/js/plugins/datePicker/date.js"></script>
    <script type="text/javascript" src="../inc/js/plugins/datePicker/jquery.datePicker.js"></script>
    <script type="text/javascript" src="editor/tiny_mce.js?<?php print time(); ?>"></script>
</head>
<body id="body" dir="<?php print $PMF_LANG["dir"]; ?>">

<div id="wrap">
    <div id="top">
    <div class="rights"></div>
        <div id="languageselection">
        <?php if (isset($auth) && is_null($action)) { ?>
            <form action="index.php<?php print (isset($action) ? '?action=' . $action : ''); ?>" method="post">
            <label for="language"><?php print $PMF_LANG['msgLangaugeSubmit']; ?>: </label>
            <?php print PMF_Language::selectLanguages($LANGCODE, true); ?>
            </form>
        <?php } ?>
        </div>
    <div class="lefts">
    <h1><a class="mainpage" href="../"><?php print $faqconfig->get('main.titleFAQ'); ?></a></h1>
            <?php if (isset($auth)) { ?>
            <h2><?php print $PMF_LANG['ad_user_loggedin'] . $user->getUserData('display_name') . ' (' . $user->getLogin(); ?>)<br />
            <?php print $PMF_LANG['ad_session_expiration']; ?>: <span id="sessioncounter">Loading...</span></h2>
            <?php } ?>
        </div>
    </div>

<?php if (isset($auth)) { ?>
    <div id="topmenu">
    <div class="rights"></div>
    <div class="lefts">
    <ul>
        <li<?php print ($dashboardPage ? ' class="active"' : ''); ?>><a href="index.php"><?php print $PMF_LANG['admin_mainmenu_home']; ?></a></li>
        <li<?php print ($userPage ? ' class="active"' : ''); ?>><a href="index.php?action=user"><?php print $PMF_LANG['admin_mainmenu_users']; ?></a></li>
        <li<?php print ($contentPage ? ' class="active"' : ''); ?>><a href="index.php?action=content"><?php print $PMF_LANG['admin_mainmenu_content']; ?></a></li>
        <li<?php print ($statisticsPage ? ' class="active"' : ''); ?>><a href="index.php?action=statistics"><?php print $PMF_LANG['admin_mainmenu_statistics']; ?></a></li>
        <li<?php print ($exportsPage ? ' class="active"' : ''); ?>><a href="index.php?action=export"><?php print $PMF_LANG['admin_mainmenu_exports']; ?></a></li>
        <li<?php print ($backupPage ? ' class="active"' : ''); ?>><a href="index.php?action=backup"><?php print $PMF_LANG['admin_mainmenu_backup']; ?></a></li>
        <li<?php print ($configurationPage ? ' class="active"' : ''); ?>><a href="index.php?action=config"><?php print $PMF_LANG['admin_mainmenu_configuration']; ?></a></li>
        <li><a class="logout" href="index.php?action=logout"><?php print $PMF_LANG['admin_mainmenu_logout']; ?></a></li>
    </ul>
    </div>
    </div>

    <div id="main">
        <div id="leftmenu">
            <h2><?php print $secLevelHeader; ?></h2>
            <div class="box">
            <ul>
                <?php print $secLevelEntries; ?>
                </ul>
            </div>

            <h2>Admin worklog</h2>
            <div class="box"><span id="saving_data_indicator"></span></div>
        </div>

    <div id="maincontent">
<?php }