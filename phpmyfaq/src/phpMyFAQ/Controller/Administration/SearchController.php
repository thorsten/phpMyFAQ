<?php

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Controller;
use phpMyFAQ\Filter;
use phpMyFAQ\Search;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends Controller
{
    #[Route('admin/api/search/term')]
    public function deleteTerm(Request $request): JsonResponse
    {
        $response = new JsonResponse();
        $deleteData = json_decode($request->getContent());

        $search = new Search(Configuration::getConfigurationInstance());

        if (!Token::getInstance()->verifyToken('delete-searchterm', $deleteData->csrf)) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            return $response;
        }

        $searchId = Filter::filterVar($deleteData->searchTermId, FILTER_VALIDATE_INT);

        if ($search->deleteSearchTermById($searchId)) {
            $response->setStatusCode(Response::HTTP_OK);
            $response->setData(['deleted' => $searchId]);
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => $searchId]);
        }

        return $response;
    }
}
