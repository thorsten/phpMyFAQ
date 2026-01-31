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
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-03
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

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
use Symfony\Component\Routing\Attribute\Route;

final class CommentController extends AbstractController
{
    /**
     * @throws Exception
     * @throws \JsonException
     * @throws \Exception|TransportExceptionInterface
     */
    #[Route(path: 'comment/create', name: 'api.private.comment', methods: ['POST'])]
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

        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);

        if (!isset($data->{'pmf-csrf-token'})) {
            throw new Exception('Missing CSRF token');
        }

        if (!Token::getInstance($this->session)->verifyToken(
            page: 'add-comment',
            requestToken: $data->{'pmf-csrf-token'},
        )) {
            throw new Exception('Invalid CSRF token');
        }

        if (!isset($data->user)) {
            throw new Exception('Missing user');
        }

        if (!isset($data->mail)) {
            throw new Exception('Missing email');
        }

        if (!isset($data->comment_text)) {
            throw new Exception('Missing or empty comment text');
        }

        $type = Filter::filterVar($data->type, FILTER_SANITIZE_SPECIAL_CHARS);

        if ($type === 'news') {
            throw new Exception('News comments not supported');
        }

        $faqId = Filter::filterVar($data->id ?? null, FILTER_VALIDATE_INT, default: 0);
        $newsId = Filter::filterVar($data->newsId ?? null, FILTER_VALIDATE_INT);
        $username = Filter::filterVar($data->user, FILTER_SANITIZE_SPECIAL_CHARS);
        $email = Filter::filterVar($data->mail, FILTER_VALIDATE_EMAIL);

        if (!$email) {
            throw new Exception('Invalid email address');
        }

        if (!$this->captchaCodeIsValid($request)) {
            return $this->json(['error' => Translation::get(key: 'msgCaptcha')], Response::HTTP_BAD_REQUEST);
        }

        // Check if user is logged in and editor is enabled
        $enableCommentEditor = (bool) $this->configuration->get('main.enableCommentEditor');
        $isLoggedIn = $this->currentUser->isLoggedIn();

        // Sanitize comment text based on user status and configuration
        if ($enableCommentEditor && $isLoggedIn) {
            // Allow HTML for logged-in users when editor is enabled
            $commentText = $this->sanitizeHtmlComment($data->comment_text);
        } else {
            // Strip all HTML for anonymous users or when editor is disabled
            $commentText = Filter::filterVar($data->comment_text, FILTER_SANITIZE_SPECIAL_CHARS);
        }

        $commentId = match ($type) {
            'news' => (int) $newsId,
            'faq' => (int) $faqId,
            default => 0,
        };

        if ($commentId === 0) {
            return $this->json(['error' => Translation::get(key: 'errSaveComment')], Response::HTTP_BAD_REQUEST);
        }

        // Check display name and e-mail address for not logged-in users
        if (!$this->currentUser->isLoggedIn()) {
            $user = $this->container->get(id: 'phpmyfaq.user');
            if ($user->checkDisplayName($username) && $user->checkMailAddress($email)) {
                $this->configuration->getLogger()->error(message: 'Name and email already used by registered user.');
                return $this->json(['error' => Translation::get(key: 'errSaveComment')], Response::HTTP_CONFLICT);
            }
        }

        if (
            $username !== ''
            && $email !== ''
            && $commentText !== ''
            && $stopWords->checkBannedWord($commentText)
            && $comment->isCommentAllowed($commentId, $languageCode, $type)
            && $faq->isActive($commentId, $languageCode, $type)
        ) {
            $session->userTracking(action: 'save_comment', data: $commentId);
            $commentEntity = new Comment();
            $commentEntity
                ->setRecordId((int) $commentId)
                ->setType($type)
                ->setUsername($username)
                ->setEmail($email)
                ->setComment(
                    $enableCommentEditor && $isLoggedIn
                        ? (string) $commentText
                        : nl2br(strip_tags((string) $commentText)),
                ) // Already sanitized with HTML support // Plain text with line breaks
                ->setDate((string) $request->server->get(key: 'REQUEST_TIME'));

            if ($comment->create($commentEntity)) {
                $notification = $this->container->get(id: 'phpmyfaq.notification');
                if ('faq' === $type) {
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

            $session->userTracking(action: 'error_save_comment', data: $commentId);
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

    /**
     * Sanitizes HTML comment text, allowing only safe tags and attributes
     *
     * @param string $html Raw HTML content from editor
     * @return string Sanitized HTML with only allowed tags
     */
    private function sanitizeHtmlComment(string $html): string
    {
        // Allowed HTML tags for comments
        $allowedTags = '<p><br><strong><em><u><s><a><ul><ol><li>';

        // Strip all tags except allowed ones
        $sanitized = strip_tags($html, $allowedTags);

        // Additional sanitization for <a> tags - only allow specific attributes
        $sanitized = preg_replace_callback(
            '/<a\s+([^>]*)>/i',
            static function ($matches) {
                $attributes = $matches[1];
                $allowedAttrs = [];

                // Extract and validate href
                if (preg_match('/href\s*=\s*["\']([^"\']*)["\']/', $attributes, $hrefMatch)) {
                    $href = htmlspecialchars($hrefMatch[1], ENT_QUOTES, 'UTF-8');
                    // Only allow http, https, and mailto protocols
                    if (preg_match('/^(https?:\/\/|mailto:)/i', $href) || preg_match('/^\/[^\/]/', $href)) {
                        $allowedAttrs[] = 'href="' . $href . '"';
                    }
                }

                // Extract and validate title
                if (preg_match('/title\s*=\s*["\']([^"\']*)["\']/', $attributes, $titleMatch)) {
                    $title = htmlspecialchars($titleMatch[1], ENT_QUOTES, 'UTF-8');
                    $allowedAttrs[] = 'title="' . $title . '"';
                }

                // Extract and validate target
                if (preg_match('/target\s*=\s*["\']([^"\']*)["\']/', $attributes, $targetMatch)) {
                    $target = $targetMatch[1];
                    if (in_array($target, ['_blank', '_self', '_parent', '_top'])) {
                        $allowedAttrs[] = 'target="' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '"';
                    }
                }

                return empty($allowedAttrs) ? '<a>' : '<a ' . implode(' ', $allowedAttrs) . '>';
            },
            $sanitized,
        );

        // Remove any remaining dangerous attributes from other tags
        return preg_replace('/<(\w+)\s+[^>]*?(on\w+|style|class|id)\s*=\s*[^>]*>/i', '<$1>', $sanitized);
    }
}
