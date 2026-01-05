<?php

/**
 * The Administration comments Controller
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
 * @since     2024-12-01
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Pagination;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Twig\Extensions\FaqTwigExtension;
use phpMyFAQ\Twig\Extensions\TitleSlugifierTwigExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\Extension\AttributeExtension;
use Twig\Extra\Intl\IntlExtension;

final class CommentsController extends AbstractAdministrationController
{
    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: '/comments')]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::COMMENT_DELETE);

        $page = Filter::filterVar($request->query->get(key: 'page'), FILTER_VALIDATE_INT);
        $page = max(1, $page);

        $comment = $this->container->get(id: 'phpmyfaq.comments');

        $itemsPerPage = 10;
        $allFaqComments = $comment->getAllComments();
        $allNewsComments = $comment->getAllComments(CommentType::NEWS);

        $faqComments = array_slice($allFaqComments, ($page - 1) * $itemsPerPage, $itemsPerPage);
        $newsComments = array_slice($allNewsComments, ($page - 1) * $itemsPerPage, $itemsPerPage);

        $news = $this->container->get(id: 'phpmyfaq.news');
        $newsHeader = $news->getHeader();

        $baseUrl = sprintf('%sadmin/comments?page=%d', $this->configuration->getDefaultUrl(), $page);

        $faqCommentsPagination = new Pagination([
            'baseUrl' => $baseUrl,
            'total' => is_countable($allFaqComments) ? count($allFaqComments) : 0,
            'perPage' => $itemsPerPage,
        ]);

        $newsCommentsPagination = new Pagination([
            'baseUrl' => $baseUrl,
            'total' => is_countable($allNewsComments) ? count($allNewsComments) : 0,
            'perPage' => $itemsPerPage,
        ]);

        $this->addExtension(new IntlExtension());
        $this->addExtension(new AttributeExtension(FaqTwigExtension::class));
        $this->addExtension(new AttributeExtension(TitleSlugifierTwigExtension::class));
        return $this->render(file: '@admin/content/comments.twig', context: [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'currentLocale' => $this->configuration->getLanguage()->getLanguage(),
            'faqComments' => $faqComments,
            'newsComments' => $newsComments,
            'faqCommentsPagination' => $faqCommentsPagination->render(),
            'newsCommentsPagination' => $newsCommentsPagination->render(),
            'newsHeader' => $newsHeader,
            'csrfToken' => Token::getInstance($this->session)->getTokenString(page: 'delete-comment'),
        ]);
    }
}
