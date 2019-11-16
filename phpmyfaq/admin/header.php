<?php
/**
 * Header of the admin area.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2003-02-26
 */

use phpMyFAQ\Helper\Administration;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Services\Gravatar;
use phpMyFAQ\Template;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$httpHeader = new HttpHelper();
$httpHeader->setContentType('text/html');
$httpHeader->addHeader();

$secLevelEntries = [
    'user' => '',
    'content' => '',
    'statistics' => '',
    'exports' => '',
    'backup' => '',
    'config' => '',
];
$dashboardPage = true;
$contentPage = false;
$userPage = false;
$statisticsPage = false;
$exportsPage = false;
$backupPage = false;
$configurationPage = false;
$edAutoSave = (('editentry' === $action) && $faqConfig->get('records.autosaveActive'));

$adminHelper = new Administration();
$adminHelper->setUser($user);

$secLevelEntries['user'] = $adminHelper->addMenuEntry('add_user+edit_user+delete_user', 'user',
    'ad_menu_user_administration', $action);
if ($faqConfig->get('security.permLevel') !== 'basic') {
    $secLevelEntries['user'] .= $adminHelper->addMenuEntry('addgroup+editgroup+delgroup', 'group',
        'ad_menu_group_administration', $action);
}
if ($faqConfig->get('security.permLevel') === 'large') {
    $secLevelEntries['user'] .= $adminHelper->addMenuEntry('add_section+edit_section+del_section', 'section',
        'ad_menu_section_administration', $action);
}
$secLevelEntries['content'] = $adminHelper->addMenuEntry('addcateg+editcateg+delcateg', 'category',
    'ad_menu_categ_edit', $action);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry('add_faq', 'editentry', 'ad_entry_add', $action);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry('edit_faq+delete_faq', 'view', 'ad_menu_entry_edit', $action);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry('edit_faq+delete_faq', 'searchfaqs', 'ad_menu_searchfaqs',
    $action);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry('delcomment', 'comments', 'ad_menu_comments', $action);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry('delquestion', 'question', 'ad_menu_open', $action);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry('addglossary+editglossary+delglossary', 'glossary',
    'ad_menu_glossary', $action);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry('addnews+editnews+delnews', 'news', 'ad_menu_news_edit',
    $action);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry('addattachment+editattachment+delattachment', 'attachments',
    'ad_menu_attachments', $action);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry('edit_faq', 'tags', 'ad_entry_tags', $action);

$secLevelEntries['statistics'] = $adminHelper->addMenuEntry('viewlog', 'statistics', 'ad_menu_stat', $action);
$secLevelEntries['statistics'] .= $adminHelper->addMenuEntry('viewlog', 'viewsessions', 'ad_menu_session', $action);
$secLevelEntries['statistics'] .= $adminHelper->addMenuEntry('adminlog', 'adminlog', 'ad_menu_adminlog', $action);
$secLevelEntries['statistics'] .= $adminHelper->addMenuEntry('viewlog', 'searchstats', 'ad_menu_searchstats', $action);
$secLevelEntries['statistics'] .= $adminHelper->addMenuEntry('reports', 'reports', 'ad_menu_reports', $action);

$secLevelEntries['exports'] = $adminHelper->addMenuEntry('editconfig', 'export', 'ad_menu_export', $action);

$secLevelEntries['backup'] = $adminHelper->addMenuEntry('editconfig', 'backup', 'ad_menu_backup', $action);

$secLevelEntries['config'] .= $adminHelper->addMenuEntry('editconfig', 'config', 'ad_menu_editconfig', $action);
$secLevelEntries['config'] .= $adminHelper->addMenuEntry('editconfig', 'system', 'ad_system_info', $action, false);
$secLevelEntries['config'] .= $adminHelper->addMenuEntry('editinstances+addinstances+delinstances', 'instances',
    'ad_menu_instances', $action);
$secLevelEntries['config'] .= $adminHelper->addMenuEntry('editconfig', 'stopwordsconfig', 'ad_menu_stopwordsconfig',
    $action);
$secLevelEntries['config'] .= $adminHelper->addMenuEntry('editconfig', 'meta', 'ad_menu_meta', $action);
if ($faqConfig->get('search.enableElasticsearch')) {
    $secLevelEntries['config'] .= $adminHelper->addMenuEntry(
        'editconfig',
        'elasticsearch',
        'ad_menu_elasticsearch',
        $action
    );
}

