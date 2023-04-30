<?php

/**
 * Private phpMyFAQ Admin API: handling of REST configuration calls.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-04-01
 */

use phpMyFAQ\Configuration\DatabaseConfiguration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Entity\InstanceEntity;
use phpMyFAQ\Entity\TemplateMetaDataEntity;
use phpMyFAQ\Filter;
use phpMyFAQ\Instance;
use phpMyFAQ\Instance\Client;
use phpMyFAQ\Instance\Setup;
use phpMyFAQ\Language;
use phpMyFAQ\Mail;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Template\TemplateMetaData;
use phpMyFAQ\StopWords;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

//
// Create Request & Response
//
$response = new JsonResponse();
$request = Request::createFromGlobals();

$ajaxAction = Filter::filterVar($request->query->get('ajaxaction'), FILTER_SANITIZE_SPECIAL_CHARS);
$instanceId = Filter::filterVar($request->query->get('instanceId'), FILTER_VALIDATE_INT);
$stopwordId = Filter::filterVar($request->query->get('stopword_id'), FILTER_VALIDATE_INT);
$stopword = Filter::filterVar($request->query->get('stopword'), FILTER_SANITIZE_SPECIAL_CHARS);
$stopwordsLang = Filter::filterVar($request->query->get('stopwords_lang'), FILTER_SANITIZE_SPECIAL_CHARS);
$csrfToken = Filter::filterVar($request->query->get('csrf'), FILTER_SANITIZE_SPECIAL_CHARS);

$stopWords = new StopWords($faqConfig);

