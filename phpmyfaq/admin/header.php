<?php

/**
 * Header of the admin area.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-26
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Helper\AdministrationHelper;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\Services\Gravatar;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Template;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$dashboardPage = true;
$contentPage = false;
$userPage = false;
$statisticsPage = false;
$exportsPage = false;
$backupPage = false;
$configurationPage = false;

$faqConfig = Configuration::getConfigurationInstance();

$adminHelper = new AdministrationHelper();
$adminHelper->setUser($user);

$secLevelEntries['user'] = $adminHelper->addMenuEntry(
    'add_user+edit_user+delete_user',
    'user',
    'ad_menu_user_administration',
    $action
);
if ($faqConfig->get('security.permLevel') !== 'basic') {
    $secLevelEntries['user'] .= $adminHelper->addMenuEntry(
        'addgroup+editgroup+delgroup',
        'group',
        'ad_menu_group_administration',
        $action
    );
}
$secLevelEntries['content'] = $adminHelper->addMenuEntry(
    'addcateg+editcateg+delcateg',
    'category-overview',
    'ad_menu_categ_edit',
    $action
);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry(
    PermissionType::FAQ_ADD->value,
    'editentry',
    'ad_entry_add',
    $action
);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry(
    'edit_faq+delete_faq',
    'faqs-overview',
    'ad_menu_entry_edit',
    $action
);

$secLevelEntries['content'] .= $adminHelper->addMenuEntry(
    PermissionType::FAQ_EDIT->value,
    'stickyfaqs',
    'stickyRecordsHeader',
    $action
);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry('delcomment', 'comments', 'ad_menu_comments', $action);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry('delquestion', 'question', 'ad_menu_open', $action);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry(
    'addglossary+editglossary+delglossary',
    'glossary',
    'ad_menu_glossary',
    $action
);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry(
    'addnews+editnews+delnews',
    'news',
    'ad_menu_news_edit',
    $action
);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry(
    'addattachment+editattachment+delattachment',
    'attachments',
    'ad_menu_attachments',
    $action
);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry(
    PermissionType::FAQ_EDIT->value,
    'tags',
    'ad_entry_tags',
    $action
);

$secLevelEntries['statistics'] = $adminHelper->addMenuEntry(
    PermissionType::STATISTICS_VIEWLOGS->value,
    'statistics',
    'ad_menu_stat',
    $action
);
$secLevelEntries['statistics'] .= $adminHelper->addMenuEntry(
    PermissionType::STATISTICS_VIEWLOGS->value,
    'viewsessions',
    'ad_menu_session',
    $action
);
$secLevelEntries['statistics'] .= $adminHelper->addMenuEntry(
    PermissionType::STATISTICS_ADMINLOG->value,
    'adminlog',
    'ad_menu_adminlog',
    $action
);
$secLevelEntries['statistics'] .= $adminHelper->addMenuEntry(
    PermissionType::STATISTICS_VIEWLOGS->value,
    'searchstats',
    'ad_menu_searchstats',
    $action
);
$secLevelEntries['statistics'] .= $adminHelper->addMenuEntry('reports', 'reports', 'ad_menu_reports', $action);

$secLevelEntries['imports_exports'] = $adminHelper->addMenuEntry(
    PermissionType::FAQ_ADD->value,
    'importcsv',
    'msgImportRecords',
    $action
);
$secLevelEntries['imports_exports'] .= $adminHelper->addMenuEntry('export', 'export', 'ad_menu_export', $action);

$secLevelEntries['backup'] = $adminHelper->addMenuEntry('editconfig', 'backup', 'ad_menu_backup', $action);

$secLevelEntries['config'] = $adminHelper->addMenuEntry('editconfig', 'config', 'ad_menu_editconfig', $action);
$secLevelEntries['config'] .= $adminHelper->addMenuEntry('forms_edit', 'forms', 'msgEditForms', $action);
$secLevelEntries['config'] .= $adminHelper->addMenuEntry(
    'editinstances+addinstances+delinstances',
    'instances',
    'ad_menu_instances',
    $action
);
$secLevelEntries['config'] .= $adminHelper->addMenuEntry(
    'editconfig',
    'stopwordsconfig',
    'ad_menu_stopwordsconfig',
    $action
);
$secLevelEntries['config'] .= $adminHelper->addMenuEntry('editconfig', 'upgrade', 'ad_menu_upgrade', $action);
if ($faqConfig->get('search.enableElasticsearch')) {
    $secLevelEntries['config'] .= $adminHelper->addMenuEntry(
        'editconfig',
        'elasticsearch',
        'ad_menu_elasticsearch',
        $action
    );
}
$secLevelEntries['config'] .= $adminHelper->addMenuEntry('editconfig', 'system', 'ad_system_info', $action);

switch ($action) {
    case 'user':
    case 'group':
    case 'passwd':
    case 'cookies':
        $userPage = true;
        break;
    case 'category-overview':
    case 'addcategory':
    case 'savecategory':
    case 'editcategory':
    case 'translatecategory':
    case 'updatecategory':
    case 'showcategory':
    case 'faqs-overview':
    case 'editentry':
    case 'insertentry':
    case 'saveentry':
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
    case 'delete-tag':
    case 'stickyfaqs':
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
    case 'importcsv':
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
        $configurationPage = true;
        break;
    default:
        $dashboardPage = true;
        break;
}
?>
<!DOCTYPE html>
<html lang="<?= Translation::get('metaLanguage'); ?>" data-bs-theme="light">
<head>
  <meta charset="utf-8">

  <title>
    <?= Strings::htmlentities($faqConfig->getTitle()) ?> - <?= System::getPoweredByString() ?>
  </title>
  <base href="<?= Strings::htmlentities($faqConfig->getDefaultUrl()) ?>admin/">

  <meta name="description" content="Only Chuck Norris can divide by zero.">
  <meta name="author" content="phpMyFAQ Team">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="application-name" content="phpMyFAQ <?= System::getVersion() ?>">
  <meta name="copyright" content="© 2001-<?= date('Y') ?> phpMyFAQ Team">
  <meta name="publisher" content="phpMyFAQ Team">
  <meta name="robots" content="<?= $faqConfig->get('seo.metaTagsAdmin') ?>">

  <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet" type="text/css">
  <link rel="stylesheet" href="../assets/dist/admin.css">

  <script src="assets/js/configuration.js"></script>
  <link rel="shortcut icon" href="../assets/themes/<?= Template::getTplSetName(); ?>/img/favicon.ico">
</head>
<body dir="<?= Translation::get('dir'); ?>" id="page-top">

<!-- phpMyFAQ Admin Top Bar -->
<nav class="pmf-admin-topnav navbar navbar-expand bg-dark">
    <a class="navbar-brand text-white text-center ps-3" href="../" title="phpMyFAQ <?= System::getVersion() ?>">
        <img height="50" src="../assets/img/logo-transparent.svg" alt="phpMyFAQ Logo">
    </a>

    <?php if ($adminHelper->canAccessContent($user)) : ?>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" role="button"
            name="sidebar-toggle" href="#">
        <i class="bi bi-list h6"></i>
    </button>

    <ul class="nav col-12 col-lg-auto me-lg-auto mb-2 justify-content-center mb-md-0">
        <li>
            <div class="text-white small">
                <i class="bi bi-clock-o bi-fw"></i> <?= Translation::get('ad_session_expiration'); ?>:
                <span id="sessioncounter" class="pl-2">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </span>
            </div>
        </li>
    </ul>
    <?php endif; ?>

    <!-- Language Switcher -->
    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0 navbar-search"
          action="index.php<?= (isset($action) ? '?action=' . $action : ''); ?>" method="post">
        <?= LanguageHelper::renderSelectLanguage($faqLangCode, true); ?>
    </form>

    <?php if ($adminHelper->canAccessContent($user)) : ?>
    <!-- Navbar-->
    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown"
               aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline small">
                      <?= $user->getUserData('display_name'); ?>
                </span>
                <?php
                if ($faqConfig->get('main.enableGravatarSupport')) {
                    $avatar = new Gravatar();
                    echo $avatar->getImage(
                        $user->getUserData('email'),
                        ['size' => 24, 'class' => 'img-profile rounded-circle']
                    );
                } else {
                    echo '<i aria-hidden="true" class="bi bi-user"></i>';
                }
                ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                <li>
                    <a class="dropdown-item" href="index.php?action=passwd">
                        <?= Translation::get('ad_menu_passwd') ?>
                    </a>
                </li>
                <li><hr class="dropdown-divider" /></li>
                <li>
                    <a class="dropdown-item"
                       href="index.php?action=logout&csrf=<?= Token::getInstance()->getTokenString('logout') ?>">
                        <?= Translation::get('admin_mainmenu_logout'); ?>
                    </a>
                </li>
            </ul>
        </li>
    </ul>
    <?php endif; ?>
</nav>
<!-- /phpMyFAQ Admin Top Bar -->

<div id="pmf-admin-layout-sidenav">

    <?php if ($adminHelper->canAccessContent($user)) : ?>
    <!-- phpMyFAQ Admin Side Navigation -->
    <div id="pmf-admin-layout-sidenav_nav">
        <nav class="pmf-admin-sidenav accordion pmf-admin-sidenav-dark" id="sidenavAccordion">
            <div class="pmf-admin-sidenav-menu">
                <div class="nav">
                    <!-- Dashboard -->
                    <a class="nav-link" href="index.php">
                        <div class="pmf-admin-nav-link-icon"><i class="bi bi-speedometer h6"></i></div>
                        Dashboard
                    </a>

                    <!-- User -->
                    <?php if ($secLevelEntries['user'] !== '') : ?>
                    <a class="nav-link <?= ($userPage) ? '' : 'collapsed' ?>" href="#" data-bs-toggle="collapse"
                       data-bs-target="#collapseUsers" aria-expanded="false" aria-controls="collapseUsers">
                        <div class="pmf-admin-nav-link-icon"><i aria-hidden="true" class="bi bi-person h6"></i></div>
                        <?= Translation::get('admin_mainmenu_users'); ?>
                        <div class="pmf-admin-sidenav-collapse-arrow"><i class="bi bi-arrow-down"></i></div>
                    </a>
                    <div class="<?= ($userPage) ? '' : 'collapse' ?>" id="collapseUsers" aria-labelledby="headingOne"
                         data-bs-parent="#sidenavAccordion">
                        <nav class="pmf-admin-sidenav-menu-nested nav">
                            <?= $secLevelEntries['user']; ?>
                        </nav>
                    </div>
                    <?php endif; ?>
                    <!-- Content -->
                    <?php if ($secLevelEntries['content'] !== '') : ?>
                    <a class="nav-link <?= ($contentPage) ? '' : 'collapsed' ?>" href="#" data-bs-toggle="collapse"
                       data-bs-target="#collapseContent" aria-expanded="false" aria-controls="collapseContent">
                        <div class="pmf-admin-nav-link-icon"><i aria-hidden="true" class="bi bi-pencil-square h6"></i></div>
                        <?= Translation::get('admin_mainmenu_content'); ?>
                        <div class="pmf-admin-sidenav-collapse-arrow"><i class="bi bi-arrow-down"></i></div>
                    </a>
                    <div class="<?= ($contentPage) ? '' : 'collapse' ?>" id="collapseContent"
                         aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                        <nav class="pmf-admin-sidenav-menu-nested nav">
                            <?= $secLevelEntries['content']; ?>
                        </nav>
                    </div>
                    <?php endif; ?>
                    <!-- Statistics -->
                    <?php if ($secLevelEntries['statistics'] !== '') : ?>
                    <a class="nav-link <?= ($statisticsPage) ? '' : 'collapsed' ?>" href="#" data-bs-toggle="collapse"
                       data-bs-target="#collapseStatistics" aria-expanded="false" aria-controls="collapseStatistics">
                        <div class="pmf-admin-nav-link-icon"><i aria-hidden="true" class="bi bi-graph-up-arrow h6"></i></div>
                        <?= Translation::get('admin_mainmenu_statistics'); ?>
                        <div class="pmf-admin-sidenav-collapse-arrow"><i class="bi bi-arrow-down"></i></div>
                    </a>
                    <div class="<?= ($statisticsPage) ? '' : 'collapse' ?>" id="collapseStatistics"
                         aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                        <nav class="pmf-admin-sidenav-menu-nested nav">
                            <?= $secLevelEntries['statistics']; ?>
                        </nav>
                    </div>
                    <?php endif; ?>
                    <!-- Exports -->
                    <?php if ($secLevelEntries['imports_exports'] !== '') : ?>
                    <a class="nav-link <?= ($exportsPage) ? '' : 'collapsed' ?>" href="#" data-bs-toggle="collapse"
                       data-bs-target="#collapseExports" aria-expanded="false" aria-controls="collapseExports">
                        <div class="pmf-admin-nav-link-icon">
                            <i aria-hidden="true" class="bi bi-archive h6"></i>
                        </div>
                        <?= Translation::get('admin_mainmenu_imports_exports'); ?>
                        <div class="pmf-admin-sidenav-collapse-arrow"><i class="bi bi-arrow-down"></i></div>
                    </a>
                    <div class="<?= ($exportsPage) ? '' : 'collapse' ?>" id="collapseExports"
                         aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                        <nav class="pmf-admin-sidenav-menu-nested nav">
                            <?= $secLevelEntries['imports_exports']; ?>
                        </nav>
                    </div>
                    <?php endif; ?>
                    <!-- Backup -->
                    <?php if ($secLevelEntries['backup'] !== '') : ?>
                    <a class="nav-link <?= ($backupPage) ? '' : 'collapsed' ?>" href="#" data-bs-toggle="collapse"
                       data-bs-target="#collapseBackupAdmin" aria-expanded="false" aria-controls="collapseBackupAdmin">
                        <div class="pmf-admin-nav-link-icon">
                            <i aria-hidden="true" class="bi bi-cloud-download"></i>
                        </div>
                        <?= Translation::get('admin_mainmenu_backup'); ?>
                        <div class="pmf-admin-sidenav-collapse-arrow"><i class="bi bi-arrow-down"></i></div>
                    </a>
                    <div class="<?= ($backupPage) ? '' : 'collapse' ?>" id="collapseBackupAdmin"
                         aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                        <nav class="pmf-admin-sidenav-menu-nested nav">
                            <?= $secLevelEntries['backup']; ?>
                        </nav>
                    </div>
                    <?php endif; ?>
                    <!-- Config -->
                    <?php if ($secLevelEntries['config'] !== '') : ?>
                    <a class="nav-link <?= ($configurationPage) ? '' : 'collapsed' ?>" href="#"
                       data-bs-toggle="collapse" data-bs-target="#collapseConfigAdmin" aria-expanded="false"
                       aria-controls="collapseConfigAdmin">
                        <div class="pmf-admin-nav-link-icon"><i aria-hidden="true" class="bi bi-wrench"></i></div>
                        <?= Translation::get('admin_mainmenu_configuration'); ?>
                        <div class="pmf-admin-sidenav-collapse-arrow"><i class="bi bi-arrow-down"></i></div>
                    </a>
                    <div class="<?= ($configurationPage) ? '' : 'collapse' ?>" id="collapseConfigAdmin"
                         aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                        <nav class="pmf-admin-sidenav-menu-nested nav">
                            <?= $secLevelEntries['config']; ?>
                        </nav>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <div class="pmf-admin-sidenav-footer">
                <div class="small">Logged in as:</div>
                <?= Strings::htmlentities($user->getUserData('display_name')) ?>
            </div>
        </nav>
    </div>
    <?php endif; ?>
    <!-- /phpMyFAQ Admin Side Navigation -->

    <!-- phpMyFAQ Admin Main Content -->
    <div id="pmf-admin-layout-sidenav_content">
        <main>
            <div class="container-fluid px-4">
