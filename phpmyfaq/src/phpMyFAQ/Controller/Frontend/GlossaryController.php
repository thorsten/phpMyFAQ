<?php

/**
 * Glossary Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-09-03
 */

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Pagination;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

final class GlossaryController extends AbstractFrontController
{
    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */ #[Route(path: '/glossary.html', name: 'public.glossary')]
    public function index(Request $request): Response
    {
        $faqSession = $this->container->get('phpmyfaq.user.session');
        $faqSession->setCurrentUser($this->currentUser);
        $faqSession->userTracking('glossary', 0);

        $page = Filter::filterVar($request->query->get('page'), FILTER_VALIDATE_INT, 1);

        $glossary = $this->container->get('phpmyfaq.glossary');
        $glossaryItems = $glossary->fetchAll();

        $itemsPerPage = 8;

        $baseUrl = sprintf('%sglossary.html?page=%d', $this->configuration->getDefaultUrl(), $page);

        // Pagination options
        $options = [
            'baseUrl' => $baseUrl,
            'total' => is_countable($glossaryItems) ? count($glossaryItems) : 0,
            'perPage' => $itemsPerPage,
            'pageParamName' => 'page',
        ];
        $pagination = new Pagination($options);

        return $this->render('glossary.twig', [
            ...$this->getHeader($request),
            'title' => sprintf('%s - %s', Translation::get(key: 'ad_menu_glossary'), $this->configuration->getTitle()),
            'metaDescription' => sprintf(
                Translation::get(key: 'msgGlossaryMetaDesc'),
                $this->configuration->getTitle(),
            ),
            'pageHeader' => Translation::get(key: 'ad_menu_glossary'),
            'glossaryItems' => array_slice($glossaryItems, ($page - 1) * $itemsPerPage, $itemsPerPage),
            'pagination' => $pagination->render(),
        ]);
    }
}
