<?php

/**
 * The News Administration Controller
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
 * @since     2024-12-01
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\Extensions\FormatDateTwigExtension;
use phpMyFAQ\Twig\Extensions\IsoDateTwigExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\Extension\AttributeExtension;

final class NewsController extends AbstractAdministrationController
{
    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/news', name: 'admin.news', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::NEWS_ADD);
        $this->userHasPermission(PermissionType::NEWS_DELETE);
        $this->userHasPermission(PermissionType::NEWS_EDIT);

        $news = $this->container->get('phpmyfaq.news');

        $this->addExtension(new AttributeExtension(IsoDateTwigExtension::class));
        $this->addExtension(new AttributeExtension(FormatDateTwigExtension::class));
        return $this->render('@admin/content/news.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
            'news' => $news->getHeader(),
        ]);
    }

    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route('/news/add', name: 'admin.news.add', methods: ['GET'])]
    public function add(Request $request): Response
    {
        $this->userHasPermission(PermissionType::NEWS_ADD);

        $this->addExtension(new AttributeExtension(IsoDateTwigExtension::class));
        $this->addExtension(new AttributeExtension(FormatDateTwigExtension::class));
        return $this->render('@admin/content/news.add.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
            'userEmail' => $this->currentUser->getUserData('email'),
            'userName' => $this->currentUser->getUserData('display_name'),
        ]);
    }

    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route('/news/edit/:newsId', name: 'admin.news.edit', methods: ['GET'])]
    public function edit(Request $request): Response
    {
        $this->userHasPermission(PermissionType::NEWS_ADD);

        $newsId = Filter::filterVar($request->get('newsId'), FILTER_VALIDATE_INT);

        $news = $this->container->get('phpmyfaq.news');
        $comment = $this->container->get('phpmyfaq.comments');
        $newsData = $news->get($newsId, true);

        $comments = $comment->getCommentsData($newsId, CommentType::NEWS);

        $this->addExtension(new AttributeExtension(IsoDateTwigExtension::class));
        $this->addExtension(new AttributeExtension(FormatDateTwigExtension::class));
        return $this->render('@admin/content/news.edit.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
            'newsData' => $newsData,
            'newsDataContent' => isset($newsData['content'])
                ? htmlspecialchars((string) $newsData['content'], ENT_QUOTES)
                : '',
            'comments' => $comments,
            'newsId' => $newsId,
            'commentTypeNews' => CommentType::NEWS,
        ]);
    }

    /**
     * @return array<string, string>
     * @throws \Exception
     * @throws LoaderError
     * @throws Exception
     * @todo move to Twig translation filter
     */
    private function getBaseTemplateVars(): array
    {
        $session = $this->container->get('session');
        $user = $this->currentUser;
        $language = $this->configuration->getLanguage()->getLanguage();

        return [
            'permissionAddNews' => $user->perm->hasPermission($user->getUserId(), PermissionType::NEWS_ADD),
            'permissionEditNews' => $user->perm->hasPermission($user->getUserId(), PermissionType::NEWS_EDIT),
            'permissionDeleteNews' => $user->perm->hasPermission($user->getUserId(), PermissionType::NEWS_DELETE),
            'defaultUrl' => $this->configuration->getDefaultUrl(),
            'enableWysiwyg' => $this->configuration->get('main.enableWysiwygEditor'),
            'ad_news_add' => Translation::get('ad_news_add'),
            'csrfToken_saveNews' => Token::getInstance($session)->getTokenString('save-news'),
            'ad_news_author_name' => Translation::get('ad_news_author_name'),
            'ad_news_set_active' => Translation::get('ad_news_set_active'),
            'ad_news_link_url' => Translation::get('ad_news_link_url'),
            'ad_news_link_title' => Translation::get('ad_news_link_title'),
            'ad_news_link_target' => Translation::get('ad_news_link_target'),
            'ad_news_link_window' => Translation::get('ad_news_link_window'),
            'ad_news_link_faq' => Translation::get('ad_news_link_faq'),
            'ad_news_link_parent' => Translation::get('ad_news_link_parent'),
            'selectLanguage' => LanguageHelper::renderSelectLanguage($language, false, [], 'langTo'),
            'ad_news_expiration_window' => Translation::get('ad_news_expiration_window'),
            'ad_news_from' => Translation::get('ad_news_from'),
            'ad_news_to' => Translation::get('ad_news_to'),
            'ad_entry_back' => Translation::get('ad_entry_back'),
            'ad_menu_news_add' => Translation::get('ad_menu_news_add'),
            'ad_news_headline' => Translation::get('ad_news_headline'),
            'ad_news_date' => Translation::get('ad_news_date'),
            'ad_news_update' => Translation::get('ad_news_update'),
            'ad_news_delete' => Translation::get('ad_news_delete'),
            'ad_news_nodata' => Translation::get('ad_news_nodata'),
            'ad_news_edit' => Translation::get('ad_news_edit'),
            'ad_news_header' => Translation::get('ad_news_header'),
            'ad_news_text' => Translation::get('ad_news_text'),
            'ad_news_allowComments' => Translation::get('ad_news_allowComments'),
            'msgLanguage' => Translation::get('msgLanguage'),
            'ad_entry_comment' => Translation::get('ad_entry_comment'),
            'ad_entry_commentby' => Translation::get('ad_entry_commentby'),
            'newsCommentDate' => Translation::get('newsCommentDate'),
            'ad_news_insertfail' => Translation::get('ad_news_insertfail'),
            'msgNews' => Translation::get('msgNews'),
            'ad_news_data' => Translation::get('ad_news_data'),
            'ad_news_del' => Translation::get('ad_news_del'),
            'ad_news_nodelete' => Translation::get('ad_news_nodelete'),
            'ad_news_yesdelete' => Translation::get('ad_news_yesdelete'),
            'ad_news_delsuc' => Translation::get('ad_news_delsuc'),
            'ad_news_updatesuc' => Translation::get('ad_news_updatesuc'),
            'msgDeleteNews' => Translation::get('msgDeleteNews'),
            'csrfToken_deleteNews' => Token::getInstance($session)->getTokenString('delete-news'),
            'csrfToken_updateNews' => Token::getInstance($session)->getTokenString('update-news'),
            'ad_entry_active' => Translation::get('ad_entry_active'),
            'csrfToken_activateNews' => Token::getInstance($session)->getTokenString('activate-news'),
        ];
    }
}
