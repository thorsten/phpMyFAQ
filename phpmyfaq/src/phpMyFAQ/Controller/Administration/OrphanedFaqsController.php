<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

class OrphanedFaqsController extends AbstractAdministrationController
{
    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/orphaned-faqs', name: 'admin.content.orphaned-faqs', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $faq = $this->container->get('phpmyfaq.faq');

        return $this->render(
            '@admin/content/orphaned-faqs.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                'orphanedFaqs' => $faq->getOrphanedFaqs(),
            ]
        );
    }
}
