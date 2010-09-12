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

if (isset($auth)) {
    $user         = new PMF_User();
    $groupSupport = ($user->perm instanceof PMF_Perm_PermMedium);
    $adminHelper  = PMF_Helper_Administration::getInstance();
    $adminHelper->setPermission($permission);

    $menuGroup = $secLevelEntries = '';
    
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
            $menuGroup        = 'user';
            $secLevelEntries .= $adminHelper->addMenuEntry('adduser+edituser+deluser', 'user', 'ad_menu_user_administration', $action);
            if ($groupSupport) {
                $secLevelEntries .= $adminHelper->addMenuEntry('adduser+edituser+deluser', 'group', 'ad_menu_group_administration', $action);
            }
            $secLevelEntries .= $adminHelper->addMenuEntry('passwd', 'passwd', 'ad_menu_passwd', $action);
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
        case 'news':
        case 'addnews':
        case 'editnews':
        case 'delnews':
        case 'savenews':
        case 'question':
        case 'comments':
            $menuGroup        = 'content';
            $secLevelEntries .= $adminHelper->addMenuEntry('addcateg+editcateg+delcateg', 'category', 'ad_menu_categ_edit', $action);
            $secLevelEntries .= $adminHelper->addMenuEntry('addbt', 'editentry', 'ad_entry_add', $action);
            $secLevelEntries .= $adminHelper->addMenuEntry('editbt+delbt', 'view', 'ad_menu_entry_edit', $action);
            $secLevelEntries .= $adminHelper->addMenuEntry('delcomment', 'comments', 'ad_menu_comments', $action);
            $secLevelEntries .= $adminHelper->addMenuEntry('delquestion', 'question', 'ad_menu_open', $action);
            $secLevelEntries .= $adminHelper->addMenuEntry('addglossary+editglossary+delglossary', 'glossary', 'ad_menu_glossary', $action);
            $secLevelEntries .= $adminHelper->addMenuEntry('addnews+editnews+delnews', 'news', 'ad_menu_news_edit', $action);
            $dashboardPage    = false;
            $contentPage      = true;
            break;
        case 'statistics':
        case 'viewsessions':
        case 'sessionbrowse':
        case 'sessionsuche':
        case 'adminlog':
        case 'searchstats':
            $menuGroup        = 'statistics';
            $secLevelEntries .= $adminHelper->addMenuEntry('viewlog', 'statistics', 'ad_menu_stat', $action);
            $secLevelEntries .= $adminHelper->addMenuEntry('viewlog', 'viewsessions', 'ad_menu_session', $action);
            $secLevelEntries .= $adminHelper->addMenuEntry('adminlog', 'adminlog', 'ad_menu_adminlog', $action);
            $secLevelEntries .= $adminHelper->addMenuEntry('viewlog', 'searchstats', 'ad_menu_searchstats', $action);
            $dashboardPage    = false;
            $statisticsPage   = true;
            break;
        case 'export':
            $menuGroup        = 'export';
            $secLevelEntries .= $adminHelper->addMenuEntry('', 'export', 'ad_menu_export', $action);
            $dashboardPage    = false;
            $exportsPage      = true;
            break;
        case 'config':
        case 'linkconfig':
        case 'stopwordsconfig':
        case 'translist':
        case 'transedit':
        case 'transadd':
        case 'upgrade':
            $menuGroup         = 'config';
            $secLevelEntries  .= $adminHelper->addMenuEntry('editconfig', 'config', 'ad_menu_editconfig', $action);
            $secLevelEntries  .= $adminHelper->addMenuEntry('editconfig+editbt+delbt', 'linkconfig', 'ad_menu_linkconfig', $action);
            $secLevelEntries  .= $adminHelper->addMenuEntry('editconfig', 'stopwordsconfig', 'ad_menu_stopwordsconfig', $action);
            $secLevelEntries  .= $adminHelper->addMenuEntry('edittranslation+addtranslation+deltranslation', 'translist', 'ad_menu_translations', $action);
            $dashboardPage     = false;
            $configurationPage = true;
            break;
        case 'backup':
            $menuGroup       = 'backup';
            $secLevelEntries = '';
            $dashboardPage   = false;
            $backupPage      = true;
            break;
        default:
            $secLevelEntries .= $adminHelper->addMenuEntry('addcateg+editcateg+delcateg', 'category', 'ad_menu_categ_edit');
            $secLevelEntries .= $adminHelper->addMenuEntry('addbt', 'editentry', 'ad_quick_record');
            $secLevelEntries .= $adminHelper->addMenuEntry('editbt+delbt', 'view', 'ad_menu_entry_edit');
            $secLevelEntries .= $adminHelper->addMenuEntry('delquestion', 'question', 'ad_menu_open');
            $dashboardPage    = true;
            break;
    }
    $firstLevelEntries  = $adminHelper->addMenuEntry('', '', 'admin_mainmenu_home', $menuGroup, false);
    $firstLevelEntries .= $adminHelper->addMenuEntry('', 'user', 'admin_mainmenu_users', $menuGroup, false);
    $firstLevelEntries .= $adminHelper->addMenuEntry('', 'content', 'admin_mainmenu_content', $menuGroup, false);
    $firstLevelEntries .= $adminHelper->addMenuEntry('', 'statistics', 'admin_mainmenu_statistics', $menuGroup, false);
    $firstLevelEntries .= $adminHelper->addMenuEntry('', 'export', 'admin_mainmenu_exports', $menuGroup, false);
    $firstLevelEntries .= $adminHelper->addMenuEntry('', 'backup', 'admin_mainmenu_backup', $action, false);
    $firstLevelEntries .= $adminHelper->addMenuEntry('', 'config', 'admin_mainmenu_configuration', $menuGroup, false);
}

