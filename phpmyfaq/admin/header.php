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
use phpMyFAQ\System;
use phpMyFAQ\Template;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use Twig\Extension\DebugExtension;

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
    'faqOverview',
    $action
);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry(
    PermissionType::FAQ_EDIT->value,
    'stickyfaqs',
    'stickyRecordsHeader',
    $action
);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry('delquestion', 'question', 'ad_menu_open', $action);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry('delcomment', 'comments', 'ad_menu_comments', $action);
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
    case 'forms':
        $configurationPage = true;
        break;
    default:
        $dashboardPage = true;
        break;
}

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$twig->addExtension(new DebugExtension());
$template = $twig->loadTemplate('./admin/header.twig');

if ($faqConfig->get('main.enableGravatarSupport')) {
    $avatar = new Gravatar();
    $gravatarImage = $avatar->getImage(
        $user->getUserData('email'),
        ['size' => 24, 'class' => 'img-profile rounded-circle']
    );
}

$templateVars = [
    'metaLanguage' => Translation::get('metaLanguage'),
    'layoutMode' => 'light',
    'pageTitle' => $faqConfig->getTitle() . ' - ' . System::getPoweredByString(),
    'baseHref' => $faqConfig->getDefaultUrl() . 'admin/',
    'version' => System::getVersion(),
    'currentYear' => date('Y'),
    'metaRobots' => $faqConfig->get('seo.metaTagsAdmin'),
    'templateSetName' => Template::getTplSetName(),
    'pageDirection' => Translation::get('dir'),
    'userHasAccessPermission' => $adminHelper->canAccessContent($user),
    'msgSessionExpiration' => Translation::get('ad_session_expiration'),
    'pageAction' => isset($action) ? '?action=' . $action : '',
    'renderedLanguageSelection' => LanguageHelper::renderSelectLanguage($faqLangCode, true),
    'userName' => $user->getUserData('display_name'),
    'hasGravatarSupport' => $faqConfig->get('main.enableGravatarSupport'),
    'gravatarImage' => $gravatarImage ?? '',
    'msgChangePassword' => Translation::get('ad_menu_passwd'),
    'csrfTokenLogout' => Token::getInstance()->getTokenString('admin-logout'),
    'msgLogout' => Translation::get('admin_mainmenu_logout'),
    'secondLevelEntries' => $secLevelEntries,
    'menuUsers' => Translation::get('admin_mainmenu_users'),
    'menuContent' => Translation::get('admin_mainmenu_content'),
    'menuStatistics' => Translation::get('admin_mainmenu_statistics'),
    'menuImportsExports' => Translation::get('admin_mainmenu_imports_exports'),
    'menuBackup' => Translation::get('admin_mainmenu_backup'),
    'menuConfiguration' => Translation::get('admin_mainmenu_configuration'),
    'userPage' => $userPage,
    'contentPage' => $contentPage,
    'statisticsPage' => $statisticsPage,
    'exportsPage' => $exportsPage,
    'backupPage' => $backupPage,
    'configurationPage' => $configurationPage,
];

echo $template->render($templateVars);
