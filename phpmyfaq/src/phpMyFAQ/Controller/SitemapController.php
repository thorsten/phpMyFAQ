<?php

/**
 * The XML Sitemap Controller
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
 * @since     2024-06-16
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Seo\SitemapXmlService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SitemapController extends AbstractController
{
    public function __construct(
        private readonly SitemapXmlService $sitemapXmlService,
    ) {
        parent::__construct();
    }

    /**
     * Returns gzipped sitemap.xml or redirects to plain XML if zlib is not available
     *
     * @throws Exception|\Exception
     */
    #[Route(path: '/sitemap.gz', name: 'public.sitemap.gz')]
    public function sitemapGz(): Response
    {
        return $this->generateGzippedSitemap();
    }

    /**
     * Returns gzipped sitemap.xml or redirects to plain XML if zlib is not available
     *
     * @throws Exception|\Exception
     */
    #[Route(path: '/sitemap.xml.gz', name: 'public.sitemap.xml.gz')]
    public function sitemapXmlGz(): Response
    {
        return $this->generateGzippedSitemap();
    }

    /**
     * @throws Exception|\Exception
     */
    #[Route(path: '/sitemap.xml', name: 'public.sitemap.xml')]
    public function index(): Response
    {
        $xml = $this->sitemapXmlService->generateXml();

        if ($xml === null) {
            $response = new Response();
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
            $response->setContent('XML Sitemap is disabled.');
            return $response;
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $response->setStatusCode(Response::HTTP_OK);
        $response->setContent($xml);

        return $response;
    }

    /**
     * Generates a gzipped sitemap response or redirects if zlib is unavailable
     *
     * @throws Exception|\Exception
     */
    private function generateGzippedSitemap(): Response
    {
        // Fallback to plain XML if zlib extension is not available
        if (!extension_loaded('zlib')) {
            return new RedirectResponse('/sitemap.xml', Response::HTTP_MOVED_PERMANENTLY);
        }

        $xml = $this->sitemapXmlService->generateXml();

        if ($xml === null) {
            $response = new Response();
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
            $response->setContent('XML Sitemap is disabled.');
            return $response;
        }

        $gzipped = gzencode($xml, level: 9);

        $response = new Response($gzipped);
        $response->headers->set('Content-Type', 'application/x-gzip');
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Disposition', 'attachment; filename="sitemap.xml.gz"');
        $response->setStatusCode(Response::HTTP_OK);

        return $response;
    }
}
