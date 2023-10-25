<?php

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Comments;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CommentController extends Controller
{
    #[Route('admin/api/content/comments')]
    public function delete(Request $request): JsonResponse
    {
        $response = new JsonResponse();
        $data = json_decode($request->getContent());

        if (!Token::getInstance()->verifyToken('delete-comment', $data->data->{'pmf-csrf-token'})) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            $response->send();
        }

        $comment = new Comments(Configuration::getConfigurationInstance());
        $commentIds = $data->data->{'comments[]'} ?? [];

        $result = false;
        if (!is_null($commentIds)) {
            if (!is_array($commentIds)) {
                $commentIds = [$commentIds];
            }
            foreach ($commentIds as $commentId) {
                $result = $comment->delete($data->type, $commentId);
            }

            $response->setStatusCode(Response::HTTP_OK);
            $response->setData(['success' => $result]);
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => false]);
        }

        return $response;
    }
}