header("Expires: Thu, 7 Apr 1977 14:47:00 GMT");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-type: text/html; charset=utf-8");
header("Vary: Negotiate,Accept");
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
    
    <link rel="shortcut icon" href="../template/<?php echo PMF_Template::getTplSetName(); ?>/favicon.ico" type="image/x-icon" />
    <link rel="icon" href="../template/<?php echo PMF_Template::getTplSetName(); ?>/favicon.ico" type="image/x-icon" />
    
    <style type="text/css"> @import url(style/reset.css); </style>
    <style type="text/css"> @import url(style/960.css); </style>
    <style type="text/css"> @import url(style/admin.css); </style>
    <style type="text/css"> @import url(../inc/js/plugins/autocomplete/jquery.autocomplete.css); </style>
    <style type="text/css"> @import url(../inc/js/plugins/datePicker/datePicker.css); </style>
    
    <script type="text/javascript" src="../inc/js/functions.js"></script>
    <script type="text/javascript" src="../inc/js/jquery.min.js"></script>
    <script type='text/javascript' src='../inc/js/plugins/autocomplete/jquery.autocomplete.pack.js'></script>
    <script type="text/javascript" src="../inc/js/plugins/datePicker/date.js"></script>
    <script type="text/javascript" src="../inc/js/plugins/datePicker/jquery.datePicker.js"></script>
    <script type="text/javascript" src="editor/tiny_mce.js?<?php print time(); ?>"></script>
</head>
<body id="body" dir="<?php print $PMF_LANG["dir"]; ?>">

<a name="top"></a>

<!-- header -->
<div id="headerWrapper">
    <div id="header" class="container_16">
        
        <h1><a href="../">phpMyFAQ <?php print $faqconfig->get('main.currentVersion'); ?></a></h1>
        <?php if (isset($auth)) { ?>
        <p id="session"><?php print $PMF_LANG['ad_user_loggedin'] . $user->getUserData('display_name') . ' (' . $user->getLogin(); ?>)<br />
	<?php print $PMF_LANG['ad_session_expiration']; ?>: <span id="sessioncounter">Loading...</span></p>
        <?php } ?>
        
        

        <div id="navigation">
        <?php if (isset($auth)) { ?>
            <ul>
                <?php print $firstLevelEntries; ?>
                <li><a class="logout" href="?action=logout"><?php print $PMF_LANG['admin_mainmenu_logout']; ?></a></li>
                
            </ul>
        <?php } ?>
        </div>
        
        <?php if (isset($auth) && is_null($action)) { ?>
        <form id="languageSelection" action="index.php<?php print (isset($action) ? '?action=' . $action : ''); ?>" method="post">
        <p>
            <label for="language"><?php print $PMF_LANG['msgLangaugeSubmit']; ?>: </label>
            <?php print PMF_Language::selectLanguages($LANGCODE, true); ?>
        </p>
        </form>
        <?php } ?>
        
    </div>
</div>
<!-- /header -->

<!-- content -->
<div id="contentWrapper">
    <div id="mainContent" class="container_16">
<?php if (isset($auth)) { ?>
        <div id="leftContent" class="grid_4">
            <div class="leftMenu">
                <ul>
                <?php print $secLevelEntries; ?>
                </ul>
            </div>

            <div class="leftMenu" id="adminWorklog">
                <span id="saving_data_indicator"></span>
            </div>
        </div>
        <div id="rightContent" class="grid_12">
<?php }
