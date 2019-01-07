<?php
/**
 * Header of the admin area.
 *
 *
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-26
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
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$httpHeader = new HttpHelper();
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

$adminHelper = new Administration();
$adminHelper->setUser($user);

switch ($action) {
    case 'user':
    case 'group':
    case 'section':
    case 'passwd':
    case 'cookies':
        $secLevelHeader = $PMF_LANG['admin_mainmenu_users'];
        $secLevelEntries .= $adminHelper->addMenuEntry('add_user+edit_user+delete_user', 'user', 'ad_menu_user_administration', $action);
        if ($faqConfig->get('security.permLevel') !== 'basic') {
            $secLevelEntries .= $adminHelper->addMenuEntry('addgroup+editgroup+delgroup', 'group', 'ad_menu_group_administration', $action);
        }
        if ($faqConfig->get('security.permLevel') == 'large') {
           $secLevelEntries .= $adminHelper->addMenuEntry('add_section+edit_section+del_section', 'section', 'ad_menu_section_administration', $action);
        }
        if (!$faqConfig->get('ldap.ldapSupport')) {
            $secLevelEntries .= $adminHelper->addMenuEntry('passwd', 'passwd', 'ad_menu_passwd', $action);
        }
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
        $secLevelEntries .= $adminHelper->addMenuEntry('add_faq', 'editentry', 'ad_entry_add', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('edit_faq+delete_faq', 'view', 'ad_menu_entry_edit', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('edit_faq+delete_faq', 'searchfaqs', 'ad_menu_searchfaqs', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('delcomment', 'comments', 'ad_menu_comments', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('delquestion', 'question', 'ad_menu_open', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('addglossary+editglossary+delglossary', 'glossary', 'ad_menu_glossary', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('addnews+editnews+delnews', 'news', 'ad_menu_news_edit', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('addattachment+editattachment+delattachment', 'attachments', 'ad_menu_attachments', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('edit_faq', 'tags', 'ad_entry_tags', $action);
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
    case 'upgrade':
    case 'instances':
    case 'system':
    case 'elasticsearch':
    case 'meta':
        $secLevelHeader = $PMF_LANG['admin_mainmenu_configuration'];
        $secLevelEntries .= $adminHelper->addMenuEntry('editconfig', 'config', 'ad_menu_editconfig', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('', 'system', 'ad_system_info', $action, false);
        $secLevelEntries .= $adminHelper->addMenuEntry('editinstances+addinstances+delinstances', 'instances', 'ad_menu_instances', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('editconfig', 'stopwordsconfig', 'ad_menu_stopwordsconfig', $action);
        $secLevelEntries .= $adminHelper->addMenuEntry('editconfig', 'meta', 'ad_menu_meta', $action);
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
        $secLevelEntries .= $adminHelper->addMenuEntry('add_faq', 'editentry', 'ad_quick_record');
        $secLevelEntries .= $adminHelper->addMenuEntry('edit_faq+delete_faq', 'view', 'ad_menu_entry_edit');
        $secLevelEntries .= $adminHelper->addMenuEntry('delquestion', 'question', 'ad_menu_open');
        $secLevelEntries .= $adminHelper->addMenuEntry('', 'system', 'ad_system_info', $action, false);
        $dashboardPage = true;
        break;
}
?>
<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

  <title><?= $faqConfig->get('main.titleFAQ'); ?> - powered by phpMyFAQ <?= $faqConfig->get('main.currentVersion'); ?></title>
  <base href="<?= $faqSystem->getSystemUri($faqConfig) ?>admin/">

  <meta name="description" content="Only Chuck Norris can divide by zero.">
  <meta name="author" content="phpMyFAQ Team">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="application-name" content="phpMyFAQ <?= $faqConfig->get('main.currentVersion'); ?>">
  <meta name="copyright" content="(c) 2001-<?= date('Y') ?> phpMyFAQ Team">
  <meta name="publisher" content="phpMyFAQ Team">
  <meta name="robots" content="<?= $faqConfig->get('seo.metaTagsAdmin') ?>">

  <link href="http://fonts.googleapis.com/css?family=Roboto" rel="stylesheet" type="text/css">
  <link rel="stylesheet" href="assets/css/style.css?v=1">

  <script src="../assets/themes/default/js/vendors.min.js"></script>
  <script src="../assets/themes/default/js/phpmyfaq.min.js"></script>
  <script src="assets/js/sidebar.js"></script>
  <script src="assets/js/editor/tinymce.min.js?<?= time(); ?>"></script>

  <?php if ($edAutosave): ?>
  <script>let pmfAutosaveInterval = <?= $faqConfig->get('records.autosaveSecs') ?>;</script>
  <script src="../assets/js/autosave.js" async></script>
  <?php endif; ?>

  <link rel="shortcut icon" href="../assets/themes/<?= Template::getTplSetName(); ?>/img/favicon.ico">
</head>
<body dir="<?= $PMF_LANG['dir']; ?>">

<header>
  <nav class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap p-0 shadow">
    <a class="navbar-brand col-sm-3 col-md-2 mr-0" title="<?= $faqConfig->get('main.titleFAQ') ?>" href="../index.php">
      phpMyFAQ <?= $faqConfig->get('main.currentVersion') ?>
    </a>
    <?php if (isset($auth) && count($user->perm->getAllUserRights($user->getUserId())) > 0): ?>
    <!--
    <form class="form-control form-control-dark w-50" action="index.php<?= (isset($action) ? '?action='.$action : ''); ?>" method="post">
        <?= Language::selectLanguages($LANGCODE, true); ?>
    </form>
    -->
    <div class="navbar-text">
      <?php
      if ($faqConfig->get('main.enableGravatarSupport')) {
          $avatar = new Gravatar($faqConfig);
          echo $avatar->getImage($user->getUserData('email'), ['size' => 24, 'class' => 'rounded-circle']);
      } else {
          echo '<i aria-hidden="true" class="fas fa-user"></i>';
      }
      ?>
      <span title="<?= $PMF_LANG['ad_user_loggedin'].$user->getLogin(); ?>">
        <?= $user->getUserData('display_name'); ?>
      </span>
    </div>
    <ul class="navbar-nav px-3">
      <li class="nav-item">
        <a class="nav-link" href="index.php?action=logout">
          <i aria-hidden="true" class="fas fa-sign-out-alt"></i>
          <?= $PMF_LANG['admin_mainmenu_logout']; ?>
        </a>
      </li>
    </ul>
    <?php endif; ?>
  </nav>
</header>

<div class="container-fluid">
  <div class="row">
      <?php if (isset($auth) && (count($user->perm->getAllUserRights($user->getUserId())) > 0 || $user->isSuperAdmin())): ?>
        <nav class="col-md-2 d-none d-md-block bg-light sidebar">
          <div class="sidebar-sticky">
            <ul class="nav flex-column">
              <li class="nav-item <?= $dashboardPage ? 'active' : ''; ?>">
                <a class="nav-link" href="index.php">
                  <i aria-hidden="true" class="fas fa-tachometer-alt"></i> <?= $PMF_LANG['admin_mainmenu_home']; ?>
                </a>
              </li>

              <li class="nav-item <?= $userPage ? 'active' : ''; ?>">
                <a class="nav-link" href="index.php?action=user">
                  <i aria-hidden="true" class="fas fa-user"></i> <?= $PMF_LANG['admin_mainmenu_users']; ?>
                  <span class="fas arrow"></span>
                </a>
                  <?php if ($userPage) { ?>
                    <ul class="navbar-nav navbar-dark ml-5 <?= ($userPage ? 'in' : '') ?>" id="user-menu">
                        <?= $secLevelEntries; ?>
                    </ul>
                  <?php } ?>
              </li>

              <li class="nav-item <?= $contentPage ? 'active' : ''; ?>">
                <a class="nav-link" href="index.php?action=content">
                  <i aria-hidden="true" class="fas fa-edit"></i> <?= $PMF_LANG['admin_mainmenu_content']; ?>
                  <span class="fas arrow"></span>
                </a>
                  <?php if ($contentPage) { ?>
                    <ul class="navbar-nav navbar-dark ml-5 <?= ($contentPage ? 'in' : '') ?>">
                        <?= $secLevelEntries; ?>
                    </ul>
                  <?php } ?>
              </li>

              <li class="nav-item <?= $statisticsPage ? 'active' : ''; ?>">
                <a class="nav-link" href="index.php?action=statistics">
                  <i aria-hidden="true" class="fas fa-chart-line"></i> <?= $PMF_LANG['admin_mainmenu_statistics']; ?>
                  <span class="fas arrow"></span>
                </a>
                  <?php if ($statisticsPage) { ?>
                    <ul class="navbar-nav navbar-dark ml-5 <?= ($statisticsPage ? 'in' : '') ?>">
                        <?= $secLevelEntries; ?>
                    </ul>
                  <?php } ?>
              </li>

              <li class="nav-item <?= $exportsPage ? 'active' : ''; ?>">
                <a class="nav-link" href="index.php?action=export">
                  <i aria-hidden="true" class="fas fa-file-export"></i> <?= $PMF_LANG['admin_mainmenu_exports']; ?>
                </a>
              </li>

              <li class="nav-item <?= $backupPage ? 'active' : ''; ?>">
                <a class="nav-link" href="index.php?action=backup">
                  <i aria-hidden="true" class="fas fa-download"></i> <?= $PMF_LANG['admin_mainmenu_backup']; ?>
                </a>
                  <?php if ($backupPage) { ?>
                    <ul class="navbar-nav navbar-dark ml-5 <?= $backupPage ? 'in' : '' ?>">
                        <?= $secLevelEntries; ?>
                    </ul>
                  <?php } ?>
              </li>

              <li class="nav-item <?= $configurationPage ? 'active' : ''; ?>">
                <a class="nav-link" href="index.php?action=config">
                  <i aria-hidden="true" class="fas fa-wrench"></i> <?= $PMF_LANG['admin_mainmenu_configuration']; ?>
                  <span class="fas arrow"></span>
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
                  <i aria-hidden="true" class="fas fa-user-clock"></i>
                  <?= $PMF_LANG['ad_session_expiration']; ?>:
                  <span id="sessioncounter">Loading...</span>
                </a>
              </li>
            </ul>
          </div>
        </nav>
      <?php endif; ?>

    <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
