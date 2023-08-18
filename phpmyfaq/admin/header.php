<?php

/**
 * Header of the admin area.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2023 phpMyFAQ Team
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2003-02-26
 */

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
    'category',
    'ad_menu_categ_edit',
    $action
);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry('add_faq', 'editentry', 'ad_entry_add', $action);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry('edit_faq+delete_faq', 'view', 'ad_menu_entry_edit', $action);
//if (DEBUG) {
//    $secLevelEntries['content'] .= $adminHelper->addMenuEntry(
//        'edit_faq+delete_faq',
//        'faqs-overview',
//        'ad_menu_entry_edit',
//        $action
//    );
//}

$secLevelEntries['content'] .= $adminHelper->addMenuEntry(
    'edit_faq+delete_faq',
    'searchfaqs',
    'ad_menu_searchfaqs',
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
$secLevelEntries['content'] .= $adminHelper->addMenuEntry('edit_faq', 'tags', 'ad_entry_tags', $action);

$secLevelEntries['statistics'] = $adminHelper->addMenuEntry('viewlog', 'statistics', 'ad_menu_stat', $action);
$secLevelEntries['statistics'] .= $adminHelper->addMenuEntry('viewlog', 'viewsessions', 'ad_menu_session', $action);
$secLevelEntries['statistics'] .= $adminHelper->addMenuEntry('adminlog', 'adminlog', 'ad_menu_adminlog', $action);
$secLevelEntries['statistics'] .= $adminHelper->addMenuEntry('viewlog', 'searchstats', 'ad_menu_searchstats', $action);
$secLevelEntries['statistics'] .= $adminHelper->addMenuEntry('reports', 'reports', 'ad_menu_reports', $action);

$secLevelEntries['exports'] = $adminHelper->addMenuEntry('export', 'export', 'ad_menu_export', $action);

$secLevelEntries['backup'] = $adminHelper->addMenuEntry('editconfig', 'backup', 'ad_menu_backup', $action);

$secLevelEntries['config'] = $adminHelper->addMenuEntry('editconfig', 'config', 'ad_menu_editconfig', $action);
$secLevelEntries['config'] .= $adminHelper->addMenuEntry('editconfig', 'system', 'ad_system_info', $action);
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
    case 'delete-tag':
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
<html lang="<?= Translation::get('metaLanguage'); ?>">
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

  <script src="../assets/dist/backend.js?<?= time(); ?>"></script>
  <script src="assets/js/configuration.js"></script>
  <link rel="shortcut icon" href="../assets/themes/<?= Template::getTplSetName(); ?>/img/favicon.ico">
</head>
<body dir="<?= Translation::get('dir'); ?>" id="page-top">

<!-- phpMyFAQ Admin Top Bar -->
<nav class="pmf-admin-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="../">phpMyFAQ <?= System::getVersion() ?></a>

    <?php if ($user->isLoggedIn() && ((is_countable($user->perm->getAllUserRights($user->getUserId())) ? count($user->perm->getAllUserRights($user->getUserId())) : 0) || $user->isSuperAdmin())): ?>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!">
        <i class="fa fa-bars"></i>
    </button>

    <ul class="nav col-12 col-lg-auto me-lg-auto mb-2 justify-content-center mb-md-0">
        <li>
            <div class="text-white-50 small">
                <i class="fa fa-clock-o fa-fw"></i> <?= Translation::get('ad_session_expiration'); ?>:
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

    <?php if ($user->isLoggedIn() && ((is_countable($user->perm->getAllUserRights($user->getUserId())) ? count($user->perm->getAllUserRights($user->getUserId())) : 0) || $user->isSuperAdmin())): ?>
    <!-- Navbar-->
    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown"
               aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
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
                    echo '<i aria-hidden="true" class="fa fa-user"></i>';
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

    <?php if ($user->isLoggedIn() && ((is_countable($user->perm->getAllUserRights($user->getUserId())) ? count($user->perm->getAllUserRights($user->getUserId())) : 0) || $user->isSuperAdmin())) : ?>
    <!-- phpMyFAQ Admin Side Navigation -->
    <div id="pmf-admin-layout-sidenav_nav">
        <nav class="pmf-admin-sidenav accordion pmf-admin-sidenav-dark" id="sidenavAccordion">
            <div class="pmf-admin-sidenav-menu">
                <div class="nav">
                    <!-- Dashboard -->
                    <a class="nav-link" href="index.php">
                        <div class="pmf-admin-nav-link-icon"><i class="fa fa-tachometer"></i></div>
                        Dashboard
                    </a>

                    <!-- User -->
                    <?php if ($secLevelEntries['user'] !== '') : ?>
                    <a class="nav-link <?= ($userPage) ? '' : 'collapsed' ?>" href="#" data-bs-toggle="collapse"
                       data-bs-target="#collapseUsers" aria-expanded="false" aria-controls="collapseUsers">
                        <div class="pmf-admin-nav-link-icon"><i aria-hidden="true" class="fa fa-user"></i></div>
                        <?= Translation::get('admin_mainmenu_users'); ?>
                        <div class="pmf-admin-sidenav-collapse-arrow"><i class="fa fa-angle-down"></i></div>
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
                        <div class="pmf-admin-nav-link-icon"><i aria-hidden="true" class="fa fa-edit"></i></div>
                        <?= Translation::get('admin_mainmenu_content'); ?>
                        <div class="pmf-admin-sidenav-collapse-arrow"><i class="fa fa-angle-down"></i></div>
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
                        <div class="pmf-admin-nav-link-icon"><i aria-hidden="true" class="fa fa-tasks"></i></div>
                        <?= Translation::get('admin_mainmenu_statistics'); ?>
                        <div class="pmf-admin-sidenav-collapse-arrow"><i class="fa fa-angle-down"></i></div>
                    </a>
                    <div class="<?= ($statisticsPage) ? '' : 'collapse' ?>" id="collapseStatistics"
                         aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                        <nav class="pmf-admin-sidenav-menu-nested nav">
                            <?= $secLevelEntries['statistics']; ?>
                        </nav>
                    </div>
                    <?php endif; ?>
                    <!-- Exports -->
                    <?php if ($secLevelEntries['exports'] !== '') : ?>
                    <a class="nav-link <?= ($exportsPage) ? '' : 'collapsed' ?>" href="#" data-bs-toggle="collapse"
                       data-bs-target="#collapseExports" aria-expanded="false" aria-controls="collapseExports">
                        <div class="pmf-admin-nav-link-icon"><i aria-hidden="true" class="fa fa-file-archive-o"></i></div>
                        <?= Translation::get('admin_mainmenu_exports'); ?>
                        <div class="pmf-admin-sidenav-collapse-arrow"><i class="fa fa-angle-down"></i></div>
                    </a>
                    <div class="<?= ($exportsPage) ? '' : 'collapse' ?>" id="collapseExports"
                         aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                        <nav class="pmf-admin-sidenav-menu-nested nav">
                            <?= $secLevelEntries['exports']; ?>
                        </nav>
                    </div>
                    <?php endif; ?>
                    <!-- Backup -->
                    <?php if ($secLevelEntries['backup'] !== '') : ?>
                    <a class="nav-link <?= ($backupPage) ? '' : 'collapsed' ?>" href="#" data-bs-toggle="collapse"
                       data-bs-target="#collapseBackupAdmin" aria-expanded="false" aria-controls="collapseBackupAdmin">
                        <div class="pmf-admin-nav-link-icon"><i aria-hidden="true" class="fa fa-cloud-download"></i></div>
                        <?= Translation::get('admin_mainmenu_backup'); ?>
                        <div class="pmf-admin-sidenav-collapse-arrow"><i class="fa fa-angle-down"></i></div>
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
                        <div class="pmf-admin-nav-link-icon"><i aria-hidden="true" class="fa fa-wrench"></i></div>
                        <?= Translation::get('admin_mainmenu_configuration'); ?>
                        <div class="pmf-admin-sidenav-collapse-arrow"><i class="fa fa-angle-down"></i></div>
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
