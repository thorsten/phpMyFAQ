<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

class StickyFaqsController extends AbstractAdministrationController
{
    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/sticky-faqs')]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        $customOrdering = $this->configuration->get('records.orderStickyFaqsCustom');

        return $this->render(
            '@admin/content/sticky-faqs.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                'stickyFAQsHeader' => Translation::get('stickyRecordsHeader'),
                'stickyData' => $this->container->get('phpmyfaq.faq')->getStickyFaqsData(),
                'sortableDisabled' => ($customOrdering === false) ? 'sortable-disabled' : '',
                'orderingStickyFaqsActivated' => $this->configuration->get('records.orderStickyFaqsCustom'),
                'alertMessageStickyFaqsDeactivated' => Translation::get('msgOrderStickyFaqsCustomDeactivated'),
                'alertMessageNoStickyRecords' => Translation::get('msgNoStickyFaqs'),
                'csrfToken' => Token::getInstance($this->container->get('session'))->getTokenString('order-stickyfaqs')
            ]
        );
    }
}
