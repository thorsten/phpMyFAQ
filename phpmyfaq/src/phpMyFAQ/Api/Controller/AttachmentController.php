<?php

namespace phpMyFAQ\Api\Controller;

use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Configuration;
use phpMyFAQ\Filter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AttachmentController
{
    public function list(Request $request): JsonResponse
    {
        $response = new JsonResponse();
        $faqConfig = Configuration::getConfigurationInstance();

        $recordId = Filter::filterVar($request->get('recordId'), FILTER_VALIDATE_INT);

        $attachments = $result = [];
        try {
            $attachments = AttachmentFactory::fetchByRecordId($faqConfig, $recordId);
        } catch (AttachmentException) {
            $result = [];
        }
        foreach ($attachments as $attachment) {
            $result[] = [
                'filename' => $attachment->getFilename(),
                'url' => $faqConfig->getDefaultUrl() . $attachment->buildUrl(),
            ];
        }
        if (count($result) === 0) {
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
        }
        $response->setData($result);

        return $response;
    }
}
