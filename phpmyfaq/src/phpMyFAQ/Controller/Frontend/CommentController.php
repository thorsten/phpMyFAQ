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
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-03
 */

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Category;
use phpMyFAQ\Comments;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Entity\Comment;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Language;
use phpMyFAQ\Link;
use phpMyFAQ\Mail;
use phpMyFAQ\News;
use phpMyFAQ\Session;
use phpMyFAQ\Session\Token;
use phpMyFAQ\StopWords;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
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
        $user = CurrentUser::getCurrentUser($this->configuration);

        $faq = new Faq($this->configuration);
        $comment = new Comments($this->configuration);
        $category = new Category($this->configuration);
        $stopWords = new StopWords($this->configuration);
        $session = new Session($this->configuration);
        $session->setCurrentUser($user);

        $language = new Language($this->configuration);
        $languageCode = $language->setLanguage(
            $this->configuration->get('main.languageDetection'),
            $this->configuration->get('main.language')
        );

        if (!$this->isCommentAllowed($user)) {
            return $this->json(['error' => Translation::get('msgNotAllowed')], Response::HTTP_FORBIDDEN);
        }

        if (!$this->captchaCodeIsValid($request)) {
            return $this->json(['error' => Translation::get('msgCaptcha')], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);

        if (!Token::getInstance()->verifyToken('add-comment', $data->{'pmf-csrf-token'})) {
            return $this->json(['error' => Translation::get('ad_msg_noauth')], Response::HTTP_UNAUTHORIZED);
        }

        $type = Filter::filterVar($data->type, FILTER_SANITIZE_SPECIAL_CHARS);
        $faqId = Filter::filterVar($data->id ?? null, FILTER_VALIDATE_INT, 0);
        $newsId = Filter::filterVar($data->newsId ?? null, FILTER_VALIDATE_INT);
        $username = Filter::filterVar($data->user, FILTER_SANITIZE_SPECIAL_CHARS);
        $mailer = Filter::filterVar($data->mail, FILTER_VALIDATE_EMAIL);
        $commentText = Filter::filterVar($data->comment_text, FILTER_SANITIZE_SPECIAL_CHARS);

        switch ($type) {
            case 'news':
                $id = $newsId;
                break;
            case 'faq':
                $id = $faqId;
                break;
        }

        if (empty($id)) {
            return $this->json(['error' => Translation::get('errSaveComment')], Response::HTTP_BAD_REQUEST);
        }

        // If e-mail address is set to optional
        if (!$this->configuration->get('main.optionalMailAddress') && empty($mailer)) {
            $mailer = $this->configuration->getAdminEmail();
        }

        // Check display name and e-mail address for not logged-in users
        if (!$user->isLoggedIn()) {
            $user = new User($this->configuration);
            if ($user->checkDisplayName($username) && $user->checkMailAddress($mailer)) {
                $this->configuration->getLogger()->error('Name and email already used by registered user.');
                return $this->json(['error' => Translation::get('errSaveComment')], Response::HTTP_CONFLICT);
            }
        }

        if (
            !empty($username) && !empty($mailer) && !empty($commentText) && $stopWords->checkBannedWord($commentText) &&
            !$faq->commentDisabled($id, $languageCode, $type) && !$faq->isActive($id, $languageCode, $type)
        ) {
            try {
                $session->userTracking('save_comment', $id);
            } catch (Exception $exception) {
                $this->configuration->getLogger()->error(
                    'Tracking of save new comment',
                    ['exception' => $exception->getMessage()]
                );
            }

            $commentEntity = new Comment();
            $commentEntity
                ->setRecordId($id)
                ->setType($type)
                ->setUsername($username)
                ->setEmail($mailer)
                ->setComment(nl2br(strip_tags((string) $commentText)))
                ->setDate($request->server->get('REQUEST_TIME'));

            if ($comment->addComment($commentEntity)) {
                $emailTo = $this->configuration->getAdminEmail();
                if ('faq' == $type) {
                    $faq->getRecord($id);
                    if ($faq->faqRecord['email'] != '') {
                        $emailTo = $faq->faqRecord['email'];
                    }

                    $title = $faq->getRecordTitle($id);

                    $faqUrl = sprintf(
                        '%s?action=faq&cat=%d&id=%d&artlang=%s',
                        $this->configuration->getDefaultUrl(),
                        $category->getCategoryIdFromFaq($faq->faqRecord['id']),
                        $faq->faqRecord['id'],
                        $faq->faqRecord['lang']
                    );
                    $link = new Link($faqUrl, $this->configuration);
                    $link->itemTitle = $faq->faqRecord['title'];
                } else {
                    $news = new News($this->configuration);
                    $newsData = $news->getNewsEntry($id);
                    if ($newsData['authorEmail'] != '') {
                        $emailTo = $newsData['authorEmail'];
                    }

                    $title = $newsData['header'];

                    $newsUrl = sprintf(
                        '%s?action=news&newsid=%d&newslang=%s',
                        $this->configuration->getDefaultUrl(),
                        $newsData['id'],
                        $newsData['lang']
                    );
                    $link = new Link($newsUrl, $this->configuration);
                    $link->itemTitle = $newsData['header'];
                }
                $urlToContent = $link->toString();

                $commentMail =
                    sprintf('User: %s, mailto:%s<br>', $commentEntity->getUsername(), $commentEntity->getEmail()) .
                    sprintf('Title: %s<br>', $title) .
                    sprintf('New comment posted here: %s<br><br>', $urlToContent) .
                    sprintf('%s', wordwrap((string) $commentText, 72));

                $send = [];
                $mailer = new Mail($this->configuration);
                $mailer->setReplyTo($commentEntity->getEmail(), $commentEntity->getUsername());
                $mailer->addTo($emailTo);

                $send[$emailTo] = 1;
                $send[$this->configuration->getAdminEmail()] = 1;

                if ($type === CommentType::FAQ) {
                    // Let the category owner of a FAQ get a copy of the message
                    $category = new Category($this->configuration);
                    $categories = $category->getCategoryIdsFromFaq($faq->faqRecord['id']);
                    foreach ($categories as $_category) {
                        $userId = $category->getOwner($_category);
                        $catUser = new User($this->configuration);
                        $catUser->getUserById($userId);
                        $catOwnerEmail = $catUser->getUserData('email');

                        if ($catOwnerEmail !== '' && (!isset($send[$catOwnerEmail]) && $catOwnerEmail !== $emailTo)) {
                            $mailer->addCc($catOwnerEmail);
                            $send[$catOwnerEmail] = 1;
                        }
                    }
                }

                $mailer->subject = $this->configuration->getTitle() . ': New comment for "' . $title . '"';
                $mailer->message = strip_tags($commentMail);

                $mailer->send();
                unset($mailer);

                return $this->json(['success' => Translation::get('msgCommentThanks')], Response::HTTP_OK);
            } else {
                try {
                    $session->userTracking('error_save_comment', $id);
                } catch (Exception) {
                    // @todo handle the exception
                }
                return $this->json(['error' => Translation::get('errSaveComment')], Response::HTTP_BAD_REQUEST);
            }
        } else {
            return $this->json(
                ['error' => 'Please add your name, your e-mail address and a comment!'],
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    private function isCommentAllowed(CurrentUser $user): bool
    {
        if (
            !$this->configuration->get('records.allowCommentsForGuests') &&
            !$user->perm->hasPermission($user->getUserId(), 'addcomment')
        ) {
            return false;
        }
        return true;
    }
}
