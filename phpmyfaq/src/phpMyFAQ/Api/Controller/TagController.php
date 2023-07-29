<?php

namespace phpMyFAQ\Api\Controller;

use phpMyFAQ\Configuration;
use phpMyFAQ\Tags;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TagController
{
    public function list(): JsonResponse
    {
        $response = new JsonResponse();
        $faqConfig = Configuration::getConfigurationInstance();

        $tags = new Tags($faqConfig);
        $result = $tags->getPopularTagsAsArray(16);
        if ((is_countable($result) ? count($result) : 0) === 0) {
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
        }
        $response->setData($result);

        return $response;
    }
}
