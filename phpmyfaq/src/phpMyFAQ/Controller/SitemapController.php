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
use Symfony\Component\HttpFoundation\Response;

final class SitemapController extends AbstractController
{
    private const int PMF_SITEMAP_GOOGLE_MAX_URLS = 50000;

    /**
     * @throws TemplateException|Exception|\Exception
     */
    public function index(): Response
    {
        $response = new Response();

        $siteMapEnabled = $this->configuration->get(item: 'seo.enableXMLSitemap');
        if (!$siteMapEnabled) {
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
            $response->setContent('XML Sitemap is disabled.');
            return $response;
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

        $xml = $this->renderView(pathToTwigFile: './sitemap.xml.twig', templateVars: ['urls' => $urls]);

        $response->headers->set(key: 'Content-Type', values: 'text/xml');
        $response->setStatusCode(Response::HTTP_OK);
        $response->setContent($xml);

        return $response;
    }
}
