<?php

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Controller;
use phpMyFAQ\Filter;
use phpMyFAQ\Language;
use phpMyFAQ\Session\Token;
use phpMyFAQ\StopWords;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StopWordController extends Controller
{
    #[Route('admin/api/stopwords')]
    public function list(Request $request): JsonResponse
    {
        $response = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();
        $stopWords = new StopWords($configuration);

        $language = Filter::filterVar($request->query->get('language'), FILTER_SANITIZE_SPECIAL_CHARS);

        if (Language::isASupportedLanguage($language)) {
            $stopWordsList = $stopWords->getByLang($language);
            $response->setStatusCode(Response::HTTP_OK);
            $response->setData($stopWordsList);
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => 'Language not supported']);
        }

        return $response;
    }

    #[Route('admin/api/stopword/delete')]
    public function delete(Request $request): JsonResponse
    {
        $response = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();
        $stopWords = new StopWords($configuration);

        $data = json_decode($request->getContent());

        $stopWordId = Filter::filterVar($data->stopWordId, FILTER_VALIDATE_INT);
        $stopWordsLang = Filter::filterVar($data->stopWordsLang, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance()->verifyToken('stopwords', $data->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            return $response;
        }

        if (null != $stopWordId && Language::isASupportedLanguage($stopWordsLang)) {
            $stopWords
                ->setLanguage($stopWordsLang)
                ->remove((int)$stopWordId);
            $response->setStatusCode(Response::HTTP_OK);
            $response->setData(['deleted' => $stopWordId ]);
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => 'Language not supported']);
        }

        return $response;
    }

    #[Route('admin/api/stopword/save')]
    public function save(Request $request): JsonResponse
    {
        $response = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();
        $stopWords = new StopWords($configuration);

        $data = json_decode($request->getContent());

        $stopWordId = Filter::filterVar($data->stopWordId, FILTER_VALIDATE_INT);
        $stopWordsLang = Filter::filterVar($data->stopWordsLang, FILTER_SANITIZE_SPECIAL_CHARS);
        $stopWord = Filter::filterVar($data->stopWord, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance()->verifyToken('stopwords', $data->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            return $response;
        }

        if (null != $stopWord && Language::isASupportedLanguage($stopWordsLang)) {
            $stopWords->setLanguage($stopWordsLang);

            if (null !== $stopWordId && -1 < $stopWordId) {
                $stopWords->update((int)$stopWordId, $stopWord);
                $response->setStatusCode(Response::HTTP_OK);
                $response->setData(['updated' => $stopWordId ]);
            } elseif (!$stopWords->match($stopWord)) {
                $stopWordId = $stopWords->add($stopWord);
                $response->setStatusCode(Response::HTTP_OK);
                $response->setData(['added' => $stopWordId ]);
            }
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => 'Language not supported']);
        }

        return $response;
    }
}
