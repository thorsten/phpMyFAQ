<?php
/**
 * Header of the admin area.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-26
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

$secLevelEntries = '';
$dashboardPage = true;
$contentPage = false;
$userPage = false;
$statisticsPage = false;
$exportsPage = false;
$backupPage = false;
$configurationPage = false;
$edAutosave = (('editentry' === $action) && $faqConfig->get('records.autosaveActive'));

$adminHelper = new PMF_Helper_Administration();
$adminHelper->setUser($user);

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
        $dashboardPage = false;
        $userPage = true;
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
    case 'news':
    case 'addnews':
    case 'editnews':
    case 'savenews':
    case 'updatenews':
    case 'delnews':
    case 'question':
    case 'takequestion':
    case 'comments':
    case 'attachments':
    case 'tags':
        $secLevelHeader = $PMF_LANG['admin_mainmenu_content'];
        $secLevelEntries .= $adminHelper->addMenuEntry('addcateg+editcateg+delcateg', 'category', 'ad_menu_categ_edit', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('addbt', 'editentry', 'ad_entry_add', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('editbt+delbt', 'view', 'ad_menu_entry_edit', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('editbt+delbt', 'searchfaqs', 'ad_menu_searchfaqs', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('delcomment', 'comments', 'ad_menu_comments', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('delquestion', 'question', 'ad_menu_open', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('addglossary+editglossary+delglossary', 'glossary', 'ad_menu_glossary', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('addnews+editnews+delnews', 'news', 'ad_menu_news_edit', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('addattachment+editattachment+delattachment', 'attachments', 'ad_menu_attachments', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('editbt', 'tags', 'ad_entry_tags', $action);
        $dashboardPage = false;
        $contentPage = true;
        break;
    case 'statistics':
    case 'viewsessions':
    case 'sessionbrowse':
    case 'sessionsuche':
    case 'adminlog':
    case 'searchstats':
    case 'reports':
    case 'reportview':
        $secLevelHeader = $PMF_LANG['admin_mainmenu_statistics'];
        $secLevelEntries .= $adminHelper->addMenuEntry('viewlog', 'statistics', 'ad_menu_stat', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('viewlog', 'viewsessions', 'ad_menu_session', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('adminlog', 'adminlog', 'ad_menu_adminlog', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('viewlog', 'searchstats', 'ad_menu_searchstats', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('reports', 'reports', 'ad_menu_reports', $action);
        $dashboardPage = false;
        $statisticsPage = true;
        break;
    case 'export':
        $secLevelHeader = $PMF_LANG['admin_mainmenu_exports'];
        $secLevelEntries .= $adminHelper->addMenuEntry('', 'export', 'ad_menu_export', $action);
        $dashboardPage = false;
        $exportsPage = true;
        break;
    case 'backup':
        $secLevelHeader = $PMF_LANG['admin_mainmenu_backup'];
        $secLevelEntries .= $adminHelper->addMenuEntry('', 'backup', 'ad_menu_export', $action);
        $dashboardPage = false;
        $backupPage = true;
        break;
    case 'config':
    case 'stopwordsconfig':
    case 'translist':
    case 'transedit':
    case 'transadd':
    case 'upgrade':
    case 'instances':
    case 'system':
    case 'elasticsearch':
        $secLevelHeader = $PMF_LANG['admin_mainmenu_configuration'];
        $secLevelEntries .= $adminHelper->addMenuEntry('editconfig', 'config', 'ad_menu_editconfig', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('', 'system', 'ad_system_info', $action, false);
        $secLevelEntries .= $adminHelper->addMenuEntry('editinstances+addinstances+delinstances', 'instances', 'ad_menu_instances', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('editconfig', 'stopwordsconfig', 'ad_menu_stopwordsconfig', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('edittranslation+addtranslation+deltranslation', 'translist', 'ad_menu_translations', $action);
        if ($faqConfig->get('search.enableElasticsearch')) {
            $secLevelEntries .= $adminHelper->addMenuEntry(
                'editconfig',
                'elasticsearch',
                'ad_menu_elasticsearch',
                $action
            );
        }
        $dashboardPage = false;
        $configurationPage = true;
        break;
    default:
        $secLevelHeader = $PMF_LANG['admin_mainmenu_home'];
        $secLevelEntries .= $adminHelper->addMenuEntry('addcateg+editcateg+delcateg', 'category', 'ad_menu_categ_edit');
        $secLevelEntries .= $adminHelper->addMenuEntry('addbt', 'editentry', 'ad_quick_record');
        $secLevelEntries .= $adminHelper->addMenuEntry('editbt+delbt', 'view', 'ad_menu_entry_edit');
        $secLevelEntries .= $adminHelper->addMenuEntry('delquestion', 'question', 'ad_menu_open');
        $secLevelEntries .= $adminHelper->addMenuEntry('', 'system', 'ad_system_info', $action, false);
        $dashboardPage = true;
        break;
}
?>
<!DOCTYPE html>
<!--[if IE 9 ]> <html lang="<?php echo $PMF_LANG['metaLanguage']; ?>" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="<?php echo $PMF_LANG['metaLanguage']; ?>" class="no-js"> <!--<![endif]-->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <title><?php echo $faqConfig->get('main.titleFAQ'); ?> - powered by phpMyFAQ <?php echo $faqConfig->get('main.currentVersion'); ?></title>
    <base href="<?php echo $faqSystem->getSystemUri($faqConfig) ?>admin/" />

    <meta name="description" content="Only Chuck Norris can divide by zero.">
    <meta name="author" content="phpMyFAQ Team">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="application-name" content="phpMyFAQ <?php echo $faqConfig->get('main.currentVersion'); ?>">
    <meta name="copyright" content="(c) 2001-<?php echo date('Y') ?> phpMyFAQ Team">
    <meta name="publisher" content="phpMyFAQ Team">
    <meta name="robots" content="<?php echo $faqConfig->get('seo.metaTagsAdmin') ?>">
    <meta name="MSSmartTagsPreventParsing" content="true">

    <link rel="stylesheet" href="assets/css/style.css?v=1">

    <script src="../assets/js/modernizr.min.js"></script>
    <script src="../assets/js/phpmyfaq.min.js"></script>
    <script src="assets/js/sidebar.js"></script>
    <script src="assets/js/editor/tinymce.min.js?<?php echo time(); ?>"></script>

<?php if ($edAutosave): ?>
    <script>var pmfAutosaveInterval = <?php echo $faqConfig->get('records.autosaveSecs') ?>;</script>
    <script src="../assets/js/autosave.js" async></script>
<?php endif; ?>

    <link rel="shortcut icon" href="../assets/template/<?php echo PMF_Template::getTplSetName(); ?>/favicon.ico">
    <link rel="apple-touch-icon" href="../assets/template/<?php echo PMF_Template::getTplSetName(); ?>/apple-touch-icon.png">
</head>
<body dir="<?php echo $PMF_LANG['dir']; ?>">

<div id="wrapper">

    <nav class="navbar navbar-default navbar-static-top navbar-admin">
        <div class="navbar-header">
            <?php if (isset($auth) && count($user->perm->getAllUserRights($user->getUserId())) > 0): ?>
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar-collapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <?php endif; ?>
            <a class="navbar-brand" title="<?php echo $faqConfig->get('main.titleFAQ') ?>" href="../index.php">
                phpMyFAQ <?php echo $faqConfig->get('main.currentVersion') ?>
            </a>
        </div>

        <?php if (isset($auth) && count($user->perm->getAllUserRights($user->getUserId())) > 0): ?>
        <ul class="nav navbar-nav navbar-right">
            <li class="dropdown">
                <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                    <?php
                    if ($faqConfig->get('main.enableGravatarSupport')) {
                        $avatar = new PMF_Services_Gravatar($faqConfig);
                        echo $avatar->getImage($user->getUserData('email'), ['size' => 24]);
                    } else {
                        echo '<b class="fa fa-user"></b>';
                    }
                    ?>
                    <span title="<?php echo $PMF_LANG['ad_user_loggedin'].$user->getLogin(); ?>">
                        <?php echo $user->getUserData('display_name'); ?>
                    </span>
                    <b class="fa fa-caret-down"></b>
                </a>
                <ul class="dropdown-menu">
                    <li>
                        <a href="index.php?action=passwd">
                            <i aria-hidden="true" class="fa fa-lock"></i> <?php echo $PMF_LANG['ad_menu_passwd'] ?>
                        </a>
                    </li>
                    <li class="divider"></li>
                    <li>
                        <a href="index.php?action=logout">
                            <i aria-hidden="true" class="fa fa-power-off"></i> <?php echo $PMF_LANG['admin_mainmenu_logout']; ?>
                        </a>
                    </li>
                </ul>
            </li>
            <li>
                <form action="index.php<?php echo(isset($action) ? '?action='.$action : ''); ?>" method="post"
                      class="navbar-form navbar-right" role="form" accept-charset="utf-8">
                    <?php echo PMF_Language::selectLanguages($LANGCODE, true); ?>
                </form>
            </li>
        </ul>
        <?php endif; ?>
    </nav>

    <?php if (isset($auth) && count($user->perm->getAllUserRights($user->getUserId())) > 0): ?>
    <nav class="navbar-default navbar-static-side" role="navigation">
        <div class="sidebar-collapse">
            <ul class="nav" id="side-menu">
                <li<?php echo($dashboardPage ? ' class="active"' : ''); ?>>
                    <a href="index.php">
                        <i aria-hidden="true" class="fa fa-dashboard fa-fw"></i> <?php echo $PMF_LANG['admin_mainmenu_home']; ?>
                    </a>
                </li>
                <li<?php echo($userPage ? ' class="active"' : ''); ?>>
                    <a href="index.php?action=user">
                        <i aria-hidden="true" class="fa fa-users"></i> <?php echo $PMF_LANG['admin_mainmenu_users']; ?>
                        <span class="fa arrow"></span>
                    </a>
                    <ul class="nav nav-second-level collapse <?php echo($userPage ? 'in' : '') ?>">
                        <?php echo $secLevelEntries; ?>
                    </ul>
                </li>
                <li<?php echo($contentPage ? ' class="active"' : ''); ?>>
                    <a href="index.php?action=content">
                        <i aria-hidden="true" class="fa fa-edit fa-fw"></i> <?php echo $PMF_LANG['admin_mainmenu_content']; ?>
                        <span class="fa arrow"></span>
                    </a>
                    <ul class="nav nav-second-level collapse <?php echo($contentPage ? 'in' : '') ?>">
                        <?php echo $secLevelEntries; ?>
                    </ul>
                </li>
                <li<?php echo($statisticsPage ? ' class="active"' : ''); ?>>
                    <a href="index.php?action=statistics">
                        <i aria-hidden="true" class="fa fa-tasks fa-fw"></i> <?php echo $PMF_LANG['admin_mainmenu_statistics']; ?>
                        <span class="fa arrow"></span>
                    </a>
                    <ul class="nav nav-second-level collapse <?php echo($statisticsPage ? 'in' : '') ?>">
                        <?php echo $secLevelEntries; ?>
                    </ul>
                </li>
                <li<?php echo($exportsPage ? ' class="active"' : ''); ?>>
                    <a href="index.php?action=export">
                        <i aria-hidden="true" class="fa fa-book fa-fw"></i> <?php echo $PMF_LANG['admin_mainmenu_exports']; ?>
                    </a>
                </li>
                <li<?php echo($backupPage ? ' class="active"' : ''); ?>>
                    <a href="index.php?action=backup">
                        <i aria-hidden="true" class="fa fa-download fa-fw"></i> <?php echo $PMF_LANG['admin_mainmenu_backup']; ?>
                    </a>
                    <ul class="nav nav-second-level collapse">
                        <?php echo $secLevelEntries; ?>
                    </ul>
                </li>
                <li<?php echo($configurationPage ? ' class="active"' : ''); ?>>
                    <a href="index.php?action=config">
                        <i aria-hidden="true" class="fa fa-wrench fa-fw"></i> <?php echo $PMF_LANG['admin_mainmenu_configuration']; ?>
                        <span class="fa arrow"></span>
                    </a>
                    <ul class="nav nav-second-level collapse <?php echo($configurationPage ? 'in' : '') ?>">
                        <?php echo $secLevelEntries; ?>
                    </ul>
                </li>

                <li class="sidebar-adminlog">
                    <div>
                        <b class="fa fa-info-circle fa-fw"></b> Admin worklog<br>
                        <span id="saving_data_indicator"></span>
                    </div>
                </li>
                <li class="sidebar-sessioninfo">
                    <div>
                        <b class="fa fa-clock-o fa-fw"></b> <?php echo $PMF_LANG['ad_session_expiration']; ?>:
                        <span id="sessioncounter"><i aria-hidden="true" class="fa fa-spinner fa-spin"></i> Loading...</span>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
    <?php endif; ?>



    <div id="page-wrapper">
