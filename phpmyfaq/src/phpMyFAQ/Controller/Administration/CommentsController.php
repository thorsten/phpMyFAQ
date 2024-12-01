<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Template\Extensions\FaqTwigExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
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
        $this->addExtension(new FaqTwigExtension());
        return $this->render(
            '@admin/content/comments.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                'currentLocale' => $this->configuration->getLanguage()->getLanguage(),
                'faqComments' => $faqComments,
                'newsComments' => $newsComments,
                'csrfToken' => Token::getInstance($this->container->get('session'))->getTokenString('delete-comment'),
            ]
        );
    }
}