switch ($ajaxAction) {
    case 'add-instance':
        $postData = json_decode(file_get_contents('php://input', true));

        if (!Token::getInstance()->verifyToken('add-instance', $postData->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            $response->send();
            exit();
        }

        $url = Filter::filterVar($postData->url, FILTER_SANITIZE_SPECIAL_CHARS);
        $instance = Filter::filterVar($postData->instance, FILTER_SANITIZE_SPECIAL_CHARS);
        $comment = Filter::filterVar($postData->comment, FILTER_SANITIZE_SPECIAL_CHARS);
        $email = Filter::filterVar($postData->email, FILTER_VALIDATE_EMAIL);
        $admin = Filter::filterVar($postData->admin, FILTER_SANITIZE_SPECIAL_CHARS);
        $password = Filter::filterVar($postData->password, FILTER_SANITIZE_SPECIAL_CHARS);

        if (empty($url) || empty($instance) || empty($comment) || empty($email) || empty($admin) || empty($password)) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => 'Cannot create instance.']);
            $response->send();
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
            } catch (Exception $e) {
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                $response->setData(['error' => $e->getMessage()]);
                $response->send();
                exit(1);
            }

            $dbConfig = new DatabaseConfiguration(PMF_CONFIG_DIR . '/database.php');
            $dbSetup = [
                'dbServer' => $dbConfig->getServer(),
                'dbPort' => $dbConfig->getPort(),
                'dbUser' => $dbConfig->getUser(),
                'dbPassword' => $dbConfig->getPassword(),
                'dbDatabaseName' => $dbConfig->getDatabase(),
                'dbPrefix' => substr($hostname, 0, strpos($hostname, '.')),
                'dbType' => $dbConfig->getType()
            ];
            $clientSetup->createDatabaseFile($dbSetup, '');

            $faqInstanceClient->setClientUrl('https://' . $hostname);
            $faqInstanceClient->createClientTables($dbSetup['dbPrefix']);

            Database::setTablePrefix($dbSetup['dbPrefix']);

            // add an admin account and rights
            $instanceAdmin = new User($faqConfig);
            $instanceAdmin->createUser($admin, $password, '', 1);
            $instanceAdmin->setStatus('protected');
            $instanceAdminData = [
                'display_name' => '',
                'email' => $email,
            ];
            $instanceAdmin->setUserData($instanceAdminData);

            // Add an anonymous user account
            try {
                $clientSetup->createAnonymousUser($faqConfig);
            } catch (Exception $e) {
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                $payload = ['error' => $e->getMessage()];
            }

            Database::setTablePrefix($dbConfig->getPrefix());
        } else {
            $faqInstance->removeInstance($instanceId);
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $payload = ['error' => 'Cannot create instance.'];
        }
        if (0 !== $instanceId) {
            $response->setStatusCode(Response::HTTP_OK);
            $payload = ['added' => $instanceId, 'url' => $data->getUrl()];
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $payload = ['error' => $instanceId];
        }
        $response->setData($payload);
        $response->send();
        break;

    case 'delete-instance':
        $postData = json_decode(file_get_contents('php://input', true));

        if (!Token::getInstance()->verifyToken('delete-instance', $postData->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            $response->send();
            exit();
        }

        $instanceId = Filter::filterVar($postData->instanceId, FILTER_SANITIZE_SPECIAL_CHARS);

        if (null !== $instanceId) {
            $client = new Client($faqConfig);
            $clientData = $client->getInstanceById($instanceId);
            if (
                1 !== $instanceId &&
                $client->deleteClientFolder($clientData->url) &&
                $client->removeInstance($instanceId)
            ) {
                $response->setStatusCode(Response::HTTP_OK);
                $payload = ['deleted' => $instanceId];
            } else {
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                $payload = ['error' => $instanceId];
            }
            $response->setData($payload);
            $response->send();
        }
        break;

    case 'load_stop_words_by_lang':
        if (Language::isASupportedLanguage($stopwordsLang)) {
            $stopWordsList = $stopWords->getByLang($stopwordsLang);
            $response->setStatusCode(Response::HTTP_OK);
            $response->setData($stopWordsList);
            $response->send();
        }
        break;

    case 'delete_stop_word':
        $deleteData = json_decode(file_get_contents('php://input', true));

        $stopWordId = Filter::filterVar($deleteData->stopWordId, FILTER_VALIDATE_INT);
        $stopWordsLang = Filter::filterVar($deleteData->stopWordsLang, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance()->verifyToken('stopwords', $deleteData->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            $response->send();
            exit();
        }

        if (null != $stopWordId && Language::isASupportedLanguage($stopWordsLang)) {
            $stopWords
                ->setLanguage($stopWordsLang)
                ->remove((int)$stopWordId);
            $response->setStatusCode(Response::HTTP_OK);
            $response->setData(['deleted' => $stopWordId ]);
            $response->send();
        }
        break;

    case 'save_stop_word':
        $postData = json_decode(file_get_contents('php://input', true));

        $stopWordId = Filter::filterVar($postData->stopWordId, FILTER_VALIDATE_INT);
        $stopWordsLang = Filter::filterVar($postData->stopWordsLang, FILTER_SANITIZE_SPECIAL_CHARS);
        $stopWord = Filter::filterVar($postData->stopWord, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance()->verifyToken('stopwords', $postData->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            $response->send();
            exit();
        }

        if (null != $stopWord && Language::isASupportedLanguage($stopWordsLang)) {
            $stopWords->setLanguage($stopWordsLang);

            if (null !== $stopWordId && -1 < $stopWordId) {
                $stopWords->update((int)$stopWordId, $stopWord);
                $response->setStatusCode(Response::HTTP_OK);
                $response->setData(['updated' => $stopWordId ]);
            } elseif (!$stopWords->match($stopWord)) {
                $stopWordId = $stopWords->add($stopWord);
                $response->setStatusCode(Response::HTTP_OK);
                $response->setData(['added' => $stopWordId ]);
            }
            $response->send();
        }
        break;

    case 'add-template-metadata':
        $postData = json_decode(file_get_contents('php://input', true));

        if (!Token::getInstance()->verifyToken('add-metadata', $postData->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            $response->send();
            exit();
        }

        $meta = new TemplateMetaData($faqConfig);
        $entity = new TemplateMetaDataEntity();

        $entity
            ->setPageId(Filter::filterVar($postData->pageId, FILTER_SANITIZE_SPECIAL_CHARS))
            ->setType(Filter::filterVar($postData->type, FILTER_SANITIZE_SPECIAL_CHARS))
            ->setContent(Filter::filterVar($postData->content, FILTER_SANITIZE_SPECIAL_CHARS));

        $metaId = $meta->add($entity);

        if (0 !== $metaId) {
            $payload = ['added' => $metaId];
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $payload = ['error' => $metaId];
        }

        $response->setData($payload);
        $response->send();
        break;

    case 'delete-template-metadata':
        $json = file_get_contents('php://input', true);
        $deleteData = json_decode($json);

        if (!Token::getInstance()->verifyToken('delete-meta-data', $deleteData->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            $response->send();
            exit();
        }

        $meta = new TemplateMetaData($faqConfig);
        $metaId = Filter::filterVar($deleteData->metaId, FILTER_SANITIZE_SPECIAL_CHARS);

        if ($meta->delete((int)$metaId)) {
            $payload = ['deleted' => $metaId];
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $payload = ['error' => $metaId];
        }

        $response->setData($payload);
        $response->send();
        break;

    case 'send-test-mail':
        $json = file_get_contents('php://input', true);
        $postData = json_decode($json);

        if (!Token::getInstance()->verifyToken('configuration', $postData->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            $response->send();
            exit();
        }

        try {
            $mailer = new Mail($faqConfig);
            $mailer->setReplyTo($faqConfig->getAdminEmail());
            $mailer->addTo($faqConfig->getAdminEmail());
            $mailer->subject = $faqConfig->getTitle() . ': Mail test successful.';
            $mailer->message = 'It works on my machine. 🚀';
            $result = $mailer->send();

            $response->setStatusCode(Response::HTTP_OK);
            $response->setData(['success' => $result]);
        } catch (Exception | TransportExceptionInterface $e) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => $e->getMessage()]);
        }

        $response->send();
        break;
}
