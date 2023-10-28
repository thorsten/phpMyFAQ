<?php

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\DatabaseConfiguration;
use phpMyFAQ\Controller;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Entity\InstanceEntity;
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

class InstanceController extends Controller
{
    #[Route('admin/api/instance/add')]
    public function add(Request $request): JsonResponse
    {
        $response = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();

        $data = json_decode($request->getContent());

        if (!Token::getInstance()->verifyToken('add-instance', $data->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            return $response;
        }

        $url = Filter::filterVar($data->url, FILTER_SANITIZE_SPECIAL_CHARS);
        $instance = Filter::filterVar($data->instance, FILTER_SANITIZE_SPECIAL_CHARS);
        $comment = Filter::filterVar($data->comment, FILTER_SANITIZE_SPECIAL_CHARS);
        $email = Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL);
        $admin = Filter::filterVar($data->admin, FILTER_SANITIZE_SPECIAL_CHARS);
        $password = Filter::filterVar($data->password, FILTER_SANITIZE_SPECIAL_CHARS);

        if (empty($url) || empty($instance) || empty($comment) || empty($email) || empty($admin) || empty($password)) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => 'Cannot create instance.']);
            return $response;
        }

        $url = 'https://' . $url . '.' . $_SERVER['SERVER_NAME'];
        if (!Filter::filterVar($url, FILTER_VALIDATE_URL)) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => 'Cannot create instance: wrong URL']);
            return $response;
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
            $instanceAdmin = new User($configuration);
            $instanceAdmin->createUser($admin, $password, '', 1);
            $instanceAdmin->setStatus('protected');
            $instanceAdminData = [
                'display_name' => '',
                'email' => $email,
            ];
            $instanceAdmin->setUserData($instanceAdminData);

            // Add an anonymous user account
            try {
                $clientSetup->createAnonymousUser($configuration);
            } catch (Exception $e) {
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                $response->setData(['error' => $e->getMessage()]);
                return $response;
            }

            Database::setTablePrefix($dbConfig->getPrefix());
        } else {
            $faqInstance->removeInstance($instanceId);
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => 'Cannot create instance.']);
            return $response;
        }
        if (0 !== $instanceId) {
            $response->setStatusCode(Response::HTTP_OK);
            $payload = ['added' => $instanceId, 'url' => $data->getUrl()];
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $payload = ['error' => $instanceId];
        }

        $response->setData($payload);

        return $response;
    }

    #[Route('admin/api/instance/delete')]
    public function delete(Request $request): JsonResponse
    {
        $response = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();

        $data = json_decode($request->getContent());

        if (!Token::getInstance()->verifyToken('delete-instance', $data->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            return $response;
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
                $response->setStatusCode(Response::HTTP_OK);
                $payload = ['deleted' => $instanceId];
            } else {
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                $payload = ['error' => $instanceId];
            }
            $response->setData($payload);
        }

        return $response;
    }
}
