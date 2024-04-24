<?php

/**
 * The Admin News Controller
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <modelrailroader@gmx-topmail.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-04-20
 */

namespace phpMyFAQ\Controller\Administration;

use DateTime;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Entity\NewsMessage;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\News;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class NewsController extends AbstractController
{
    #[Route('admin/api/news/add')]
    public function addNews(Request $request)
    {
        $this->userHasPermission(PermissionType::NEWS_ADD);
        $data = json_decode($request->getContent());

        $news = new News($this->configuration);

        if (!Token::getInstance()->verifyToken('save-news', $data->csrfToken)) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        $dateStart = Filter::filterVar($data->dateStart, FILTER_SANITIZE_SPECIAL_CHARS);
        $dateEnd = Filter::filterVar($data->dateEnd, FILTER_SANITIZE_SPECIAL_CHARS);
        $header = Filter::filterVar($data->newsHeader, FILTER_SANITIZE_SPECIAL_CHARS);
        $content = Filter::filterVar($data->news, FILTER_SANITIZE_SPECIAL_CHARS);
        $author = Filter::filterVar($data->authorName, FILTER_SANITIZE_SPECIAL_CHARS);
        $email = Filter::filterVar($data->authorEmail, FILTER_VALIDATE_EMAIL);
        $active = Filter::filterVar($data->active, FILTER_SANITIZE_SPECIAL_CHARS);
        $comment = Filter::filterVar($data->comment, FILTER_SANITIZE_SPECIAL_CHARS);
        $link = Filter::filterVar($data->link, FILTER_SANITIZE_SPECIAL_CHARS);
        $linkTitle = Filter::filterVar($data->linkTitle, FILTER_SANITIZE_SPECIAL_CHARS);
        $newsLang = Filter::filterVar($data->langTo, FILTER_SANITIZE_SPECIAL_CHARS);
        $target = Filter::filterVar($data->target, FILTER_SANITIZE_SPECIAL_CHARS);

        $newsMessage = new NewsMessage();
        $newsMessage
            ->setLanguage($newsLang)
            ->setHeader($header)
            ->setMessage(html_entity_decode((string)$content))
            ->setAuthor($author)
            ->setEmail($email)
            ->setActive($active)
            ->setComment($comment)
            ->setDateStart(new DateTime($dateStart))
            ->setDateEnd(new DateTime($dateEnd))
            ->setLink($link ?? '')
            ->setLinkTitle($linkTitle ?? '')
            ->setLinkTarget($target ?? '')
            ->setCreated(new DateTime());

        if ($news->create($newsMessage)) {
            return $this->json(['success' => Translation::get('ad_news_updatesuc')], Response::HTTP_OK);
        } else {
            return $this->json(['error' => Translation::get('ad_news_insertfail')], Response::HTTP_BAD_GATEWAY);
        }
    }

    #[Route('admin/api/news/delete')]
    public function deleteNews(Request $request)
    {
        $this->userHasPermission(PermissionType::NEWS_DELETE);
        $data = json_decode($request->getContent());

        $news = new News($this->configuration);

        if (!Token::getInstance()->verifyToken('delete-news', $data->csrfToken)) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        $deleteId = Filter::filterVar($data->id, FILTER_VALIDATE_INT);

        if ($news->delete((int)$deleteId)) {
            return $this->json(['success' => Translation::get('ad_news_delsuc')], Response::HTTP_OK);
        } else {
            return $this->json(['error' => Translation::get('ad_news_updatefail')], Response::HTTP_BAD_GATEWAY);
        }
    }

    #[Route('admin/api/news/update')]
    public function updateNews(Request $request)
    {
        $this->userHasPermission(PermissionType::NEWS_DELETE);
        $data = json_decode($request->getContent());

        $news = new News($this->configuration);

        if (!Token::getInstance()->verifyToken('update-news', $data->csrfToken)) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        $newsId = Filter::filterVar($data->id, FILTER_VALIDATE_INT);
        $dateStart = Filter::filterVar($data->dateStart, FILTER_SANITIZE_SPECIAL_CHARS);
        $dateEnd = Filter::filterVar($data->dateEnd, FILTER_SANITIZE_SPECIAL_CHARS);
        $header = Filter::filterVar($data->newsHeader, FILTER_SANITIZE_SPECIAL_CHARS);
        $content = Filter::filterVar($data->news, FILTER_SANITIZE_SPECIAL_CHARS);
        $author = Filter::filterVar($data->authorName, FILTER_SANITIZE_SPECIAL_CHARS);
        $email = Filter::filterVar($data->authorEmail, FILTER_VALIDATE_EMAIL);
        $active = Filter::filterVar($data->active, FILTER_SANITIZE_SPECIAL_CHARS);
        $comment = Filter::filterVar($data->comment, FILTER_SANITIZE_SPECIAL_CHARS);
        $link = Filter::filterVar($data->link, FILTER_SANITIZE_SPECIAL_CHARS);
        $linkTitle = Filter::filterVar($data->linkTitle, FILTER_SANITIZE_SPECIAL_CHARS);
        $newsLang = Filter::filterVar($data->langTo, FILTER_SANITIZE_SPECIAL_CHARS);
        $target = Filter::filterVar($data->target, FILTER_SANITIZE_SPECIAL_CHARS);

        $newsMessage = new NewsMessage();
        $newsMessage
            ->setId($newsId)
            ->setLanguage($newsLang)
            ->setHeader($header)
            ->setMessage(html_entity_decode((string)$content))
            ->setAuthor($author)
            ->setEmail($email)
            ->setActive($active)
            ->setComment($comment)
            ->setDateStart(new DateTime($dateStart))
            ->setDateEnd(new DateTime($dateEnd))
            ->setLink($link ?? '')
            ->setLinkTitle($linkTitle ?? '')
            ->setLinkTarget($target ?? '')
            ->setCreated(new DateTime());

        if ($news->update($newsMessage)) {
            return $this->json(['success' => Translation::get('ad_news_updatesuc')], Response::HTTP_OK);
        } else {
            return $this->json(['error' => Translation::get('ad_news_updatefail')], Response::HTTP_BAD_GATEWAY);
        }
    }

    #[Route('admin/api/news/activate')]
    public function activateNews(Request $request)
    {
        $this->userHasPermission(PermissionType::NEWS_EDIT);
        $data = json_decode($request->getContent());

        $news = new News($this->configuration);

        if (!Token::getInstance()->verifyToken('activate-news', $data->csrfToken)) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        $id = Filter::filterVar($data->id, FILTER_VALIDATE_INT);
        $status = Filter::filterVar($data->status, FILTER_SANITIZE_SPECIAL_CHARS);

        if ($news->activate($id, $status)) {
            return $this->json(['success' => Translation::get('ad_news_updatesuc')], Response::HTTP_OK);
        } else {
            return $this->json(['error' => Translation::get('ad_news_updatefail')], Response::HTTP_BAD_GATEWAY);
        }
    }
}