switch ($action) {
    case 'user':
    case 'group':
    case 'section':
    case 'passwd':
    case 'cookies':
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
    case 'add-news':
    case 'edit-news':
    case 'save-news':
    case 'update-news':
    case 'delete-news':
    case 'question':
    case 'takequestion':
    case 'comments':
    case 'attachments':
    case 'tags':
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
        $statisticsPage = true;
        break;
    case 'export':
        $exportsPage = true;
        break;
    case 'backup':
        $backupPage = true;
        break;
    case 'config':
    case 'stopwordsconfig':
    case 'upgrade':
    case 'instances':
    case 'system':
    case 'elasticsearch':
    case 'meta':
        $configurationPage = true;
        break;
    default:
        $dashboardPage = true;
        break;
}
?>
<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

  <title><?= $faqConfig->get('main.titleFAQ'); ?> - powered by
    phpMyFAQ <?= $faqConfig->get('main.currentVersion'); ?></title>
  <base href="<?= $faqSystem->getSystemUri($faqConfig) ?>admin/">

  <meta name="description" content="Only Chuck Norris can divide by zero.">
  <meta name="author" content="phpMyFAQ Team">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="application-name" content="phpMyFAQ <?= $faqConfig->get('main.currentVersion'); ?>">
  <meta name="copyright" content="(c) 2001-<?= date('Y') ?> phpMyFAQ Team">
  <meta name="publisher" content="phpMyFAQ Team">
  <meta name="robots" content="<?= $faqConfig->get('seo.metaTagsAdmin') ?>">

  <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet" type="text/css">
  <link rel="stylesheet" href="assets/css/style.css?v=1">

  <script src="../assets/themes/default/js/vendors.min.js"></script>
  <script src="../assets/themes/default/js/phpmyfaq.min.js"></script>
  <script src="assets/js/sidebar.js"></script>
  <script src="assets/js/editor/tinymce.min.js?<?= time(); ?>"></script>

    <?php if ($edAutoSave): ?>
      <script>let pmfAutosaveInterval = <?= $faqConfig->get('records.autosaveSecs') ?>;</script>
      <script src="../assets/js/autosave.js" async></script>
    <?php endif; ?>

  <link rel="shortcut icon" href="../assets/themes/<?= Template::getTplSetName(); ?>/img/favicon.ico">
</head>
<body dir="<?= $PMF_LANG['dir']; ?>" id="page-top">

