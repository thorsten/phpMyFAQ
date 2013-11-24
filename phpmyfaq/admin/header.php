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

use PMF\Helper\AdminMenuBuilder;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON'){
        $protocol = 'https';
    }
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$httpHeader = new PMF_Helper_Http();
$httpHeader->setContentType('text/html');
$httpHeader->addHeader();

$templateVars = array(
    'PMF_LANG'              => $PMF_LANG,
    'appleTouchIcon'        => '../assets/template/' . PMF_Template::getTplSetName() . '/apple-touch-icon.png',
    'baseUrl'               => $faqConfig->get('main.referenceURL') . '/admin/',
    'editorAutosaveActive'  => ('editentry' === $action) && $faqConfig->get('records.autosaveActive'),
    'editorAutosaveSeconds' => $faqConfig->get('records.autosaveSecs'),
    'gravatarActive'        => $faqConfig->get('main.enableGravatarSupport'),
    'isAuthenticated'       => isset($auth) && in_array(true, $permission),
    'pmfVersion'            => $faqConfig->get('main.currentVersion'),
    'secLevelEntries'       => '',
    'shortcutIcon'          => '../assets/template/' . PMF_Template::getTplSetName() . '/favicon.ico',
    'time'                  => time(),
    'titleFAQ'              => $faqConfig->get('main.titleFAQ'),
    'userDisplayName'       => isset($user) ? $user->getUserData('display_name'): '',
    'userTooltip'           => isset($user) ? $PMF_LANG['ad_user_loggedin'] . $user->getLogin() : '',
    'userEmail'             => isset($user) ? $user->getUserData('email') : ''
);

if (isset($user) && $faqConfig->get('main.enableGravatarSupport')) {
    $avatar = new PMF_Services_Gravatar($faqConfig);
    $templateVars['gravatarImage'] = $avatar->getImage($user->getUserData('email'), array('size' => 30));
    unset($avatar);
} else {
    $templateVars['gravatarImage'] = '';
}

$adminMenuBuilder = new AdminMenuBuilder($twig);
$adminMenuBuilder->setPermission($permission);

switch ($action) {
    case 'user':
    case 'group':
    case 'passwd':
    case 'cookies':
        $adminMenuBuilder->setHeader($PMF_LANG['admin_mainmenu_users']);
        $adminMenuBuilder->addMenuEntry('adduser+edituser+deluser', 'user', 'ad_menu_user_administration', $action);
        if ($faqConfig->get('security.permLevel') != 'basic') {
            $adminMenuBuilder->addMenuEntry('addgroup+editgroup+delgroup', 'group', 'ad_menu_group_administration', $action);
        }
        $adminMenuBuilder->addMenuEntry('passwd', 'passwd', 'ad_menu_passwd', $action);
        $templateVars['activePage'] = 'user';
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
        $adminMenuBuilder->setHeader($PMF_LANG['admin_mainmenu_content']);
        $adminMenuBuilder->addMenuEntry('addcateg+editcateg+delcateg', 'category', 'ad_menu_categ_edit', $action);
        $adminMenuBuilder->addMenuEntry('addbt', 'editentry', 'ad_entry_add', $action);
        $adminMenuBuilder->addMenuEntry('editbt+delbt', 'view', 'ad_menu_entry_edit', $action);
        $adminMenuBuilder->addMenuEntry('editbt+delbt', 'searchfaqs', 'ad_menu_searchfaqs', $action);
        $adminMenuBuilder->addMenuEntry('delcomment', 'comments', 'ad_menu_comments', $action);
        $adminMenuBuilder->addMenuEntry('delquestion', 'question', 'ad_menu_open', $action);
        $adminMenuBuilder->addMenuEntry('addglossary+editglossary+delglossary', 'glossary', 'ad_menu_glossary', $action);
        $adminMenuBuilder->addMenuEntry('addnews+editnews+delnews', 'news', 'ad_menu_news_edit', $action);
        $adminMenuBuilder->addMenuEntry('addattachment+editattachment+delattachment', 'attachments', 'ad_menu_attachments', $action);
        $templateVars['activePage'] = 'content';
        break;
    case 'statistics':
    case 'viewsessions':
    case 'sessionbrowse':
    case 'sessionsuche':
    case 'adminlog':
    case 'searchstats':
    case 'reports':
    case 'reportview':
        $adminMenuBuilder->setHeader($PMF_LANG['admin_mainmenu_statistics']);
        $adminMenuBuilder->addMenuEntry('viewlog', 'statistics', 'ad_menu_stat', $action);
        $adminMenuBuilder->addMenuEntry('viewlog', 'viewsessions', 'ad_menu_session', $action);
        $adminMenuBuilder->addMenuEntry('adminlog', 'adminlog', 'ad_menu_adminlog', $action);
        $adminMenuBuilder->addMenuEntry('viewlog', 'searchstats', 'ad_menu_searchstats', $action);
        $adminMenuBuilder->addMenuEntry('reports', 'reports', 'ad_menu_reports', $action);
        $templateVars['activePage'] = 'statistics';
        break;
    case 'export':
        $adminMenuBuilder->setHeader($PMF_LANG['admin_mainmenu_exports']);
        $adminMenuBuilder->addMenuEntry('', 'export', 'ad_menu_export', $action);
        $templateVars['activePage'] = 'exports';
        break;
    case 'backup':
        $adminMenuBuilder->setHeader($PMF_LANG['admin_mainmenu_backup']);
        $adminMenuBuilder->addMenuEntry('', 'backup', 'ad_menu_export', $action);
        $templateVars['activePage'] = 'backup';
        break;
    case 'config':
    case 'stopwordsconfig':
    case 'translist':
    case 'transedit':
    case 'transadd':
    case 'upgrade':
    case 'instances':
    case 'system':
        $adminMenuBuilder->setHeader($PMF_LANG['admin_mainmenu_configuration']);
        $adminMenuBuilder->addMenuEntry('editconfig', 'config', 'ad_menu_editconfig', $action);
        $adminMenuBuilder->addMenuEntry('', 'system', 'ad_system_info', $action, false);
        $adminMenuBuilder->addMenuEntry('editinstances+addinstances+delinstances', 'instances', 'ad_menu_instances', $action);
        $adminMenuBuilder->addMenuEntry('editconfig', 'stopwordsconfig', 'ad_menu_stopwordsconfig', $action);
        $adminMenuBuilder->addMenuEntry('edittranslation+addtranslation+deltranslation', 'translist', 'ad_menu_translations', $action);
        $templateVars['activePage'] = 'configuration';
        break;
    default:
        $adminMenuBuilder->setHeader($PMF_LANG['admin_mainmenu_home']);
        $adminMenuBuilder->addMenuEntry('addcateg+editcateg+delcateg', 'category', 'ad_menu_categ_edit');
        $adminMenuBuilder->addMenuEntry('addbt', 'editentry', 'ad_quick_record');
        $adminMenuBuilder->addMenuEntry('editbt+delbt', 'view', 'ad_menu_entry_edit');
        $adminMenuBuilder->addMenuEntry('delquestion', 'question', 'ad_menu_open');
        $adminMenuBuilder->addMenuEntry('', 'system', 'ad_system_info', $action, false);
        $templateVars['activePage'] = 'dashboard';
        break;
}

$templateVars['sideNavigation'] = $adminMenuBuilder->render();

$twig->loadTemplate('header.twig')
    ->display($templateVars);

unset($templateVars);
