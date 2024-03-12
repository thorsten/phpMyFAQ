<?php

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class QuestionController extends AbstractController
{
    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);

        if (!$this->captchaCodeIsValid($data->captcha)) {
            return $this->json(['error' => Translation::get('msgCaptcha')], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['status' => 'ok']);
    }
}