<!-- Page Wrapper -->
<div id="wrapper">

  <!-- Sidebar -->
    <?php if (isset($auth) && (count($user->perm->getAllUserRights($user->getUserId())) > 0 || $user->isSuperAdmin())): ?>
      <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

        <li>
          <a class="sidebar-brand d-flex align-items-center justify-content-center" href="../">
            <div class="sidebar-brand-icon rotate-n-15">
              <i class="fas fa-laugh-wink"></i>
            </div>
            <div class="sidebar-brand-text mx-4">phpMyFAQ <?= $faqConfig->get('main.currentVersion') ?></div>
          </a>
        </li>

        <li>
          <hr class="sidebar-divider my-0">
        </li>

        <li class="nav-item active">
          <a class="nav-link" href="index.php">
            <i class="fa fa-tachometer"></i>
            <span>Dashboard</span></a>
        </li>

        <li>
          <hr class="sidebar-divider">
        </li>

        <li class="nav-item">
          <a class="nav-link <?= ($userPage) ? '' : 'collapsed' ?>"
             href="#" data-toggle="collapse" data-target="#collapseUserAdmin" aria-expanded="true"
             aria-controls="collapseUserAdmin">
            <i aria-hidden="true" class="fa fa-user"></i>
            <span><?= $PMF_LANG['admin_mainmenu_users']; ?></span>
          </a>
          <div id="collapseUserAdmin" class="collapse <?= ($userPage) ? 'show' : '' ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <?= $secLevelEntries['user']; ?>
            </div>
          </div>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= ($contentPage) ? '' : 'collapsed' ?>"
             href="#" data-toggle="collapse" data-target="#collapseContentAdmin" aria-expanded="true"
             aria-controls="collapseContentAdmin">
            <i aria-hidden="true" class="fa fa-edit"></i>
            <span><?= $PMF_LANG['admin_mainmenu_content']; ?></span>
          </a>
          <div id="collapseContentAdmin" class="collapse <?= ($contentPage) ? 'show' : '' ?>"
               data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <?= $secLevelEntries['content']; ?>
            </div>
          </div>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= ($statisticsPage) ? '' : 'collapsed' ?>"
             href="#" data-toggle="collapse" data-target="#collapseStatisticsAdmin" aria-expanded="true"
             aria-controls="collapseStatisticsAdmin">
            <i aria-hidden="true" class="fa fa-tasks"></i>
            <span><?= $PMF_LANG['admin_mainmenu_statistics']; ?></span>
          </a>
          <div id="collapseStatisticsAdmin" class="collapse <?= ($statisticsPage) ? 'show' : '' ?>"
               data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <?= $secLevelEntries['statistics']; ?>
            </div>
          </div>
        </li>

        <li class="nav-item">
          <a class="nav-link  <?= ($exportsPage) ? '' : 'collapsed' ?>" href="#" data-toggle="collapse"
             data-target="#collapseExportsAdmin" aria-expanded="true"
             aria-controls="collapseExportsAdmin">
            <i aria-hidden="true" class="fa fa-file"></i>
            <span><?= $PMF_LANG['admin_mainmenu_exports']; ?></span>
          </a>
          <div id="collapseExportsAdmin" class="collapse <?= ($exportsPage) ? 'show' : '' ?>"
               data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <?= $secLevelEntries['exports']; ?>
            </div>
          </div>
        </li>

        <li class="nav-item">
          <a class="nav-link  <?= ($backupPage) ? '' : 'collapsed' ?>" href="#" data-toggle="collapse"
             data-target="#collapseBackupAdmin" aria-expanded="true"
             aria-controls="collapseBackupAdmin">
            <i aria-hidden="true" class="fa fa-download"></i>
            <span><?= $PMF_LANG['admin_mainmenu_backup']; ?></span>
          </a>
          <div id="collapseBackupAdmin" class="collapse <?= ($backupPage) ? 'show' : '' ?>"
               data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <?= $secLevelEntries['backup']; ?>
            </div>
          </div>
        </li>

        <li class="nav-item">
          <a class="nav-link  <?= ($configurationPage) ? '' : 'collapsed' ?>" href="#" data-toggle="collapse"
             data-target="#collapseConfigAdmin" aria-expanded="true"
             aria-controls="collapseConfigAdmin">
            <i aria-hidden="true" class="fa fa-wrench"></i>
            <span><?= $PMF_LANG['admin_mainmenu_configuration']; ?></span>
          </a>
          <div id="collapseConfigAdmin" class="collapse <?= ($configurationPage) ? 'show' : '' ?>"
               data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <?= $secLevelEntries['config']; ?>
            </div>
          </div>
        </li>

        <li>
          <hr class="sidebar-divider d-none d-md-block">
        </li>

        <!-- Sidebar Toggler (Sidebar) -->
        <li>
          <div class="text-center d-none d-md-inline">
            <button class="rounded-circle border-0" id="sidebarToggle"></button>
          </div>
        </li>

      </ul>
      <!-- End of Sidebar -->
    <?php endif; ?>


  <!-- Content Wrapper -->
  <div id="content-wrapper" class="d-flex flex-column">

    <!-- Main Content -->
    <div id="content">

      <!-- Topbar -->
      <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

        <!-- Sidebar Toggle (Topbar) -->
        <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
          <i class="fa fa-bars"></i>
        </button>

        <!-- Topbar Search -->
        <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search"
              action="index.php<?= (isset($action) ? '?action=' . $action : ''); ?>" method="post">
            <?= Language::selectLanguages($LANGCODE, true); ?>
        </form>

        <!-- Topbar Navbar -->
        <ul class="navbar-nav ml-auto">

          <!-- Nav Item - Search Dropdown (Visible Only XS) -->
          <li class="nav-item dropdown no-arrow d-sm-none">
            <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button" data-toggle="dropdown"
               aria-haspopup="true" aria-expanded="false">
              <i class="fas fa-search fa-fw"></i>
            </a>
            <!-- Dropdown - Messages -->
            <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in"
                 aria-labelledby="searchDropdown">
              <form class="form-inline mr-auto w-100 navbar-search">
                <div class="input-group">
                  <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..."
                         aria-label="Search" aria-describedby="basic-addon2">
                  <div class="input-group-append">
                    <button class="btn btn-primary" type="button">
                      <i class="fas fa-search fa-sm"></i>
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </li>

          <div class="topbar-divider d-none d-sm-block"></div>

            <?php if (isset($auth) && count($user->perm->getAllUserRights($user->getUserId())) > 0): ?>
              <li class="nav-item dropdown no-arrow">
                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown"
                   aria-haspopup="true" aria-expanded="false">
                  <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                      <?= $user->getUserData('display_name'); ?>
                  </span>
                    <?php
                    if ($faqConfig->get('main.enableGravatarSupport')) {
                        $avatar = new Gravatar($faqConfig);
                        echo $avatar->getImage($user->getUserData('email'),
                            ['size' => 24, 'class' => 'img-profile rounded-circle']);
                    } else {
                        echo '<i aria-hidden="true" class="fa fa-user"></i>';
                    }
                    ?>
                </a>
                <!-- Dropdown - User Information -->
                <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                  <a class="dropdown-item" href="index.php?action=passwd">
                    <i class="fa fa-key-modern mr-2 text-gray-400"></i>
                      <?= $PMF_LANG['ad_menu_passwd'] ?>
                  </a>
                  <div class="dropdown-divider"></div>
                  <a class="dropdown-item" href="index.php?action=logout">
                    <i class="fa fa-sign-out mr-2 text-gray-400"></i>
                      <?= $PMF_LANG['admin_mainmenu_logout']; ?>
                  </a>
                </div>
              </li>
            <?php endif; ?>

        </ul>

      </nav>
      <!-- End of Topbar -->

      <!-- Begin Page Content -->
      <div class="container-fluid">


        <!--
