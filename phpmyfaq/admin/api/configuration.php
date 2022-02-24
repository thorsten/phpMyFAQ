<?php

/**
 * Private phpMyFAQ Admin API: handling of REST configuration calls.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2022 phpMyFAQ Team
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2009-04-01
 */

use phpMyFAQ\Database;
use phpMyFAQ\Entity\InstanceEntity;
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

$ajaxAction = Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_UNSAFE_RAW);
$instanceId = Filter::filterInput(INPUT_GET, 'instanceId', FILTER_VALIDATE_INT);
$stopwordId = Filter::filterInput(INPUT_GET, 'stopword_id', FILTER_VALIDATE_INT);
$stopword = Filter::filterInput(INPUT_GET, 'stopword', FILTER_UNSAFE_RAW);
$stopwordsLang = Filter::filterInput(INPUT_GET, 'stopwords_lang', FILTER_UNSAFE_RAW);
$csrfToken = Filter::filterInput(INPUT_GET, 'csrf', FILTER_UNSAFE_RAW);

$http = new HttpHelper();
$stopwords = new Stopwords($faqConfig);

switch ($ajaxAction) {
    case 'add_instance':
        $json = file_get_contents('php://input', true);
        $postData = json_decode($json);

        if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $postData->csrf) {
            $http->setStatus(400);
            $http->sendJsonWithHeaders(['error' => $PMF_LANG['err_NotAuth']]);
            exit(1);
        }

        $url = Filter::filterVar($postData->url, FILTER_UNSAFE_RAW);
        $instance = Filter::filterVar($postData->instance, FILTER_UNSAFE_RAW);
        $comment = Filter::filterVar($postData->comment, FILTER_UNSAFE_RAW);
        $email = Filter::filterVar($postData->email, FILTER_VALIDATE_EMAIL);
        $admin = Filter::filterVar($postData->admin, FILTER_UNSAFE_RAW);
        $password = Filter::filterVar($postData->password, FILTER_UNSAFE_RAW);

        if (empty($url) || empty($instance) || empty($comment) || empty($email) || empty($admin) || empty($password)) {
            $http->setStatus(400);
            $http->sendJsonWithHeaders(['error' => 'Cannot create instance.']);
            exit(1);
        }

        $data = new InstanceEntity();
        $data
            ->setUrl('https://' . $url . '.' . $_SERVER['SERVER_NAME'])
            ->setInstance($instance)
            ->setComment($comment);

        $faqInstance = new Instance($faqConfig);
        $instanceId = $faqInstance->addInstance($data);

        $faqInstanceClient = new Client($faqConfig);
        $faqInstanceClient->createClient($faqInstance);

        $urlParts = parse_url($data->getUrl());
        $hostname = $urlParts['host'];

        if ($faqInstanceClient->createClientFolder($hostname)) {
            $clientDir = PMF_ROOT_DIR . '/multisite/' . $hostname;
            $clientSetup = new Setup();
            $clientSetup->setRootDir($clientDir);

            try {
                $faqInstanceClient->copyConstantsFile($clientDir . '/constants.php');
            } catch (\phpMyFAQ\Core\Exception $e) {
                $http->setStatus(400);
                $http->sendJsonWithHeaders(['error' => $e->getMessage()]);
                exit(1);
            }

            $dbSetup = [
                'dbServer' => $DB['server'],
                'dbPort' => $DB['port'],
                'dbUser' => $DB['user'],
                'dbPassword' => $DB['password'],
                'dbDatabaseName' => $DB['db'],
                'dbPrefix' => substr($hostname, 0, strpos($hostname, '.')),
                'dbType' => $DB['type'],
                'dbPort' => $DB['port']
            ];
            $clientSetup->createDatabaseFile($dbSetup, '');

            $faqInstanceClient->setClientUrl('https://' . $hostname);
            $faqInstanceClient->createClientTables($dbSetup['dbPrefix']);

            Database::setTablePrefix($dbSetup['dbPrefix']);

            // add admin account and rights
            $instanceAdmin = new User($faqConfig);
            $instanceAdmin->createUser($admin, $password, '', 1);
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
            $payload = ['added' => $instanceId, 'url' => $data->getUrl()];
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
            $client = new Client($faqConfig);
            $clientData = $client->getInstanceById($instanceId);
            if (
                1 !== $instanceId &&
                $client->deleteClientFolder($clientData->url) &&
                $client->removeInstance($instanceId)
            ) {
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
            $stopwords->remove((int)$stopwordId);
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
                echo $stopwords->update((int)$stopwordId, $stopword);
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
            ->setPageId(Filter::filterInput(INPUT_GET, 'page_id', FILTER_UNSAFE_RAW))
            ->setType(Filter::filterInput(INPUT_GET, 'type', FILTER_UNSAFE_RAW))
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
        $metaId = Filter::filterInput(INPUT_GET, 'meta_id', FILTER_UNSAFE_RAW);

        if ($meta->delete((int)$metaId)) {
            $payload = ['deleted' => $metaId];
        } else {
            $payload = ['error' => $metaId];
        }

        $http->sendJsonWithHeaders($payload);
        break;
}
