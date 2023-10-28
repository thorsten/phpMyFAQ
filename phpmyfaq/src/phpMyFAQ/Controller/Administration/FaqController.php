<?php

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Controller;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\FaqPermission;
use phpMyFAQ\Filter;
use phpMyFAQ\Language;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FaqController extends Controller
{
    #[Route('admin/api/faq/permissions')]
    public function listPermissions(Request $request): JsonResponse
    {
        $response = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();

        $faqId = Filter::filterVar($request->get('faqId'), FILTER_VALIDATE_INT);

        $faqPermission = new FaqPermission($configuration);

        $response->setStatusCode(Response::HTTP_OK);
        $response->setData(
            [
                'user' => $faqPermission->get(FaqPermission::USER, $faqId),
                'group' => $faqPermission->get(FaqPermission::GROUP, $faqId)
            ]
        );

        return $response;
    }

    #[Route('admin/api/faq/activate')]
    public function activate(Request $request): JsonResponse
    {
        $this->userHasPermission('approverec');

        $response = new JsonResponse();

        $data = json_decode($request->getContent());

        $faqIds = Filter::filterArray($data->faqIds);
        $faqLanguage = Filter::filterVar($data->faqLanguage, FILTER_SANITIZE_SPECIAL_CHARS);
        $checked = Filter::filterVar($data->checked, FILTER_VALIDATE_BOOLEAN);

        if (!Token::getInstance()->verifyToken('faq-overview', $data->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            return $response;
        }

        if (!empty($faqIds)) {
            $faq = new Faq(Configuration::getConfigurationInstance());
            $success = false;

            foreach ($faqIds as $faqId) {
                if (Language::isASupportedLanguage($faqLanguage)) {
                    $success = $faq->updateRecordFlag($faqId, $faqLanguage, $checked ?? false, 'active');
                }
            }

            $response->setStatusCode(Response::HTTP_OK);
            $response->setData(['success' => $success]);
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => 'No FAQ IDs provided.']);
        }

        return $response;
    }
}