<header>
  <nav class="navbar navbar-expand-sm navbar-dark fixed-top bg-dark flex-md-nowrap p-0 shadow">
    <a class="navbar-brand col-sm-3 col-md-2 mr-0" title="<?= $faqConfig->get('main.titleFAQ') ?>" href="../index.php">
      phpMyFAQ <?= $faqConfig->get('main.currentVersion') ?>
    </a>
    <?php if (isset($auth) && count($user->perm->getAllUserRights($user->getUserId())) > 0): ?>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#pmf-admin-navbar" aria-controls="pmf-admin-navbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <?php endif; ?>

    <div class="collapse navbar-collapse" id="pmf-admin-navbar">
      <?php if (isset($auth) && count($user->perm->getAllUserRights($user->getUserId())) > 0): ?>
      <ul class="navbar-nav mr-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="pmf-admin-dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <?php
            if ($faqConfig->get('main.enableGravatarSupport')) {
                $avatar = new Gravatar($faqConfig);
                echo $avatar->getImage($user->getUserData('email'), ['size' => 24, 'class' => 'rounded-circle']);
            } else {
                echo '<i aria-hidden="true" class="fa fa-user"></i>';
            }
            ?>
            <span title="<?= $PMF_LANG['ad_user_loggedin'] . $user->getLogin(); ?>">
              <?= $user->getUserData('display_name'); ?>
            </span>
          </a>
          <div class="dropdown-menu" aria-labelledby="pmf-admin-dropdown">
            <a class="dropdown-item" href="index.php?action=passwd">
              <i aria-hidden="true" class="fa fa-key"></i>
                <?= $PMF_LANG['ad_menu_passwd'] ?>
            </a>
            <a class="dropdown-item" href="index.php?action=logout">
              <i aria-hidden="true" class="fa fa-sign-out-alt"></i>
                <?= $PMF_LANG['admin_mainmenu_logout']; ?>
            </a>
          </div>
        </li>
      </ul>
      <?php endif; ?>
      <form class="form-inline mr-1" action="index.php<?= (isset($action) ? '?action=' . $action : ''); ?>" method="post">
          <?= Language::selectLanguages($LANGCODE, true); ?>
      </form>
    </div>
  </nav>
</header>

