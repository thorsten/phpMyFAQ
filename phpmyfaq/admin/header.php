<?php
/**
 * Header of the admin area
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administraion
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-26
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$httpHeader = new PMF_Helper_Http();
$httpHeader->setContentType('text/html');
$httpHeader->addHeader();

$secLevelEntries   = '';
$dashboardPage     = true;
$contentPage       = false;
$userPage          = false;
$statisticsPage    = false;
$exportsPage       = false;
$backupPage        = false;
$configurationPage = false;
$edAutosave        = (('editentry' === $action) && $faqConfig->get('records.autosaveActive'));

$adminHelper = new PMF_Helper_Administration();
$adminHelper->setPermission($permission);

switch ($action) {
    case 'user':
    case 'group':
    case 'passwd':
    case 'cookies':
        $secLevelHeader = $PMF_LANG['admin_mainmenu_users'];
        $secLevelEntries .= $adminHelper->addMenuEntry('adduser+edituser+deluser', 'user', 'ad_menu_user_administration', $action);
        if ($faqConfig->get('security.permLevel') != 'basic') {
            $secLevelEntries .= $adminHelper->addMenuEntry('addgroup+editgroup+delgroup', 'group', 'ad_menu_group_administration', $action);
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
    case 'saveentry':
    case 'view':
    case 'searchfaqs':
    case 'glossary':
    case 'saveglossary':
    case 'updateglossary':
    case 'deleteglossary':
    case 'addglossary':
    case 'editglossary':
    case 'news';
    case 'addnews':
    case 'editnews':
    case 'savenews':
    case 'updatenews';
    case 'delnews':
    case 'question':
    case 'takequestion':
    case 'comments':
    case 'attachments':
        $secLevelHeader   = $PMF_LANG['admin_mainmenu_content'];
        $secLevelEntries .= $adminHelper->addMenuEntry('addcateg+editcateg+delcateg', 'category', 'ad_menu_categ_edit', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('addbt', 'editentry', 'ad_entry_add', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('editbt+delbt', 'view', 'ad_menu_entry_edit', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('editbt+delbt', 'searchfaqs', 'ad_menu_searchfaqs', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('delcomment', 'comments', 'ad_menu_comments', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('delquestion', 'question', 'ad_menu_open', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('addglossary+editglossary+delglossary', 'glossary', 'ad_menu_glossary', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('addnews+editnews+delnews', 'news', 'ad_menu_news_edit', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('addattachment+editattachment+delattachment', 'attachments', 'ad_menu_attachments', $action);
        $dashboardPage    = false;
        $contentPage      = true;
        break;
    case 'statistics':
    case 'viewsessions':
    case 'sessionbrowse':
    case 'sessionsuche':
    case 'adminlog':
    case 'searchstats':
    case 'reports':
    case 'reportview':
        $secLevelHeader   = $PMF_LANG['admin_mainmenu_statistics'];
        $secLevelEntries .= $adminHelper->addMenuEntry('viewlog', 'statistics', 'ad_menu_stat', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('viewlog', 'viewsessions', 'ad_menu_session', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('adminlog', 'adminlog', 'ad_menu_adminlog', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('viewlog', 'searchstats', 'ad_menu_searchstats', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('reports', 'reports', 'ad_menu_reports', $action);
        $dashboardPage    = false;
        $statisticsPage   = true;
        break;
    case 'export':
        $secLevelHeader   = $PMF_LANG['admin_mainmenu_exports'];
        $secLevelEntries .= $adminHelper->addMenuEntry('', 'export', 'ad_menu_export', $action);
        $dashboardPage    = false;
        $exportsPage      = true;
        break;
    case 'backup':
        $secLevelHeader   = $PMF_LANG['admin_mainmenu_backup'];
        $secLevelEntries .= $adminHelper->addMenuEntry('', 'backup', 'ad_menu_export', $action);
        $dashboardPage    = false;
        $backupPage       = true;
        break;
    case 'config':
    case 'stopwordsconfig':
    case 'translist':
    case 'transedit':
    case 'transadd':
    case 'upgrade':
    case 'instances':
        $secLevelHeader    = $PMF_LANG['admin_mainmenu_configuration'];
        $secLevelEntries  .= $adminHelper->addMenuEntry('editconfig', 'config', 'ad_menu_editconfig', $action);
        $secLevelEntries  .= $adminHelper->addMenuEntry('editinstances+addinstances+delinstances', 'instances', 'ad_menu_instances', $action);
        $secLevelEntries  .= $adminHelper->addMenuEntry('editconfig', 'stopwordsconfig', 'ad_menu_stopwordsconfig', $action);
        $secLevelEntries  .= $adminHelper->addMenuEntry('edittranslation+addtranslation+deltranslation', 'translist', 'ad_menu_translations', $action);
        $dashboardPage     = false;
        $configurationPage = true;
        break;
    default:
        $secLevelHeader   = $PMF_LANG['admin_mainmenu_home'];
        $secLevelEntries .= $adminHelper->addMenuEntry('addcateg+editcateg+delcateg', 'category', 'ad_menu_categ_edit');
        $secLevelEntries .= $adminHelper->addMenuEntry('addbt', 'editentry', 'ad_quick_record');
        $secLevelEntries .= $adminHelper->addMenuEntry('editbt+delbt', 'view', 'ad_menu_entry_edit');
        $secLevelEntries .= $adminHelper->addMenuEntry('delquestion', 'question', 'ad_menu_open');
        $dashboardPage    = true;
        break;
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
    
    <title><?php print $faqConfig->get('main.titleFAQ'); ?> - powered by phpMyFAQ <?php print $faqConfig->get('main.currentVersion'); ?></title>
    <base href="<?php print $faqConfig->get('main.referenceURL'); ?>/admin/" />
    
    <meta name="description" content="Only Chuck Norris can divide by zero.">
    <meta name="author" content="phpMyFAQ Team">
    <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;">
    <meta name="application-name" content="phpMyFAQ <?php print $faqConfig->get('main.currentVersion'); ?>">
    <meta name="copyright" content="(c) 2001-2013 phpMyFAQ Team">
    <meta name="publisher" content="phpMyFAQ Team">
    <meta name="MSSmartTagsPreventParsing" content="true">
    
    <link rel="stylesheet" href="assets/css/style.css?v=1">
    <link rel="stylesheet" href="../assets/js/plugins/datePicker/datePicker.css" type="text/css">

    <script src="../assets/js/libs/modernizr.min.js"></script>
    <script src="../assets/js/libs/jquery.min.js"></script>
    <script src="../assets/js/phpmyfaq.js"></script>

    <script src="../assets/js/plugins/datePicker/date.js"></script>
    <script src="../assets/js/plugins/datePicker/jquery.datePicker.js"></script>
    <script src="editor/tiny_mce.js?<?php print time(); ?>"></script>

<?php if ($edAutosave): ?>
    <script>var pmfAutosaveInterval = <?php echo $faqConfig->get('records.autosaveSecs') ?>;</script>
    <script src="../assets/js/autosave.js"></script>
<?php endif; ?>
    
    <link rel="shortcut icon" href="../assets/template/<?php print PMF_Template::getTplSetName(); ?>/favicon.ico">
    <link rel="apple-touch-icon" href="../assets/template/<?php print PMF_Template::getTplSetName(); ?>/apple-touch-icon.png">
</head>
<body dir="<?php print $PMF_LANG["dir"]; ?>">

<div class="navbar navbar-fixed-top">
    <div class="navbar-inner">
        <div class="container-fluid">
            <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </a>
            <a class="brand" href="../index.php"><?php print $faqConfig->get('main.titleFAQ'); ?></a>
            <div class="nav-collapse">
                <?php if (isset($auth) && in_array(true, $permission)): ?>
                <ul class="nav">
                    <li<?php print ($dashboardPage ? ' class="active"' : ''); ?>>
                        <a href="index.php">
                            <i class="icon-home icon-white"></i> <?php print $PMF_LANG['admin_mainmenu_home']; ?>
                        </a>
                    </li>
                    <li<?php print ($userPage ? ' class="active"' : ''); ?>>
                        <a href="index.php?action=user">
                            <i class="icon-user icon-white"></i> <?php print $PMF_LANG['admin_mainmenu_users']; ?>
                        </a>
                    </li>
                    <li<?php print ($contentPage ? ' class="active"' : ''); ?>>
                        <a href="index.php?action=content">
                            <i class="icon-pencil icon-white"></i> <?php print $PMF_LANG['admin_mainmenu_content']; ?>
                        </a>
                    </li>
                    <li<?php print ($statisticsPage ? ' class="active"' : ''); ?>>
                        <a href="index.php?action=statistics">
                            <i class="icon-tasks icon-white"></i> <?php print $PMF_LANG['admin_mainmenu_statistics']; ?>
                        </a>
                    </li>
                    <li<?php print ($exportsPage ? ' class="active"' : ''); ?>>
                        <a href="index.php?action=export">
                            <i class="icon-book icon-white"></i> <?php print $PMF_LANG['admin_mainmenu_exports']; ?>
                        </a>
                    </li>
                    <li<?php print ($backupPage ? ' class="active"' : ''); ?>>
                        <a href="index.php?action=backup">
                            <i class="icon-download-alt icon-white"></i> <?php print $PMF_LANG['admin_mainmenu_backup']; ?>
                        </a>
                    </li>
                    <li<?php print ($configurationPage ? ' class="active"' : ''); ?>>
                        <a href="index.php?action=config">
                            <i class="icon-wrench icon-white"></i> <?php print $PMF_LANG['admin_mainmenu_configuration']; ?>
                        </a>
                    </li>
                </ul>
                <ul class="nav pull-right">
                    <li class="divider-vertical"></li>
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                            <span title="<?php print $PMF_LANG['ad_user_loggedin'] . $user->getLogin(); ?>">
                            <?php print $user->getUserData('display_name'); ?>
                            </span>
                            <b class="caret"></b>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <?php print $PMF_LANG['ad_session_expiration']; ?>: <span id="sessioncounter">Loading...</span>
                            </li>
                            <li class="divider"></li>
                            <li>
                                <a href="index.php?action=logout"><i class="icon-off"></i> <?php print $PMF_LANG['admin_mainmenu_logout']; ?></a>
                            </li>
                        </ul>
                    </li>
                </ul>
                <?php else: ?>
                <ul class="nav">
                    <li><a href="../index.php?action=password"><?php print $PMF_LANG["lostPassword"]; ?></a></li>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div id="main">
    <div class="container-fluid">
        <div class="row-fluid">
            <?php if (isset($auth) && in_array(true, $permission)) { ?>
            <div class="span2">
                <div class="well categories">
                    <ul class="nav nav-list">
                        <li class="nav-header"><?php print $secLevelHeader; ?></li>
                        <?php print $secLevelEntries; ?>
                        <li class="nav-header">Admin worklog</li>
                        <li><span id="saving_data_indicator"></span></li>
                        <li class="nav-header">Found an issue?</li>
                        <li><a href="https://github.com/thorsten/phpMyFAQ/issues/" target="_blank">Please report it here</a></li>
                    </ul>
                </div>
            </div>
            <?php } ?>

            <div class="span10">
