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

$adminHelper = new PMF_Helper_Administration();
$adminHelper->setPermission($permission);

switch ($action) {
    case 'user':
    case 'group':
    case 'passwd':
    case 'cookies':
        $templateVars['secLevelHeader'] = $PMF_LANG['admin_mainmenu_users'];
        $templateVars['secLevelEntries'] .= $adminHelper->addMenuEntry('adduser+edituser+deluser', 'user', 'ad_menu_user_administration', $action);
        if ($faqConfig->get('security.permLevel') != 'basic') {
            $templateVars['secLevelEntries'] .= $adminHelper->addMenuEntry('addgroup+editgroup+delgroup', 'group', 'ad_menu_group_administration', $action);
        }
        $templateVars['secLevelEntries'] .= $adminHelper->addMenuEntry('passwd', 'passwd', 'ad_menu_passwd', $action);
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
        $templateVars['secLevelHeader']   = $PMF_LANG['admin_mainmenu_content'];
        $templateVars['secLevelEntries'] .= $adminHelper->addMenuEntry('addcateg+editcateg+delcateg', 'category', 'ad_menu_categ_edit', $action);
        $templateVars['secLevelEntries'] .= $adminHelper->addMenuEntry('addbt', 'editentry', 'ad_entry_add', $action);
        $templateVars['secLevelEntries'] .= $adminHelper->addMenuEntry('editbt+delbt', 'view', 'ad_menu_entry_edit', $action);
        $templateVars['secLevelEntries'] .= $adminHelper->addMenuEntry('editbt+delbt', 'searchfaqs', 'ad_menu_searchfaqs', $action);
        $templateVars['secLevelEntries'] .= $adminHelper->addMenuEntry('delcomment', 'comments', 'ad_menu_comments', $action);
        $templateVars['secLevelEntries'] .= $adminHelper->addMenuEntry('delquestion', 'question', 'ad_menu_open', $action);
        $templateVars['secLevelEntries'] .= $adminHelper->addMenuEntry('addglossary+editglossary+delglossary', 'glossary', 'ad_menu_glossary', $action);
        $templateVars['secLevelEntries'] .= $adminHelper->addMenuEntry('addnews+editnews+delnews', 'news', 'ad_menu_news_edit', $action);
        $templateVars['secLevelEntries'] .= $adminHelper->addMenuEntry('addattachment+editattachment+delattachment', 'attachments', 'ad_menu_attachments', $action);
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
        $templateVars['secLevelHeader']   = $PMF_LANG['admin_mainmenu_statistics'];
        $templateVars['secLevelEntries'] .= $adminHelper->addMenuEntry('viewlog', 'statistics', 'ad_menu_stat', $action);
        $templateVars['secLevelEntries'] .= $adminHelper->addMenuEntry('viewlog', 'viewsessions', 'ad_menu_session', $action);
        $templateVars['secLevelEntries'] .= $adminHelper->addMenuEntry('adminlog', 'adminlog', 'ad_menu_adminlog', $action);
        $templateVars['secLevelEntries'] .= $adminHelper->addMenuEntry('viewlog', 'searchstats', 'ad_menu_searchstats', $action);
        $templateVars['secLevelEntries'] .= $adminHelper->addMenuEntry('reports', 'reports', 'ad_menu_reports', $action);
        $templateVars['activePage'] = 'statistics';
        break;
    case 'export':
        $templateVars['secLevelHeader']   = $PMF_LANG['admin_mainmenu_exports'];
        $templateVars['secLevelEntries'] .= $adminHelper->addMenuEntry('', 'export', 'ad_menu_export', $action);
        $templateVars['activePage'] = 'exports';
        break;
    case 'backup':
        $templateVars['secLevelHeader']   = $PMF_LANG['admin_mainmenu_backup'];
        $templateVars['secLevelEntries'] .= $adminHelper->addMenuEntry('', 'backup', 'ad_menu_export', $action);
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
        $templateVars['secLevelHeader']    = $PMF_LANG['admin_mainmenu_configuration'];
        $templateVars['secLevelEntries']  .= $adminHelper->addMenuEntry('editconfig', 'config', 'ad_menu_editconfig', $action);
        $templateVars['secLevelEntries']  .= $adminHelper->addMenuEntry('', 'system', 'ad_system_info', $action, false);
        $templateVars['secLevelEntries']  .= $adminHelper->addMenuEntry('editinstances+addinstances+delinstances', 'instances', 'ad_menu_instances', $action);
        $templateVars['secLevelEntries']  .= $adminHelper->addMenuEntry('editconfig', 'stopwordsconfig', 'ad_menu_stopwordsconfig', $action);
        $templateVars['secLevelEntries']  .= $adminHelper->addMenuEntry('edittranslation+addtranslation+deltranslation', 'translist', 'ad_menu_translations', $action);
        $templateVars['activePage'] = 'configuration';
        break;
    default:
        $templateVars['secLevelHeader']   = $PMF_LANG['admin_mainmenu_home'];
        $templateVars['secLevelEntries'] .= $adminHelper->addMenuEntry('addcateg+editcateg+delcateg', 'category', 'ad_menu_categ_edit');
        $templateVars['secLevelEntries'] .= $adminHelper->addMenuEntry('addbt', 'editentry', 'ad_quick_record');
        $templateVars['secLevelEntries'] .= $adminHelper->addMenuEntry('editbt+delbt', 'view', 'ad_menu_entry_edit');
        $templateVars['secLevelEntries'] .= $adminHelper->addMenuEntry('delquestion', 'question', 'ad_menu_open');
        $templateVars['secLevelEntries'] .= $adminHelper->addMenuEntry('', 'system', 'ad_system_info', $action, false);
        $templateVars['activePage'] = 'dashboard';
        break;
}

$twig->loadTemplate('header.twig')
    ->display($templateVars);

unset($templateVars);
