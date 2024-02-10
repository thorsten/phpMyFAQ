<?php

/**
 * The Admin Instance Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-28
 */

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\DatabaseConfiguration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Entity\InstanceEntity;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filesystem;
use phpMyFAQ\Filter;
use phpMyFAQ\Instance;
use phpMyFAQ\Instance\Client;
use phpMyFAQ\Instance\Setup;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InstanceController extends AbstractController
{
    #[Route('admin/api/instance/add')]
    public function add(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::INSTANCE_ADD);

        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();

        $data = json_decode($request->getContent());

        if (!Token::getInstance()->verifyToken('add-instance', $data->csrf)) {
            $jsonResponse->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $jsonResponse->setData(['error' => Translation::get('err_NotAuth')]);
            return $jsonResponse;
        }

        $url = Filter::filterVar($data->url, FILTER_SANITIZE_SPECIAL_CHARS);
        $instance = Filter::filterVar($data->instance, FILTER_SANITIZE_SPECIAL_CHARS);
        $comment = Filter::filterVar($data->comment, FILTER_SANITIZE_SPECIAL_CHARS);
        $email = Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL);
        $admin = Filter::filterVar($data->admin, FILTER_SANITIZE_SPECIAL_CHARS);
        $password = Filter::filterVar($data->password, FILTER_SANITIZE_SPECIAL_CHARS);

        if (empty($url) || empty($instance) || empty($comment) || empty($email) || empty($admin) || empty($password)) {
            $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
            $jsonResponse->setData(['error' => 'Cannot create instance.']);
            return $jsonResponse;
        }

        $url = 'https://' . $url . '.' . $_SERVER['SERVER_NAME'];
        if (!Filter::filterVar($url, FILTER_VALIDATE_URL)) {
            $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
            $jsonResponse->setData(['error' => 'Cannot create instance: wrong URL']);
            return $jsonResponse;
        }

        $data = new InstanceEntity();
        $data
            ->setUrl($url)
            ->setInstance($instance)
            ->setComment($comment);

        $faqInstance = new Instance($configuration);
        $instanceId = $faqInstance->addInstance($data);

        $faqInstanceClient = new Client($configuration);
        $faqInstanceClient->createClient($faqInstance);
        $faqInstanceClient->setFileSystem(new Filesystem());

        $urlParts = parse_url($data->getUrl());
        $hostname = $urlParts['host'];

        if ($faqInstanceClient->createClientFolder($hostname)) {
            $clientDir = PMF_ROOT_DIR . '/multisite/' . $hostname;
            $clientSetup = new Setup();
            $clientSetup->setRootDir($clientDir);

            try {
                $faqInstanceClient->copyConstantsFile($clientDir . '/constants.php');
            } catch (Exception $e) {
                $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
                $jsonResponse->setData(['error' => $e->getMessage()]);
                $jsonResponse->send();
                exit(1);
            }

            $databaseConfiguration = new DatabaseConfiguration(PMF_CONFIG_DIR . '/database.php');
            $dbSetup = [
                'dbServer' => $databaseConfiguration->getServer(),
                'dbPort' => $databaseConfiguration->getPort(),
                'dbUser' => $databaseConfiguration->getUser(),
                'dbPassword' => $databaseConfiguration->getPassword(),
                'dbDatabaseName' => $databaseConfiguration->getDatabase(),
                'dbPrefix' => substr($hostname, 0, strpos($hostname, '.')),
                'dbType' => $databaseConfiguration->getType()
            ];
            $clientSetup->createDatabaseFile($dbSetup, '');

            $faqInstanceClient->setClientUrl('https://' . $hostname);
            $faqInstanceClient->createClientTables($dbSetup['dbPrefix']);

            Database::setTablePrefix($dbSetup['dbPrefix']);

            // add an admin account and rights
            $user = new User($configuration);
            $user->createUser($admin, $password, '', 1);
            $user->setStatus('protected');
            $instanceAdminData = [
                'display_name' => '',
                'email' => $email,
            ];
            $user->setUserData($instanceAdminData);

            // Add an anonymous user account
            try {
                $clientSetup->createAnonymousUser($configuration);
            } catch (Exception $e) {
                $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
                $jsonResponse->setData(['error' => $e->getMessage()]);
                return $jsonResponse;
            }

            Database::setTablePrefix($databaseConfiguration->getPrefix());
        } else {
            $faqInstance->removeInstance($instanceId);
            $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
            $jsonResponse->setData(['error' => 'Cannot create instance.']);
            return $jsonResponse;
        }

        if (0 !== $instanceId) {
            $jsonResponse->setStatusCode(Response::HTTP_OK);
            $payload = ['added' => $instanceId, 'url' => $data->getUrl()];
        } else {
            $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
            $payload = ['error' => $instanceId];
        }

        $jsonResponse->setData($payload);

        return $jsonResponse;
    }

    #[Route('admin/api/instance/delete')]
    public function delete(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::INSTANCE_DELETE);

        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();

        $data = json_decode($request->getContent());

        if (!Token::getInstance()->verifyToken('delete-instance', $data->csrf)) {
            $jsonResponse->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $jsonResponse->setData(['error' => Translation::get('err_NotAuth')]);
            return $jsonResponse;
        }

        $instanceId = Filter::filterVar($data->instanceId, FILTER_SANITIZE_SPECIAL_CHARS);

        if (null !== $instanceId) {
            $client = new Client($configuration);
            $client->setFileSystem(new Filesystem());
            $clientData = $client->getInstanceById($instanceId);
            if (
                1 !== $instanceId &&
                $client->deleteClientFolder($clientData->url) &&
                $client->removeInstance($instanceId)
            ) {
                $jsonResponse->setStatusCode(Response::HTTP_OK);
                $payload = ['deleted' => $instanceId];
            } else {
                $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
                $payload = ['error' => $instanceId];
            }

            $jsonResponse->setData($payload);
        }

        return $jsonResponse;
    }
}
