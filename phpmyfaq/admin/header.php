<?php

/**
 * Header of the admin area.
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
 * @deprecated will be removed in phpMyFAQ 4.1
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Helper\AdministrationHelper;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\Services\Gravatar;
use phpMyFAQ\Session\Token;
use phpMyFAQ\System;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;

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
$user = CurrentUser::getCurrentUser($faqConfig);

$adminHelper = new AdministrationHelper();
$adminHelper->setUser($user);

$secLevelEntries['user'] = $adminHelper->addMenuEntry(
    'add_user+edit_user+delete_user',
    'user',
    'ad_menu_user_administration',
    'user'
);
if ($faqConfig->get('security.permLevel') !== 'basic') {
    $secLevelEntries['user'] .= $adminHelper->addMenuEntry(
        'addgroup+editgroup+delgroup',
        'group',
        'ad_menu_group_administration',
        'group'
    );
}
$secLevelEntries['content'] = $adminHelper->addMenuEntry(
    'addcateg+editcateg+delcateg',
    'category-overview',
    'msgHeaderCategoryOverview',
    'category'
);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry(
    PermissionType::FAQ_ADD->value,
    'editentry',
    'ad_entry_add'
);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry(
    'edit_faq+delete_faq',
    'faqs-overview',
    'msgHeaderFAQOverview'
);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry(
    PermissionType::FAQ_EDIT->value,
    'stickyfaqs',
    'stickyRecordsHeader',
    'sticky-faqs'
);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry('delquestion', 'question', 'ad_menu_open', 'questions');
$secLevelEntries['content'] .= $adminHelper->addMenuEntry('delcomment', 'comments', 'ad_menu_comments', 'comments');
$secLevelEntries['content'] .= $adminHelper->addMenuEntry(
    'addattachment+editattachment+delattachment',
    'attachments',
    'ad_menu_attachments',
    'attachments'
);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry(
    PermissionType::FAQ_EDIT->value,
    'tags',
    'ad_entry_tags',
    'tags'
);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry(
    'addglossary+editglossary+delglossary',
    'glossary',
    'ad_menu_glossary',
    'glossary'
);
$secLevelEntries['content'] .= $adminHelper->addMenuEntry(
    'addnews+editnews+delnews',
    'news',
    'ad_menu_news_edit',
    'news'
);

$secLevelEntries['statistics'] = $adminHelper->addMenuEntry(
    PermissionType::STATISTICS_VIEWLOGS->value,
    'statistics',
    'ad_menu_stat',
    'statistics/ratings'
);
$secLevelEntries['statistics'] .= $adminHelper->addMenuEntry(
    PermissionType::STATISTICS_VIEWLOGS->value,
    'viewsessions',
    'ad_menu_session',
    'statistics/sessions'
);
$secLevelEntries['statistics'] .= $adminHelper->addMenuEntry(
    PermissionType::STATISTICS_ADMINLOG->value,
    'adminlog',
    'ad_menu_adminlog',
    'statistics/admin-log'
);
$secLevelEntries['statistics'] .= $adminHelper->addMenuEntry(
    PermissionType::STATISTICS_VIEWLOGS->value,
    'searchstats',
    'msgAdminElasticsearchStats',
    'statistics/search'
);
$secLevelEntries['statistics'] .= $adminHelper->addMenuEntry(
    'reports',
    'reports',
    'ad_menu_reports',
    'statistics/report'
);

$secLevelEntries['imports_exports'] = $adminHelper->addMenuEntry(
    PermissionType::FAQ_ADD->value,
    'importcsv',
    'msgImportRecords',
    'import'
);
$secLevelEntries['imports_exports'] .= $adminHelper->addMenuEntry('export', 'export', 'ad_menu_export', 'export');

$secLevelEntries['backup'] = $adminHelper->addMenuEntry('editconfig', 'backup', 'ad_menu_backup', 'backup');

$secLevelEntries['config'] = $adminHelper->addMenuEntry(
    'editconfig',
    'config',
    'ad_menu_editconfig',
    'configuration'
);
$secLevelEntries['config'] .= $adminHelper->addMenuEntry('forms_edit', 'forms', 'msgEditForms');
$secLevelEntries['config'] .= $adminHelper->addMenuEntry(
    'editinstances+addinstances+delinstances',
    'instances',
    'ad_menu_instances',
    'instances'
);
$secLevelEntries['config'] .= $adminHelper->addMenuEntry(
    'editconfig',
    'stopwordsconfig',
    'ad_menu_stopwordsconfig',
    'stopwords'
);
$secLevelEntries['config'] .= $adminHelper->addMenuEntry('editconfig', 'upgrade', 'msgAdminHeaderUpdate', 'update');
if ($faqConfig->get('search.enableElasticsearch')) {
    $secLevelEntries['config'] .= $adminHelper->addMenuEntry(
        'editconfig',
        'elasticsearch',
        'msgAdminHeaderElasticsearch',
        'elasticsearch'
    );
}
$secLevelEntries['config'] .= $adminHelper->addMenuEntry('editconfig', 'system', 'ad_system_info', 'system');

switch ($action) {
    case 'translatecategory':
    case 'faqs-overview':
    case 'editentry':
    case 'copyentry':
    case 'question':
    case 'takequestion':
    case 'stickyfaqs':
        $contentPage = true;
        break;
    case 'forms':
        $configurationPage = true;
        break;
    default:
        $dashboardPage = true;
        break;
}

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$template = $twig->loadTemplate('@admin/header.twig');

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
    'templateSetName' => TwigWrapper::getTemplateSetName(),
    'pageDirection' => Translation::get('direction'),
    'userHasAccessPermission' => $adminHelper->canAccessContent($user),
    'msgSessionExpiration' => Translation::get('ad_session_expiration'),
    'pageAction' => isset($action) ? '?action=' . $action : '',
    'renderedLanguageSelection' => LanguageHelper::renderSelectLanguage($faqLangCode, true),
    'userName' => $user->getUserData('display_name'),
    'hasGravatarSupport' => $faqConfig->get('main.enableGravatarSupport'),
    'gravatarImage' => $gravatarImage ?? '',
    'msgChangePassword' => Translation::get('ad_menu_passwd'),
    'csrfTokenLogout' => Token::getInstance($container->get('session'))->getTokenString('admin-logout'),
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
