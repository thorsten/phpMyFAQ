<?php

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class FaqController extends AbstractController
{
    public function create(): JsonResponse
    {
        return $this->json(['status' => 'ok']);
    }
}