<div class="container-fluid">
  <div class="row">
      <?php if (isset($auth) && (count($user->perm->getAllUserRights($user->getUserId())) > 0 || $user->isSuperAdmin())): ?>
        <nav class="col-md-2 d-none d-md-block bg-dark sidebar">
          <div class="sidebar-sticky">
            <ul class="nav flex-column">
              <li class="nav-item <?= $dashboardPage ? 'active' : ''; ?>">
                <a class="nav-link" href="index.php">
                  <i aria-hidden="true" class="fa fa-tachometer"></i> <?= $PMF_LANG['admin_mainmenu_home']; ?>
                </a>
              </li>

              <li class="nav-item <?= $userPage ? 'active' : ''; ?>">
                <a class="nav-link" href="index.php?action=user">
                  <i aria-hidden="true" class="fa fa-user"></i> <?= $PMF_LANG['admin_mainmenu_users']; ?>
                  <span class="fa fa-caret-down"></span>
                </a>
                  <?php if ($userPage) { ?>
                    <ul class="navbar-nav navbar-dark ml-5 <?= ($userPage ? 'in' : '') ?>" id="user-menu">
                        <?= $secLevelEntries; ?>
                    </ul>
                  <?php } ?>
              </li>

              <li class="nav-item <?= $contentPage ? 'active' : ''; ?>">
                <a class="nav-link" href="index.php?action=content">
                  <i aria-hidden="true" class="fa fa-edit"></i> <?= $PMF_LANG['admin_mainmenu_content']; ?>
                  <span class="fa fa-caret-down"></span>
                </a>
                  <?php if ($contentPage) { ?>
                    <ul class="navbar-nav navbar-dark ml-5 <?= ($contentPage ? 'in' : '') ?>">
                        <?= $secLevelEntries; ?>
                    </ul>
                  <?php } ?>
              </li>

              <li class="nav-item <?= $statisticsPage ? 'active' : ''; ?>">
                <a class="nav-link" href="index.php?action=statistics">
                  <i aria-hidden="true" class="fa fa-tasks"></i> <?= $PMF_LANG['admin_mainmenu_statistics']; ?>
                  <span class="fa fa-caret-down"></span>
                </a>
                  <?php if ($statisticsPage) { ?>
                    <ul class="navbar-nav navbar-dark ml-5 <?= ($statisticsPage ? 'in' : '') ?>">
                        <?= $secLevelEntries; ?>
                    </ul>
                  <?php } ?>
              </li>

              <li class="nav-item <?= $exportsPage ? 'active' : ''; ?>">
                <a class="nav-link" href="index.php?action=export">
                  <i aria-hidden="true" class="fa fa-book"></i> <?= $PMF_LANG['admin_mainmenu_exports']; ?>
                </a>
              </li>

              <li class="nav-item <?= $backupPage ? 'active' : ''; ?>">
                <a class="nav-link" href="index.php?action=backup">
                  <i aria-hidden="true" class="fa fa-download"></i> <?= $PMF_LANG['admin_mainmenu_backup']; ?>
                </a>
                  <?php if ($backupPage) { ?>
                    <ul class="navbar-nav navbar-dark ml-5 <?= $backupPage ? 'in' : '' ?>">
                        <?= $secLevelEntries; ?>
                    </ul>
                  <?php } ?>
              </li>

              <li class="nav-item <?= $configurationPage ? 'active' : ''; ?>">
                <a class="nav-link" href="index.php?action=config">
                  <i aria-hidden="true" class="fa fa-wrench"></i> <?= $PMF_LANG['admin_mainmenu_configuration']; ?>
                  <span class="fa fa-caret-down"></span>
                </a>
                  <?php if ($configurationPage) { ?>
                    <ul class="navbar-nav navbar-dark ml-5 <?= $configurationPage ? 'in' : '' ?>">
                        <?= $secLevelEntries; ?>
                    </ul>
                  <?php } ?>
              </li>
            </ul>

            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
              <span>Admin worklog</span>
            </h6>
            <ul class="nav flex-column mb-2">
              <li class="nav-item">
                <a class="nav-link" href="#">
                  <span id="saving_data_indicator"></span>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="#">
                  <i aria-hidden="true" class="fa fa-user-clock"></i>
                  <?= $PMF_LANG['ad_session_expiration']; ?>:
                  <span id="sessioncounter">Loading...</span>
                </a>
              </li>
            </ul>
          </div>
        </nav>
      <?php endif; ?>

    <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
    -->
