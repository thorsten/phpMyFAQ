<?php

/**
 * AJAX: handling of Ajax configuration calls.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2020 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2009-04-01
 */

use phpMyFAQ\Database;
use phpMyFAQ\Entity\MetaEntity as MetaEntity;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Instance;
use phpMyFAQ\Instance\Client;
use phpMyFAQ\Instance\Setup;
use phpMyFAQ\Language;
use phpMyFAQ\Meta;
use phpMyFAQ\Stopwords;
use phpMyFAQ\User;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$ajaxAction = Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);
$instanceId = Filter::filterInput(INPUT_GET, 'instanceId', FILTER_VALIDATE_INT);
$stopwordId = Filter::filterInput(INPUT_GET, 'stopword_id', FILTER_VALIDATE_INT);
$stopword = Filter::filterInput(INPUT_GET, 'stopword', FILTER_SANITIZE_STRING);
$stopwordsLang = Filter::filterInput(INPUT_GET, 'stopwords_lang', FILTER_SANITIZE_STRING);
$csrfToken = Filter::filterInput(INPUT_GET, 'csrf', FILTER_SANITIZE_STRING);

$http = new HttpHelper();
$stopwords = new Stopwords($faqConfig);

switch ($ajaxAction) {
    case 'add_instance':
        if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
            $http->setStatus(400);
            $http->sendJsonWithHeaders(['error' => $PMF_LANG['err_NotAuth']]);
            exit(1);
        }

        $url = Filter::filterInput(INPUT_GET, 'url', FILTER_SANITIZE_STRING);
        $instance = Filter::filterInput(INPUT_GET, 'instance', FILTER_SANITIZE_STRING);
        $comment = Filter::filterInput(INPUT_GET, 'comment', FILTER_SANITIZE_STRING);
        $email = Filter::filterInput(INPUT_GET, 'email', FILTER_VALIDATE_EMAIL);
        $admin = Filter::filterInput(INPUT_GET, 'admin', FILTER_SANITIZE_STRING);
        $password = Filter::filterInput(INPUT_GET, 'password', FILTER_SANITIZE_STRING);

        if (empty($url) || empty($instance) || empty($comment) || empty($email) || empty($admin) || empty($password)) {
            $http->setStatus(400);
            $http->sendJsonWithHeaders(['error' => 'Cannot create instance.']);
            exit(1);
        }

        $data = [
            'url' => 'http://' . $url . '.' . $_SERVER['SERVER_NAME'],
            'instance' => $instance,
            'comment' => $comment,
        ];

        $faqInstance = new Instance($faqConfig);
        $instanceId = $faqInstance->addInstance($data);

        $faqInstanceClient = new Client($faqConfig);
        $faqInstanceClient->createClient($faqInstance);

        $urlParts = parse_url($data['url']);
        $hostname = $urlParts['host'];

        if ($faqInstanceClient->createClientFolder($hostname)) {
            $clientDir = PMF_ROOT_DIR . '/multisite/' . $hostname;
            $clientSetup = new Setup();
            $clientSetup->setRootDir($clientDir);

            try {
                $faqInstanceClient->copyConstantsFile($clientDir . '/constants.php');
            } catch (\phpMyFAQ\Exception $e) {
            }

            $dbSetup = [
                'dbServer' => $DB['server'],
                'dbPort' => $DB['port'],
                'dbUser' => $DB['user'],
                'dbPassword' => $DB['password'],
                'dbDatabaseName' => $DB['db'],
                'dbPrefix' => substr($hostname, 0, strpos($hostname, '.')),
                'dbType' => $DB['type'],
            ];
            $clientSetup->createDatabaseFile($dbSetup, '');

            $faqInstanceClient->setClientUrl('http://' . $hostname);
            $faqInstanceClient->createClientTables($dbSetup['dbPrefix']);

            Database::setTablePrefix($dbSetup['dbPrefix']);

            // add admin account and rights
            $instanceAdmin = new User($faqConfig);
            $instanceAdmin->createUser($admin, $password, null, 1);
            $instanceAdmin->setStatus('protected');
            $instanceAdminData = [
                'display_name' => '',
                'email' => $email,
            ];
            $instanceAdmin->setUserData($instanceAdminData);

            // Add anonymous user account
            $clientSetup->createAnonymousUser($faqConfig);

            Database::setTablePrefix($DB['prefix']);
        } else {
            $faqInstance->removeInstance($instanceId);
            $http->setStatus(400);
            $payload = ['error' => 'Cannot create instance.'];
        }
        if (0 !== $instanceId) {
            $http->setStatus(200);
            $payload = ['added' => $instanceId, 'url' => $data['url']];
        } else {
            $http->setStatus(400);
            $payload = ['error' => $instanceId];
        }
        $http->sendJsonWithHeaders($payload);
        break;

    case 'delete_instance':
        if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
            $http->setStatus(400);
            $http->sendJsonWithHeaders(['error' => $PMF_LANG['err_NotAuth']]);
            exit(1);
        }
        if (null !== $instanceId) {
            $faqInstance = new Instance($faqConfig);
            if (1 !== $instanceId && $faqInstance->removeInstance($instanceId)) {
                $http->setStatus(200);
                $payload = ['deleted' => $instanceId];
            } else {
                $http->setStatus(400);
                $payload = ['error' => $instanceId];
            }
            $http->sendJsonWithHeaders($payload);
        }
        break;

    case 'edit_instance':
        if (null !== $instanceId) {
            $faqInstance = new Instance($faqConfig);
            if ($faqInstance->removeInstance($instanceId)) {
                $http->setStatus(200);
                $payload = ['deleted' => $instanceId];
            } else {
                $http->setStatus(400);
                $payload = ['error' => $instanceId];
            }
            $http->sendJsonWithHeaders($payload);
        }
        break;

    case 'load_stop_words_by_lang':
        if (Language::isASupportedLanguage($stopwordsLang)) {
            $stopwordsList = $stopwords->getByLang($stopwordsLang);

            $payload = $stopwordsList;
            $http->sendJsonWithHeaders($payload);
        }
        break;

    case 'delete_stop_word':
        if (null != $stopwordId && Language::isASupportedLanguage($stopwordsLang)) {
            $stopwords->setLanguage($stopwordsLang);
            $stopwords->remove($stopwordId);
        }
        break;

    case 'save_stop_word':
        if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
            $http->sendJsonWithHeaders(['error' => $PMF_LANG['err_NotAuth']]);
            exit(1);
        }

        if (null != $stopword && Language::isASupportedLanguage($stopwordsLang)) {
            $stopwords->setLanguage($stopwordsLang);

            if (null !== $stopwordId && -1 < $stopwordId) {
                echo $stopwords->update($stopwordId, $stopword);
            } elseif (!$stopwords->match($stopword)) {
                echo $stopwords->add($stopword);
            }
        }
        break;

    case 'add_meta':
        if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
            $http->sendJsonWithHeaders(['error' => $PMF_LANG['err_NotAuth']]);
            exit(1);
        }

        $meta = new Meta($faqConfig);
        $entity = new MetaEntity();

        $entity
            ->setPageId(Filter::filterInput(INPUT_GET, 'page_id', FILTER_SANITIZE_STRING))
            ->setType(Filter::filterInput(INPUT_GET, 'type', FILTER_SANITIZE_STRING))
            ->setContent(Filter::filterInput(INPUT_GET, 'content', FILTER_SANITIZE_SPECIAL_CHARS));

        $metaId = $meta->add($entity);

        if (0 !== $metaId) {
            $payload = ['added' => $metaId];
        } else {
            $payload = ['error' => $metaId];
        }
        $http->sendJsonWithHeaders($payload);
        break;

    case 'delete_meta':
        if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
            $http->sendJsonWithHeaders(['error' => $PMF_LANG['err_NotAuth']]);
            exit(1);
        }

        $meta = new Meta($faqConfig);
        $metaId = Filter::filterInput(INPUT_GET, 'meta_id', FILTER_SANITIZE_STRING);

        if ($meta->delete($metaId)) {
            $payload = ['deleted' => $metaId];
        } else {
            $payload = ['error' => $metaId];
        }

        $http->sendJsonWithHeaders($payload);
        break;
}
