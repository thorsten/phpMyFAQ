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
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-06-16
 */

namespace phpMyFAQ\Controller;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Template\TemplateException;
use Symfony\Component\HttpFoundation\Response;

class SitemapController extends AbstractController
{
    private const PMF_SITEMAP_GOOGLE_MAX_URLS = 50000;

    /**
     * @throws TemplateException|Exception|\Exception
     */
    public function index(): Response
    {
        $response = new Response();
        $faqStatistics = $this->container->get('phpmyfaq.faq.statistics');

        $items = $faqStatistics->getTopTenData(self::PMF_SITEMAP_GOOGLE_MAX_URLS - 1);

        $urls = [];
        foreach ($items as $item) {
            $urls[] = [
                'loc' => $item['url'],
                'lastmod' => $item['date'],
                'priority' => '1.00',
            ];
        }

        $xml = $this->renderView('./sitemap.xml.twig', ['urls' => $urls]);

        $response->headers->set('Content-Type', 'text/xml');
        $response->setStatusCode(Response::HTTP_OK);
        $response->setContent($xml);

        return $response;
    }
}
