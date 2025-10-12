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
use phpMyFAQ\Session\Token;
use phpMyFAQ\Twig\Extensions\FaqTwigExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\Extension\AttributeExtension;
use Twig\Extra\Intl\IntlExtension;

class CommentsController extends AbstractAdministrationController
{
    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route('/comments')]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::COMMENT_DELETE);

        $comment = $this->container->get('phpmyfaq.comments');

        $faqComments = $comment->getAllComments();
        $newsComments = $comment->getAllComments(CommentType::NEWS);

        $this->addExtension(new IntlExtension());
        $this->addExtension(new AttributeExtension(FaqTwigExtension::class));
        return $this->render('@admin/content/comments.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'currentLocale' => $this->configuration->getLanguage()->getLanguage(),
            'faqComments' => $faqComments,
            'newsComments' => $newsComments,
            'csrfToken' => Token::getInstance($this->container->get('session'))->getTokenString('delete-comment'),
        ]);
    }
}
