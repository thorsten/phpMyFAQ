<?php

/**
 * The XML Sitemap Service
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
 * @since     2026-02-23
 */

declare(strict_types=1);

namespace phpMyFAQ\Seo;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\CustomPage;
use phpMyFAQ\Faq\Statistics as FaqStatistics;
use phpMyFAQ\Twig\TwigWrapper;

class SitemapXmlService
{
    private const int PMF_SITEMAP_GOOGLE_MAX_URLS = 50_000;

    public function __construct(
        private readonly Configuration $configuration,
        private readonly FaqStatistics $faqStatistics,
        private readonly CustomPage $customPage,
    ) {
    }

    public function isEnabled(): bool
    {
        return (bool) $this->configuration->get(item: 'seo.enableXMLSitemap');
    }

    /**
     * Collects all URLs for the sitemap from FAQs and active custom pages.
     *
     * @return array<int, array{loc: string, lastmod: string, priority: string}>
     */
    public function collectUrls(): array
    {
        $items = $this->faqStatistics->getTopTenData(self::PMF_SITEMAP_GOOGLE_MAX_URLS - 1);

        $urls = [];
        foreach ($items as $item) {
            $urls[] = [
                'loc' => $item['url'],
                'lastmod' => $item['date'],
                'priority' => '1.00',
            ];
        }

        $pages = $this->customPage->getAllPages();

        foreach ($pages as $page) {
            if ($page['active'] !== 'y') {
                continue;
            }

            $urls[] = [
                'loc' => $this->configuration->getDefaultUrl() . 'page/' . $page['slug'] . '.html',
                'lastmod' => $page['updated'] ?? $page['created'],
                'priority' => '0.80',
            ];
        }

        return $urls;
    }

    /**
     * Generates the sitemap XML content.
     *
     * @throws Exception|\Exception
     * @return string|null Returns XML content or null if sitemap is disabled
     */
    public function generateXml(): ?string
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $urls = $this->collectUrls();

        $twigWrapper = new TwigWrapper(
            PMF_ROOT_DIR . '/assets/templates',
            false,
            $this->configuration->getTemplateSet(),
        );

        $template = $twigWrapper->loadTemplate('./sitemap.xml.twig');

        return $template->render(['urls' => $urls]);
    }
}
