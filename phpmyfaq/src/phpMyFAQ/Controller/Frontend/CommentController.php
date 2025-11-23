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

declare(strict_types=1);

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

final class CommentController extends AbstractController
{
    /**
     * @throws Exception
     * @throws \JsonException
     * @throws \Exception|TransportExceptionInterface
     */
    public function create(Request $request): JsonResponse
    {
        $faq = $this->container->get(id: 'phpmyfaq.faq');
        $comment = $this->container->get(id: 'phpmyfaq.comments');
        $stopWords = $this->container->get(id: 'phpmyfaq.stop-words');
        $session = $this->container->get(id: 'phpmyfaq.user.session');
        $session->setCurrentUser($this->currentUser);

        $language = $this->container->get(id: 'phpmyfaq.language');
        $languageCode = $this->configuration->get(item: 'main.languageDetection')
            ? $language->setLanguageWithDetection($this->configuration->get(item: 'main.language'))
            : $language->setLanguageFromConfiguration($this->configuration->get(item: 'main.language'));

        if (!$this->isCommentAllowed($this->currentUser)) {
            return $this->json(['error' => Translation::get(key: 'ad_msg_noauth')], Response::HTTP_FORBIDDEN);
        }

        if (!$this->captchaCodeIsValid($request)) {
            return $this->json(['error' => Translation::get(key: 'msgCaptcha')], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);

        if (!Token::getInstance($this->container->get(id: 'session'))->verifyToken(
            'add-comment',
            $data->{'pmf-csrf-token'},
        )) {
            return $this->json(['error' => Translation::get(key: 'ad_msg_noauth')], Response::HTTP_UNAUTHORIZED);
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
            return $this->json(['error' => Translation::get(key: 'errSaveComment')], Response::HTTP_BAD_REQUEST);
        }

        // Check display name and e-mail address for not logged-in users
        if (!$this->currentUser->isLoggedIn()) {
            $user = $this->container->get(id: 'phpmyfaq.user');
            if ($user->checkDisplayName($username) && $user->checkMailAddress($email)) {
                $this->configuration->getLogger()->error('Name and email already used by registered user.');
                return $this->json(['error' => Translation::get(key: 'errSaveComment')], Response::HTTP_CONFLICT);
            }
        }

        if (
            !empty($username)
            && !empty($email)
            && !empty($commentText)
            && $stopWords->checkBannedWord($commentText)
            && $comment->isCommentAllowed($commentId, $languageCode, $type)
            && $faq->isActive($commentId, $languageCode, $type)
        ) {
            $session->userTracking('save_comment', $commentId);
            $commentEntity = new Comment();
            $commentEntity
                ->setRecordId((int) $commentId)
                ->setType($type)
                ->setUsername($username)
                ->setEmail($email)
                ->setComment(nl2br(strip_tags((string) $commentText)))
                ->setDate((string) $request->server->get('REQUEST_TIME'));

            if ($comment->create($commentEntity)) {
                $notification = $this->container->get(id: 'phpmyfaq.notification');
                if ('faq' == $type) {
                    $faq->getFaq($commentId);
                    $notification->sendFaqCommentNotification($faq, $commentEntity);
                } else {
                    $news = $this->container->get(id: 'phpmyfaq.news');
                    $newsData = $news->get($commentId);
                    $notification->sendNewsCommentNotification($newsData, $commentEntity);
                }

                $gravatar = $this->container->get(id: 'phpmyfaq.services.gravatar');
                $gravatarUrl = $gravatar->getImageUrl($commentEntity->getEmail(), ['size' => 50, 'default' => 'mm']);

                return $this->json([
                    'success' => Translation::get(key: 'msgCommentThanks'),
                    'commentData' => [
                        'username' => $commentEntity->getUsername(),
                        'email' => $commentEntity->getEmail(),
                        'comment' => $commentEntity->getComment(),
                        'date' => $commentEntity->getDate(),
                        'gravatarUrl' => $gravatarUrl,
                    ],
                ], Response::HTTP_OK);
            }

            $session->userTracking('error_save_comment', $commentId);
            return $this->json(['error' => Translation::get(key: 'errSaveComment')], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'error' => 'Please add your name, your e-mail address and a comment!',
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws \Exception
     */
    private function isCommentAllowed(CurrentUser $currentUser): bool
    {
        return !(
            !$this->configuration->get(item: 'records.allowCommentsForGuests')
            && !$currentUser->perm->hasPermission($currentUser->getUserId(), PermissionType::COMMENT_ADD->value)
        );
    }
}
