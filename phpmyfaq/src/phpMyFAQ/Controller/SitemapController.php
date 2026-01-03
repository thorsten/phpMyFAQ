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
use phpMyFAQ\Twig\TemplateException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SitemapController extends AbstractController
{
    private const int PMF_SITEMAP_GOOGLE_MAX_URLS = 50000;

    /**
     * Returns gzipped sitemap.xml or redirects to plain XML if zlib is not available
     *
     * @throws TemplateException|Exception|\Exception
     */
    #[Route(path: '/sitemap.gz', name: 'public.sitemap.gz')]
    public function sitemapGz(): Response
    {
        return $this->generateGzippedSitemap();
    }

    /**
     * Returns gzipped sitemap.xml or redirects to plain XML if zlib is not available
     *
     * @throws TemplateException|Exception|\Exception
     */
    #[Route(path: '/sitemap.xml.gz', name: 'public.sitemap.xml.gz')]
    public function sitemapXmlGz(): Response
    {
        return $this->generateGzippedSitemap();
    }

    /**
     * @throws TemplateException|Exception|\Exception
     */
    #[Route(path: '/sitemap.xml', name: 'public.sitemap.xml')]
    public function index(): Response
    {
        $response = new Response();

        $xml = $this->generateSitemapXml();

        if ($xml === null) {
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
            $response->setContent('XML Sitemap is disabled.');
            return $response;
        }

        $response->headers->set('Content-Type', 'text/xml');
        $response->setStatusCode(Response::HTTP_OK);
        $response->setContent($xml);

        return $response;
    }

    /**
     * Generates the sitemap XML content
     *
     * @throws TemplateException|Exception|\Exception
     * @return string|null Returns XML content or null if sitemap is disabled
     */
    private function generateSitemapXml(): ?string
    {
        $siteMapEnabled = $this->configuration->get(item: 'seo.enableXMLSitemap');
        if (!$siteMapEnabled) {
            return null;
        }

        $faqStatistics = $this->container->get(id: 'phpmyfaq.faq.statistics');
        $items = $faqStatistics->getTopTenData(self::PMF_SITEMAP_GOOGLE_MAX_URLS - 1);

        $urls = [];
        foreach ($items as $item) {
            $urls[] = [
                'loc' => $item['url'],
                'lastmod' => $item['date'],
                'priority' => '1.00',
            ];
        }

        return $this->renderView(pathToTwigFile: './sitemap.xml.twig', templateVars: ['urls' => $urls]);
    }

    /**
     * Generates a gzipped sitemap response or redirects if zlib is unavailable
     *
     * @throws TemplateException|Exception|\Exception
     */
    private function generateGzippedSitemap(): Response
    {
        // Fallback to plain XML if zlib extension is not available
        if (!extension_loaded('zlib')) {
            return new RedirectResponse('/sitemap.xml', Response::HTTP_MOVED_PERMANENTLY);
        }

        $xml = $this->generateSitemapXml();

        if ($xml === null) {
            $response = new Response();
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
            $response->setContent('XML Sitemap is disabled.');
            return $response;
        }

        $gzipped = gzencode($xml, 9);

        $response = new Response($gzipped);
        $response->headers->set('Content-Type', 'application/x-gzip');
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Disposition', 'attachment; filename="sitemap.xml.gz"');
        $response->setStatusCode(Response::HTTP_OK);

        return $response;
    }
}
