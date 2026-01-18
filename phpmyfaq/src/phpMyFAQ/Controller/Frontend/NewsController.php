<?php

/**
 * News Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2006-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-07-23
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Captcha\Helper\CaptchaHelper;
use phpMyFAQ\Comments;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Filter;
use phpMyFAQ\News\NewsService;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\Utils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NewsController extends AbstractFrontController
{
    /**
     * Displays a news article with comments.
     *
     * @throws Exception
     * @throws \Exception
     */
    #[Route(
        path: '/news/{newsId}/{newsLang}/{slug}.html',
        name: 'public.news',
        requirements: [
            'newsId' => '\d+',
            'newsLang' => '[a-z\-_]+',
        ],
        methods: ['GET'],
    )]
    public function index(Request $request): Response
    {
        $newsId = Filter::filterVar($request->attributes->get('newsId'), FILTER_VALIDATE_INT);

        if ($newsId === false || $newsId === null) {
            return $this->render('404.twig', [
                ...$this->getHeader($request),
            ]);
        }

        $faqSession = $this->container->get('phpmyfaq.user.session');
        $faqSession->setCurrentUser($this->currentUser);
        $faqSession->userTracking('news_view', $newsId);

        $newsService = new NewsService($this->configuration, $this->currentUser);
        $news = $newsService->getProcessedNews($newsId);

        $captcha = $this->container->get('phpmyfaq.captcha');
        $captchaHelper = CaptchaHelper::getInstance($this->configuration);

        $comment = new Comments($this->configuration);
        $comments = $comment->getCommentsData($newsId, CommentType::NEWS);

        $session = $this->container->get('session');

        return $this->render('news.twig', [
            ...$this->getHeader($request),
            'writeNewsHeader' => $this->configuration->getTitle() . Translation::get(key: 'msgNews'),
            'newsHeader' => $news['processedHeader'],
            'mainPageContent' => $news['processedContent'],
            'writeDateMsg' => $newsService->formatNewsDate($news),
            'msgAboutThisNews' => Translation::get(key: 'msgAboutThisNews'),
            'writeAuthor' => $newsService->getAuthorInfo($news),
            'editThisEntry' => $newsService->getEditLink($newsId),
            'writeCommentMsg' => $newsService->getCommentMessage($news),
            'msgWriteComment' => Translation::get(key: 'newsWriteComment'),
            'newsId' => $newsId,
            'newsLang' => $news['lang'],
            'msgCommentHeader' => Translation::get(key: 'msgCommentHeader'),
            'msgNewContentName' => Translation::get(key: 'msgNewContentName'),
            'msgNewContentMail' => Translation::get(key: 'msgNewContentMail'),
            'defaultContentMail' => $this->currentUser->getUserId() > 0 ? $this->currentUser->getUserData('email') : '',
            'defaultContentName' => $this->currentUser->getUserId() > 0
                ? $this->currentUser->getUserData('display_name')
                : '',
            'msgYourComment' => Translation::get(key: 'msgYourComment'),
            'csrfInput' => Token::getInstance($session)->getTokenInput('add-comment'),
            'msgCancel' => Translation::get(key: 'ad_gen_cancel'),
            'msgNewContentSubmit' => Translation::get(key: 'msgNewContentSubmit'),
            'captchaFieldset' => $captchaHelper->renderCaptcha(
                $captcha,
                'writecomment',
                Translation::get(key: 'msgCaptcha'),
                $this->currentUser->isLoggedIn(),
            ),
            'comments' => $this->prepareCommentsData($comments),
            'msgShowMore' => Translation::get(key: 'msgShowMore'),
        ]);
    }

    /**
     * Prepares comment data for the Twig macro
     *
     * @param array $comments Array of Comment objects
     * @throws \Exception
     * @return array
     */
    private function prepareCommentsData(array $comments): array
    {
        $date = $this->container->get('phpmyfaq.date');
        $mail = $this->container->get('phpmyfaq.mail');
        $gravatar = $this->container->get('phpmyfaq.services.gravatar');

        $preparedComments = [];
        $gravatarImages = [];
        $safeEmails = [];
        $formattedDates = [];

        foreach ($comments as $comment) {
            $commentId = $comment->getId();
            $preparedComments[] = [
                'id' => $commentId,
                'email' => $comment->getEmail(),
                'username' => Strings::htmlentities($comment->getUsername()),
                'date' => $comment->getDate(),
                'comment' => Utils::parseUrl($comment->getComment()),
            ];

            $gravatarImages[$commentId] = $gravatar->getImage($comment->getEmail(), ['class' => 'img-thumbnail']);
            $safeEmails[$commentId] = $mail->safeEmail($comment->getEmail());
            $formattedDates[$commentId] = $date->format($comment->getDate());
        }

        return [
            'comments' => $preparedComments,
            'gravatarImages' => $gravatarImages,
            'safeEmails' => $safeEmails,
            'formattedDates' => $formattedDates,
        ];
    }
}
