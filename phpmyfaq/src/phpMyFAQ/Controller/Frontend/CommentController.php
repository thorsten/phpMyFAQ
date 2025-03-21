<?php

/**
 * The Comment Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-03
 */

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Entity\Comment;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class CommentController extends AbstractController
{
    /**
     * @throws Exception
     * @throws \JsonException
     * @throws \Exception|TransportExceptionInterface
     */
    public function create(Request $request): JsonResponse
    {
        $faq = $this->container->get('phpmyfaq.faq');
        $comment = $this->container->get('phpmyfaq.comments');
        $stopWords = $this->container->get('phpmyfaq.stop-words');
        $session = $this->container->get('phpmyfaq.user.session');
        $session->setCurrentUser($this->currentUser);

        $language = $this->container->get('phpmyfaq.language');
        $languageCode = $language->setLanguage(
            $this->configuration->get('main.languageDetection'),
            $this->configuration->get('main.language')
        );

        if (!$this->isCommentAllowed($this->currentUser)) {
            return $this->json(['error' => Translation::get('ad_msg_noauth')], Response::HTTP_FORBIDDEN);
        }

        if (!$this->captchaCodeIsValid($request)) {
            return $this->json(['error' => Translation::get('msgCaptcha')], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);

        if (
            !Token::getInstance($this->container->get('session'))
                ->verifyToken('add-comment', $data->{'pmf-csrf-token'})
        ) {
            return $this->json(['error' => Translation::get('ad_msg_noauth')], Response::HTTP_UNAUTHORIZED);
        }

        $type = Filter::filterVar($data->type, FILTER_SANITIZE_SPECIAL_CHARS);
        $faqId = Filter::filterVar($data->id ?? null, FILTER_VALIDATE_INT, 0);
        $newsId = Filter::filterVar($data->newsId ?? null, FILTER_VALIDATE_INT);
        $username = Filter::filterVar($data->user, FILTER_SANITIZE_SPECIAL_CHARS);
        $email = Filter::filterVar($data->mail, FILTER_VALIDATE_EMAIL);
        $commentText = Filter::filterVar($data->comment_text, FILTER_SANITIZE_SPECIAL_CHARS);

        switch ($type) {
            case 'news':
                $commentId = $newsId;
                break;
            case 'faq':
                $commentId = $faqId;
                break;
        }

        if (empty($commentId)) {
            return $this->json(['error' => Translation::get('errSaveComment')], Response::HTTP_BAD_REQUEST);
        }

        // Check display name and e-mail address for not logged-in users
        if (!$this->currentUser->isLoggedIn()) {
            $user = $this->container->get('phpmyfaq.user');
            if ($user->checkDisplayName($username) && $user->checkMailAddress($email)) {
                $this->configuration->getLogger()->error('Name and email already used by registered user.');
                return $this->json(['error' => Translation::get('errSaveComment')], Response::HTTP_CONFLICT);
            }
        }

        if (
            !empty($username) && !empty($email) && !empty($commentText) &&
            $stopWords->checkBannedWord($commentText) &&
            $comment->isCommentAllowed($commentId, $languageCode, $type) &&
            $faq->isActive($commentId, $languageCode, $type)
        ) {
            $session->userTracking('save_comment', $commentId);
            $commentEntity = new Comment();
            $commentEntity
                ->setRecordId($commentId)
                ->setType($type)
                ->setUsername($username)
                ->setEmail($email)
                ->setComment(nl2br(strip_tags((string) $commentText)))
                ->setDate($request->server->get('REQUEST_TIME'));

            if ($comment->create($commentEntity)) {
                $notification = $this->container->get('phpmyfaq.notification');
                if ('faq' == $type) {
                    $faq->getFaq($commentId);
                    $notification->sendFaqCommentNotification($faq, $commentEntity);
                } else {
                    $news = $this->container->get('phpmyfaq.news');
                    $newsData = $news->get($commentId);
                    $notification->sendNewsCommentNotification($newsData, $commentEntity);
                }

                return $this->json(['success' => Translation::get('msgCommentThanks')], Response::HTTP_OK);
            } else {
                $session->userTracking('error_save_comment', $commentId);
                return $this->json(['error' => Translation::get('errSaveComment')], Response::HTTP_BAD_REQUEST);
            }
        } else {
            return $this->json(
                ['error' => 'Please add your name, your e-mail address and a comment!'],
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * @throws \Exception
     */
    private function isCommentAllowed(CurrentUser $user): bool
    {
        if (
            !$this->configuration->get('records.allowCommentsForGuests') &&
            !$user->perm->hasPermission($user->getUserId(), PermissionType::COMMENT_ADD->value)
        ) {
            return false;
        }
        return true;
    }
}
