<?php

/**
 * Migration for phpMyFAQ 4.1.0-alpha.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-25
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Setup\Migration\AbstractMigration;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;

readonly class Migration410Alpha extends AbstractMigration
{
    public function getVersion(): string
    {
        return '4.1.0-alpha';
    }

    public function getDependencies(): array
    {
        return ['4.0.9'];
    }

    public function getDescription(): string
    {
        return 'robots.txt content configuration for AI crawlers';
    }

    public function up(OperationRecorder $recorder): void
    {
        $robotsText = <<<EOT
            User-agent: Amazonbot
            User-agent: anthropic-ai
            User-agent: Applebot-Extended
            User-agent: Bytespider
            User-agent: CCBot
            User-agent: ChatGPT-User
            User-agent: ClaudeBot
            User-agent: Claude-Web
            User-agent: cohere-ai
            User-agent: Diffbot
            User-agent: FacebookBot
            User-agent: facebookexternalhit
            User-agent: FriendlyCrawler
            User-agent: Google-Extended
            User-agent: GoogleOther
            User-agent: GoogleOther-Image
            User-agent: GoogleOther-Video
            User-agent: GPTBot
            User-agent: ICC-Crawler
            User-agent: ImagesiftBot
            User-agent: img2dataset
            User-agent: Meta-ExternalAgent
            User-agent: OAI-SearchBot
            User-agent: omgili
            User-agent: omgilibot
            User-agent: PerplexityBot
            User-agent: PetalBot
            User-agent: Scrapy
            User-agent: Timpibot
            User-agent: VelenPublicWebCrawler
            User-agent: YouBot
            User-agent: Meta-ExternalFetcher
            User-agent: Applebot
            Disallow: /

            User-agent: *
            Disallow: /admin/

            Sitemap: /sitemap.xml
            EOT;

        $recorder->addConfig('seo.contentRobotsText', $robotsText);
    }
}
