<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

class TagController extends AbstractAdministrationController
{
    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route('/tags', name: 'admin.tags', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userIsAuthenticated();

        $tagData = $this->container->get('phpmyfaq.tags')->getAllTags();

        return $this->render(
            '@admin/content/tags.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                'adminHeaderTags' => Translation::get('msgTags'),
                'csrfToken' => Token::getInstance($this->container->get('session'))->getTokenInput('tags'),
                'tags' => $tagData,
                'noTags' => Translation::get('ad_news_nodata'),
                'buttonEdit' => Translation::get('ad_user_edit'),
                'msgConfirm' => Translation::get('ad_user_del_3'),
                'buttonDelete' => Translation::get('msgDelete'),
            ]
        );
    }
}
