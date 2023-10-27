<?php

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Controller;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Mail;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;

class ConfigurationController extends Controller
{
    #[Route('admin/api/configuration/send-test-mail')]
    public function sendTestMail(Request $request): JsonResponse
    {
        $response = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();

        $data = json_decode($request->getContent());

        if (!Token::getInstance()->verifyToken('configuration', $data->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            return $response;
        }

        try {
            $mailer = new Mail($configuration);
            $mailer->setReplyTo($configuration->getAdminEmail());
            $mailer->addTo($configuration->getAdminEmail());
            $mailer->subject = $configuration->getTitle() . ': Mail test successful.';
            $mailer->message = 'It works on my machine. 🚀';
            $result = $mailer->send();

            $response->setStatusCode(Response::HTTP_OK);
            $response->setData(['success' => $result]);
        } catch (Exception | TransportExceptionInterface $e) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => $e->getMessage()]);
        }

        return $response;
    }
}
