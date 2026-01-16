<?php

/**
 * Imprint Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-16
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use Exception;
use phpMyFAQ\CustomPage;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ImprintController extends AbstractFrontController
{
    /**
     * Displays the imprint page - either a custom page or redirects to configured URL.
     * @throws Exception
     */
    #[Route(path: '/imprint.html', name: 'public.imprint')]
    public function index(Request $request): Response
    {
        $imprintUrl = $this->configuration->get('main.imprintURL');

        // Check if this is a reference to a custom page (format: "page:slug")
        if (str_starts_with((string) $imprintUrl, 'page:')) {
            $slug = substr((string) $imprintUrl, 5);
            $customPage = new CustomPage($this->configuration);
            $page = $customPage->getBySlug($slug);

            if ($page && $page->isActive()) {
                // Redirect to the custom page URL
                $pageUrl = $this->configuration->getDefaultUrl() . 'page/' . $page->getSlug() . '.html';
                return new RedirectResponse($pageUrl);
            }
        }

        // Default behavior: redirect to external URL or 404
        if ((string) $imprintUrl !== '') {
            return new RedirectResponse($imprintUrl);
        }

        // If no URL configured, return 404
        return $this->render('error/404.twig', [], Response::HTTP_NOT_FOUND);
    }
}
