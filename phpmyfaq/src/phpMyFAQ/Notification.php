<?php

/**
 * The notification class for phpMyFAQ.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-08-30
 */

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Entity\Comment;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Entity\QuestionEntity;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Class Notification
 *
 * @package phpMyFAQ
 */
readonly class Notification
{
    private Mail $mail;

    private Faq $faq;

    private Category $category;

    /**
     * Constructor.
     *
     * @throws Core\Exception
     */
    public function __construct(private Configuration $configuration)
    {
        $this->mail = new Mail($this->configuration);
        $this->faq = new Faq($this->configuration);
        $this->category = new Category($this->configuration);
        $this->mail->setReplyTo(
            $this->configuration->getNoReplyEmail(),
            $this->configuration->getTitle()
        );
    }

    /**
     * Sends mail to user who added a question.
     *
     * @param string $email Email address of the user
     * @param string $userName Name of the user
     * @param string $url URL of answered FAQ
     * @throws Core\Exception|TransportExceptionInterface
     */
    public function sendOpenQuestionAnswered(string $email, string $userName, string $url): void
    {
        if ($this->configuration->get('main.enableNotifications')) {
            $this->mail->addTo($email, $userName);
            $this->mail->subject = $this->configuration->getTitle() . ' - ' . Translation::get('msgQuestionAnswered');
            $this->mail->message = sprintf(
                Translation::get('msgMessageQuestionAnswered'),
                $this->configuration->getTitle()
            ) . "\n\r" . $url;
            $this->mail->send();
        }
    }

    /**
     * Sends mails to FAQ admin and other given users about a newly added FAQ.
     *
     * @param array<string> $emails
     * @throws Core\Exception|TransportExceptionInterface
     */
    public function sendNewFaqAdded(array $emails, FaqEntity $faq): void
    {
        if ($this->configuration->get('main.enableNotifications')) {
            $this->mail->addTo($this->configuration->getAdminEmail());
            foreach ($emails as $email) {
                if ($email !== $this->configuration->getAdminEmail()) {
                    $this->mail->addCc($email);
                }
            }

            $this->mail->subject = $this->configuration->getTitle() . ': New FAQ was added.';
            $this->faq->getRecord($faq->getId(), null, true);

            $url = sprintf(
                '%sadmin/?action=editentry&id=%d&lang=%s',
                $this->configuration->getDefaultUrl(),
                $faq->getId(),
                $faq->getLanguage()
            );
            $link = new Link($url, $this->configuration);
            $link->itemTitle = $this->faq->getQuestion($faq->getId());

            $this->mail->message = html_entity_decode((string) Translation::get('msgMailCheck')) .
                "<p><strong>" . Translation::get('msgAskYourQuestion') . ":</strong> " .
                $this->faq->getQuestion($faq->getId()) . "</p>" .
                "<p><strong>" . Translation::get('msgNewContentArticle') . ":</strong> " .
                $this->faq->faqRecord['content'] . "</p>" .
                "<hr>" .
                $this->configuration->getTitle() .
                ': <a target="_blank" href="' . $link->toString() . '">' . $link->toString() . '</a>';

            $this->mail->contentType = 'text/html';

            $this->mail->send();
        }
    }

    /**
     * Sends mail to user who added a comment.
     * @param Faq $faq
     * @param Comment $comment
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function sendFaqCommentNotification(Faq $faq, Comment $comment): void
    {
        $category = new Category($this->configuration);
        $emailTo = $this->configuration->getAdminEmail();

        if ($faq->faqRecord['email'] !== '') {
            $emailTo = $faq->faqRecord['email'];
        }

        $title = $faq->faqRecord['title'];

        $faqUrl = sprintf(
            '%s?action=faq&cat=%d&id=%d&artlang=%s',
            $this->configuration->getDefaultUrl(),
            $category->getCategoryIdFromFaq($faq->faqRecord['id']),
            $faq->faqRecord['id'],
            $faq->faqRecord['lang']
        );
        $link = new Link($faqUrl, $this->configuration);
        $link->itemTitle = $faq->faqRecord['title'];

        $urlToContent = $link->toString();

        $commentMail =
            sprintf('User: %s, mailto:%s<br>', $comment->getUsername(), $comment->getEmail()) .
            sprintf('Title: %s<br>', $title) .
            sprintf('New comment posted here: %s<br><br>', $urlToContent) .
            sprintf('%s', wordwrap($comment->getComment(), 72));

        $send = [];

        $this->mail->setReplyTo($comment->getEmail(), $comment->getUsername());
        $this->mail->addTo($emailTo);

        $send[$emailTo] = 1;
        $send[$this->configuration->getAdminEmail()] = 1;

        // Let the category owner of a FAQ get a copy of the message
        $category = new Category($this->configuration);
        $categories = $category->getCategoryIdsFromFaq($faq->faqRecord['id']);
        foreach ($categories as $_category) {
            $userId = $category->getOwner($_category);
            $catUser = new User($this->configuration);
            $catUser->getUserById($userId);
            $catOwnerEmail = $catUser->getUserData('email');

            if ($catOwnerEmail !== '' && (!isset($send[$catOwnerEmail]) && $catOwnerEmail !== $emailTo)) {
                $this->mail->addCc($catOwnerEmail);
                $send[$catOwnerEmail] = 1;
            }
        }

        $this->mail->subject = $this->configuration->getTitle() . ': New comment for "' . $title . '"';
        $this->mail->message = strip_tags($commentMail);

        $this->mail->send();
    }

    /**
     * @param array $newsData
     * @param Comment $comment
     * @return void
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function sendNewsCommentNotification(array $newsData, Comment $comment): void
    {
        if ($newsData['authorEmail'] != '') {
            $this->mail->addTo($newsData['authorEmail']);
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
        $urlToContent = $link->toString();

        $commentMail =
            sprintf('User: %s, mailto:%s<br>', $comment->getUsername(), $comment->getEmail()) .
            sprintf('Title: %s<br>', $title) .
            sprintf('New comment posted here: %s<br><br>', $urlToContent) .
            sprintf('%s', wordwrap($comment->getComment(), 72));

        $this->mail->setReplyTo($comment->getEmail(), $comment->getUsername());

        $send = [];
        $send[$this->configuration->getAdminEmail()] = 1;

        $this->mail->subject = $this->configuration->getTitle() . ': New comment for "' . $title . '"';
        $this->mail->message = strip_tags($commentMail);

        $this->mail->send();
    }

    public function sendQuestionSuccessMail(QuestionEntity $questionData, array $categories): void
    {
        $questionMail = sprintf(
            "%s<br><br>User: %s, %s<br>%s: %s<br><br>%s: %s<br><br>%s",
            Translation::get('msgNewQuestionAdded'),
            $questionData->getUsername(),
            $questionData->getEmail(),
            Translation::get('msgCategory'),
            $categories[$questionData->getCategoryId()]['name'],
            Translation::get('msgAskYourQuestion'),
            wordwrap($questionData->getQuestion(), 72),
            $this->configuration->getDefaultUrl() . 'admin/'
        );

        $userId = $this->category->getOwner($questionData->getCategoryId());
        try {
            $oUser = new User($this->configuration);
            $oUser->getUserById($userId);
            $userEmail = $oUser->getUserData('email');
        } catch (Exception $e) {
            $this->configuration->getLogger()->error('Error getting user data: ' . $e->getMessage());
            $userEmail = null;
        }

        $mainAdminEmail = $this->configuration->getAdminEmail();

        try {
            $mail = new Mail($this->configuration);
            $mail->setReplyTo($questionData->getEmail(), $questionData->getUsername());
            $mail->addTo($mainAdminEmail);

            // Let the category owner get a copy of the message
            if (!empty($userEmail) && $mainAdminEmail != $userEmail) {
                $mail->addCc($userEmail);
            }

            $mail->subject = $this->configuration->getTitle() . ': New Question was added.';
            $mail->message = $questionMail;
            $mail->send();
            unset($mail);
        } catch (Exception | TransportExceptionInterface $exception) {
            $this->configuration->getLogger()->error('Error sending mail: ' . $exception->getMessage());
        }
    }
}
