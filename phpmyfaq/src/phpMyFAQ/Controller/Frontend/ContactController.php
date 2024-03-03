<?php

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class ContactController extends AbstractController
{
    public function create(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }
}
