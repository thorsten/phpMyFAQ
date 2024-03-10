<?php

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\RegistrationHelper;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class RegistrationController extends AbstractController
{
    /**
     * @throws \JsonException
     */
    public function create(Request $request): JsonResponse
    {
        $configuration = Configuration::getConfigurationInstance();
        $registration = new RegistrationHelper($configuration);

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);

        $fullName = trim((string) Filter::filterVar($data->realname, FILTER_SANITIZE_SPECIAL_CHARS));
        $userName = trim((string) Filter::filterVar($data->name, FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim((string) Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL));
        $isVisible = Filter::filterVar($data->isVisible, FILTER_SANITIZE_SPECIAL_CHARS) ?? false;

        if (!$registration->isDomainAllowed($email)) {
            return $this->json(['error' => 'The domain is not whitelisted.'], Response::HTTP_BAD_REQUEST);
        }

        if (!empty($userName) && !empty($email) && !empty($fullName)) {
            try {
                return $this->json(
                    $registration->createUser($userName, $fullName, $email, $isVisible),
                    Response::HTTP_CREATED
                );
            } catch (Exception | TransportExceptionInterface $exception) {
                return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        } else {
            return $this->json(['error' => Translation::get('err_sendMail')], Response::HTTP_BAD_REQUEST);
        }
    }
}
