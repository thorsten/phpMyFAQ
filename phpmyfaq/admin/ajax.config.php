<?php

/**
 * AJAX: handling of Ajax configuration calls.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-04-01
 */
if (!defined('IS_VALID_PHPMYFAQ') || !$user->perm->checkRight($user->getUserId(), 'editconfig')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$ajaxAction = PMF_Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);
$instanceId = PMF_Filter::filterInput(INPUT_GET, 'instanceId', FILTER_VALIDATE_INT);
$stopwordId = PMF_Filter::filterInput(INPUT_GET, 'stopword_id', FILTER_VALIDATE_INT);
$stopword = PMF_Filter::filterInput(INPUT_GET, 'stopword', FILTER_SANITIZE_STRING);
$stopwordsLang = PMF_Filter::filterInput(INPUT_GET, 'stopwords_lang', FILTER_SANITIZE_STRING);
$csrfToken = PMF_Filter::filterInput(INPUT_GET, 'csrf', FILTER_SANITIZE_STRING);

$http = new PMF_Helper_Http();
$stopwords = new PMF_Stopwords($faqConfig);

switch ($ajaxAction) {

    case 'add_instance':

        if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
            $http->sendJsonWithHeaders(array('error' => $PMF_LANG['err_NotAuth']));
            exit(1);
        }

        $url = PMF_Filter::filterInput(INPUT_GET, 'url', FILTER_SANITIZE_STRING);
        $instance = PMF_Filter::filterInput(INPUT_GET, 'instance', FILTER_SANITIZE_STRING);
        $comment = PMF_Filter::filterInput(INPUT_GET, 'comment', FILTER_SANITIZE_STRING);
        $email = PMF_Filter::filterInput(INPUT_GET, 'email', FILTER_VALIDATE_EMAIL);
        $admin = PMF_Filter::filterInput(INPUT_GET, 'admin', FILTER_SANITIZE_STRING);
        $password = PMF_Filter::filterInput(INPUT_GET, 'password', FILTER_SANITIZE_STRING);

        $data = array(
            'url' => 'http://'.$url.'.'.$_SERVER['SERVER_NAME'],
            'instance' => $instance,
            'comment' => $comment,
        );

        $faqInstance = new PMF_Instance($faqConfig);
        $instanceId = $faqInstance->addInstance($data);

        $faqInstanceClient = new PMF_Instance_Client($faqConfig);
        $faqInstanceClient->createClient($faqInstance);

        $urlParts = parse_url($data['url']);
        $hostname = $urlParts['host'];

        if ($faqInstanceClient->createClientFolder($hostname)) {
            $clientDir = PMF_ROOT_DIR.'/multisite/'.$hostname;
            $clientSetup = new PMF_Instance_Setup();
            $clientSetup->setRootDir($clientDir);

            $faqInstanceClient->copyConstantsFile($clientDir.'/constants.php');
            $faqInstanceClient->copyLdapConstantsFile($clientDir.'/constants_ldap.php');

            $dbSetup = array(
                'dbServer' => $DB['server'],
                'dbUser' => $DB['user'],
                'dbPassword' => $DB['password'],
                'dbDatabaseName' => $DB['db'],
                'dbPrefix' => substr($hostname, 0, strpos($hostname, '.')),
                'dbType' => $DB['type'],
            );
            $clientSetup->createDatabaseFile($dbSetup, '');

            $faqInstanceClient->setClientUrl('http://'.$hostname);
            $faqInstanceClient->createClientTables($dbSetup['dbPrefix']);

            PMF_Db::setTablePrefix($dbSetup['dbPrefix']);

            // add admin account and rights
            $instanceAdmin = new PMF_User($faqConfig);
            $instanceAdmin->createUser($admin, $password, 1);
            $instanceAdmin->setStatus('protected');
            $instanceAdminData = array(
                'display_name' => '',
                'email' => $email,
            );
            $instanceAdmin->setUserData($instanceAdminData);

            // Add anonymous user account
            $clientSetup->createAnonymousUser($faqConfig);

            PMF_Db::setTablePrefix($DB['prefix']);
        } else {
            $faqInstance->removeInstance($instanceId);
            $payload = array('error' => 'Cannot create instance.');
        }

        if (0 !== $instanceId) {
            $payload = array('added' => $instanceId, 'url' => $data['url']);
        } else {
            $payload = array('error' => $instanceId);
        }
        $http->sendJsonWithHeaders($payload);
        break;

    case 'delete_instance':

        if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
            $http->sendJsonWithHeaders(array('error' => $PMF_LANG['err_NotAuth']));
            exit(1);
        }

        if (null !== $instanceId) {
            $faqInstance = new PMF_Instance($faqConfig);
            if (1 !== $instanceId && $faqInstance->removeInstance($instanceId)) {
                $payload = array('deleted' => $instanceId);
            } else {
                $payload = array('error' => $instanceId);
            }
            $http->sendJsonWithHeaders($payload);
        }
        break;

    case 'edit_instance':
        if (null !== $instanceId) {
            $faqInstance = new PMF_Instance($faqConfig);
            if ($faqInstance->removeInstance($instanceId)) {
                $payload = array('deleted' => $instanceId);
            } else {
                $payload = array('error' => $instanceId);
            }
            $http->sendJsonWithHeaders($payload);
        }
        break;

    case 'load_stop_words_by_lang':
        if (PMF_Language::isASupportedLanguage($stopwordsLang)) {
            $stopwordsList = $stopwords->getByLang($stopwordsLang);

            $payload = $stopwordsList;
            $http->sendJsonWithHeaders($payload);
        }
        break;

    case 'delete_stop_word':
        if (null != $stopwordId && PMF_Language::isASupportedLanguage($stopwordsLang)) {
            $stopwords->setLanguage($stopwordsLang);
            $stopwords->remove($stopwordId);
        }
        break;

    case 'save_stop_word':

        if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
            $http->sendJsonWithHeaders(array('error' => $PMF_LANG['err_NotAuth']));
            exit(1);
        }

        if (null != $stopword && PMF_Language::isASupportedLanguage($stopwordsLang)) {
            $stopwords->setLanguage($stopwordsLang);
            if (null !== $stopwordId && -1 < $stopwordId) {
                echo $stopwords->update($stopwordId, $stopword);
            } elseif (!$stopwords->match($stopword)) {
                echo $stopwords->add($stopword);
            }
        }
        break;
}
