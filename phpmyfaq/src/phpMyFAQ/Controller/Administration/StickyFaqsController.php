<?php

/**
 * The Sticky FAQs Administration Controller
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
 * @since     2024-12-02
 */

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
