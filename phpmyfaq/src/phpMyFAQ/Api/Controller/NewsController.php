<?php

namespace phpMyFAQ\Api\Controller;

use phpMyFAQ\Configuration;
use phpMyFAQ\News;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class NewsController
{
    public function list(): JsonResponse
    {
        $response = new JsonResponse();
        $faqConfig = Configuration::getConfigurationInstance();

        $news = new News($faqConfig);
        $result = $news->getLatestData(false, true, true);
        if ((is_countable($result) ? count($result) : 0) === 0) {
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
        }
        $response->setData($result);

        return $response;
    }
}
