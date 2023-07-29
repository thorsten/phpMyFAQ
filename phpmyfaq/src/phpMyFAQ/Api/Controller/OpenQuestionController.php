<?php

namespace phpMyFAQ\Api\Controller;

use phpMyFAQ\Configuration;
use phpMyFAQ\Question;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class OpenQuestionController
{
    public function list(): JsonResponse
    {
        $response = new JsonResponse();
        $faqConfig = Configuration::getConfigurationInstance();

        $questions = new Question($faqConfig);
        $result = $questions->getAllOpenQuestions();
        if ((is_countable($result) ? count($result) : 0) === 0) {
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
        }
        $response->setData($result);

        return $response;
    }
}
